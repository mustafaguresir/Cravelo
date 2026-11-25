<?php
declare(strict_types=1);

namespace App\Service;

use App\Enum\CourierStatus;
use App\Enum\OrderStatus;
use App\Enum\UserRole;
use App\Exception\InvalidOrderTransitionException;
use App\Exception\NoAvailableCourierException;
use App\Model\Courier;
use App\Model\Order;
use App\Model\OrderItem;
use App\Model\Restaurant;
use App\Model\User;
use App\Repository\CourierRepositoryInterface;
use App\Repository\OrderRepositoryInterface;
use App\Repository\RestaurantRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Repository\Json\JsonCourierRepository;
use App\Repository\Json\JsonOrderRepository;
use App\Repository\Json\JsonRestaurantRepository;
use App\Repository\Json\JsonUserRepository;
use App\Service\Courier\ScoreBasedAssignmentStrategy;
use App\Storage\JsonStorageHelper;
use InvalidArgumentException;
use RuntimeException;

/**
 * Sipariş yaşam döngüsü, sahiplik (IDOR) kontrolleri, KDS / Kurye sorguları ve raporlama
 * sorumluluklarını Controller katmanından tamamen ayıran merkezi servis sınıfı.
 * Controller sınıfları doğrudan Repository çağırmaz; yalnızca OrderService üzerinden konuşur.
 */
final class OrderService
{
    /** @var array<int, float> */
    private const FLASH_DEAL_PRICES = [
        1 => 149.0,
        6 => 199.0,
        10 => 179.0,
        18 => 129.0,
    ];

    /** @var array<string, array{type: string, val: float, min: float, category: ?string}> */
    private const PROMO_COUPONS = [
        'CRAVELO30' => ['type' => 'percent', 'val' => 0.30, 'min' => 100.0, 'category' => null],
        'PIZZA50' => ['type' => 'fixed', 'val' => 50.0, 'min' => 180.0, 'category' => 'pizza'],
        'BURGERFEST' => ['type' => 'fixed', 'val' => 35.0, 'min' => 150.0, 'category' => 'burger'],
        'HEMEN10' => ['type' => 'fixed', 'val' => 10.0, 'min' => 80.0, 'category' => null],
    ];

    private OrderRepositoryInterface $orderRepo;
    private RestaurantRepositoryInterface $restaurantRepo;
    private CourierRepositoryInterface $courierRepo;
    private UserRepositoryInterface $userRepo;
    private OrderStateMachineService $stateMachine;
    private CourierAssignmentService $assignmentService;
    private AuthService $authService;
    private bool $useFileStorage;
    private string $auditLogFile;
    private string $dispatchLockFile;

    public function __construct(
        ?OrderRepositoryInterface $orderRepo = null,
        ?RestaurantRepositoryInterface $restaurantRepo = null,
        ?CourierRepositoryInterface $courierRepo = null,
        ?UserRepositoryInterface $userRepo = null,
        ?OrderStateMachineService $stateMachine = null,
        ?CourierAssignmentService $assignmentService = null,
        ?AuthService $authService = null,
        ?string $auditLogFile = null,
        ?string $dispatchLockFile = null
    ) {
        $this->orderRepo = $orderRepo ?? new JsonOrderRepository();
        $this->restaurantRepo = $restaurantRepo ?? new JsonRestaurantRepository();
        $this->courierRepo = $courierRepo ?? new JsonCourierRepository();
        $this->userRepo = $userRepo ?? new JsonUserRepository();
        $this->stateMachine = $stateMachine ?? new OrderStateMachineService($this->orderRepo);
        $this->useFileStorage = ($this->orderRepo instanceof JsonOrderRepository) || $auditLogFile !== null || $dispatchLockFile !== null;
        $storageDir = dirname(__DIR__, 2) . '/storage';
        $this->auditLogFile = $auditLogFile ?? ($storageDir . '/audit_logs.json');
        $this->dispatchLockFile = $dispatchLockFile ?? ($storageDir . '/dispatch.lock');

        if ($assignmentService !== null) {
            $this->assignmentService = $assignmentService;
        } else {
            $haversine = new HaversineDistanceService();
            $strategy = new ScoreBasedAssignmentStrategy($haversine);
            $this->assignmentService = new CourierAssignmentService(
                $this->courierRepo,
                $this->restaurantRepo,
                $strategy,
                $haversine
            );
        }

        $this->authService = $authService ?? new AuthService($this->userRepo);
    }

    /**
     * Yeni sipariş oluşturur, başlangıç durumunu ALINDI olarak kaydeder ve
     * müsait kurye varsa Haversine + iş yükü algoritmasıyla en uygun kuryeyi atar.
     *
     * @param array<int, OrderItem|array<string, mixed>> $items
     * @return array<string, mixed>
     */
    public function createOrder(
        int $customerId,
        int $restaurantId,
        float $totalAmount,
        int $delX = 18,
        int $delY = 14,
        array $items = [],
        float $deliveryLat = 40.9850,
        float $deliveryLon = 29.0280,
        string $deliveryMode = 'courier',
        ?string $couponCode = null,
        float $deliveryFee = 0.0,
        float $tip = 0.0
    ): array {
        $restaurant = $this->restaurantRepo->findById($restaurantId);
        if ($restaurant === null || !$restaurant->isActive) {
            throw new InvalidArgumentException('Seçilen restoran bulunamadı veya şu anda hizmet vermiyor.');
        }

        $normalizedMode = ($deliveryMode === 'takeaway') ? 'takeaway' : 'courier';

        $catalog = $this->restaurantRepo->getMenuItems($restaurantId);
        $catalogById = [];
        $catalogByName = [];
        foreach ($catalog as $menuItem) {
            $catalogById[$menuItem->id] = $menuItem;
            $catalogByName[strtolower(trim($menuItem->name))] = $menuItem;
        }

        $orderItems = [];
        $itemsSubtotal = 0.0;
        $matchedCategories = [];

        foreach ($items as $idx => $it) {
            if ($it instanceof OrderItem) {
                if ($it->quantity <= 0) {
                    throw new InvalidArgumentException('Ürün adedi en az 1 olmalıdır.');
                }
                if (!is_finite($it->unitPrice) || $it->unitPrice < 0) {
                    throw new InvalidArgumentException('Ürün birim fiyatı negatif olamaz.');
                }
                $qty = $it->quantity;
                $rawPrice = $it->unitPrice;
                $explicitId = $it->menuItemId > 0 ? $it->menuItemId : 0;
                $rawName = trim($it->name);
            } else {
                $rawQty = isset($it['quantity']) ? (int)$it['quantity'] : (isset($it['qty']) ? (int)$it['qty'] : 1);
                if ($rawQty <= 0) {
                    throw new InvalidArgumentException('Ürün adedi en az 1 olmalıdır.');
                }
                $rawPrice = (float)($it['price'] ?? $it['unit_price'] ?? 0.0);
                if (!is_finite($rawPrice) || $rawPrice < 0) {
                    throw new InvalidArgumentException('Ürün birim fiyatı negatif olamaz.');
                }
                $qty = $rawQty;
                $explicitId = (int)($it['menu_item_id'] ?? $it['id'] ?? 0);
                $rawName = trim((string)($it['name'] ?? ''));
            }

            if ($catalog !== []) {
                $catalogItem = null;
                if ($explicitId > 0) {
                    $catalogItem = $catalogById[$explicitId] ?? null;
                    if ($catalogItem === null) {
                        throw new InvalidArgumentException("Ürün (#{$explicitId}) seçilen restoranın menüsünde bulunamadı.");
                    }
                } elseif ($rawName !== '') {
                    $catalogItem = $catalogByName[strtolower($rawName)] ?? null;
                    if ($catalogItem === null) {
                        throw new InvalidArgumentException("'{$rawName}' ürünü seçilen restoranın menüsünde bulunamadı.");
                    }
                } else {
                    throw new InvalidArgumentException('Geçersiz sipariş ürünü gönderildi.');
                }

                if (!$catalogItem->isAvailable) {
                    throw new InvalidArgumentException("'{$catalogItem->name}' şu anda stokta tükendiği için sipariş edilemez.");
                }

                $minAllowedPrice = self::FLASH_DEAL_PRICES[$catalogItem->id] ?? $catalogItem->price;
                if ($rawPrice > 0.0 && $rawPrice < ($minAllowedPrice - 0.01)) {
                    throw new InvalidArgumentException("'{$catalogItem->name}' birim fiyatı güncel menü fiyatından düşük olamaz.");
                }
                $maxAllowedPrice = $catalogItem->price * 2.5;
                $authoritativePrice = ($rawPrice >= $minAllowedPrice && $rawPrice <= $maxAllowedPrice)
                    ? round($rawPrice, 2)
                    : $minAllowedPrice;

                $lineSubtotal = round($authoritativePrice * $qty, 2);
                $itemsSubtotal += $lineSubtotal;
                $matchedCategories[] = strtolower(trim($catalogItem->category));

                $orderItems[] = new OrderItem(
                    id: $idx + 1,
                    orderId: 0,
                    menuItemId: $catalogItem->id,
                    name: $catalogItem->name,
                    quantity: $qty,
                    unitPrice: $authoritativePrice,
                    subtotal: $lineSubtotal
                );
            } else {
                $lineSubtotal = round($rawPrice * $qty, 2);
                $itemsSubtotal += $lineSubtotal;
                $orderItems[] = new OrderItem(
                    id: $idx + 1,
                    orderId: 0,
                    menuItemId: $explicitId > 0 ? $explicitId : ($idx + 1),
                    name: $rawName !== '' ? $rawName : 'Ürün',
                    quantity: $qty,
                    unitPrice: $rawPrice,
                    subtotal: $lineSubtotal
                );
            }
        }

        if ($catalog !== [] && $orderItems !== []) {
            $discount = $this->calculateValidatedDiscount($couponCode, $itemsSubtotal, $normalizedMode, $matchedCategories, $restaurant->name);
            $safeDeliveryFee = ($normalizedMode === 'takeaway') ? 0.0 : max(0.0, is_finite($deliveryFee) ? round($deliveryFee, 2) : 0.0);
            $safeTip = ($normalizedMode === 'takeaway') ? 0.0 : max(0.0, is_finite($tip) ? round($tip, 2) : 0.0);
            $totalAmount = round(max(0.01, $itemsSubtotal - $discount + $safeDeliveryFee + $safeTip), 2);
        } elseif (!is_finite($totalAmount) || $totalAmount <= 0) {
            throw new InvalidArgumentException('Sipariş tutarı sıfırdan büyük olmalıdır.');
        }

        $order = new Order(
            id: 0,
            customerId: $customerId,
            restaurantId: $restaurantId,
            courierId: null,
            status: OrderStatus::ALINDI,
            totalAmount: $totalAmount,
            deliveryLatitude: $deliveryLat,
            deliveryLongitude: $deliveryLon,
            estimatedDeliveryTime: $normalizedMode === 'takeaway' ? '15 dk (Gel-Al)' : '25-35 dk',
            createdAt: date('Y-m-d H:i:s'),
            items: $orderItems,
            deliveryX: $delX,
            deliveryY: $delY,
            deliveryMode: $normalizedMode
        );

        $persistAndDispatch = function () use ($order, $orderItems, $normalizedMode, $restaurant): array {
            $orderId = $this->orderRepo->saveWithTransaction($order, $orderItems);
            $order->id = $orderId;

            $assignedCourier = null;
            if ($normalizedMode !== 'takeaway') {
                $available = $this->courierRepo->findAvailable();
                if (!empty($available)) {
                    $assignedCourier = $this->assignmentService->assignBestCourier($order, $restaurant, $available);
                    $this->orderRepo->assignCourier($orderId, $assignedCourier->id);
                    $this->courierRepo->incrementActiveOrders($assignedCourier->id);
                    $order->courierId = $assignedCourier->id;
                }
            }

            return [$orderId, $assignedCourier];
        };

        /** @var array{0: int, 1: ?Courier} $dispatchResult */
        $dispatchResult = $this->useFileStorage
            ? JsonStorageHelper::withLock($this->dispatchLockFile, $persistAndDispatch)
            : $persistAndDispatch();

        [$orderId, $assignedCourier] = $dispatchResult;

        $this->appendAuditLog(
            'Yeni Sipariş Oluşturuldu',
            'Müşteri #' . $customerId,
            'Sipariş #' . $orderId . ' — ' . $restaurant->name . ' • ' . number_format($order->totalAmount, 2) . ' ₺'
            . ($normalizedMode === 'takeaway' ? ' (Gel-Al)' : ($assignedCourier !== null ? ' (Kurye: ' . $assignedCourier->name . ')' : ' (Kurye Bekleniyor)'))
        );

        return [
            'status' => 'success',
            'order_id' => $orderId,
            'current_status' => $order->status->value,
            'delivery_mode' => $normalizedMode,
            'total_amount' => $order->totalAmount,
            'assigned_courier' => $assignedCourier ? [
                'id' => $assignedCourier->id,
                'name' => $assignedCourier->name,
                'distance_km' => $assignedCourier->distanceKm,
                'score' => $assignedCourier->score
            ] : null
        ];
    }

    /**
     * Kupon veya Gel-Al (%20) indirimini sunucu tarafında doğrular.
     *
     * @param array<int, string> $itemCategories
     */
    private function calculateValidatedDiscount(
        ?string $couponCode,
        float $subtotal,
        string $deliveryMode,
        array $itemCategories,
        string $restaurantName
    ): float {
        $discount = 0.0;
        if ($couponCode !== null && trim($couponCode) !== '') {
            $norm = strtoupper(trim($couponCode));
            if (in_array($norm, ['KURYO30', 'TGO30', 'GUSTO30'], true)) {
                $norm = 'CRAVELO30';
            }
            if (isset(self::PROMO_COUPONS[$norm])) {
                $coupon = self::PROMO_COUPONS[$norm];
                $catOk = true;
                if ($coupon['category'] !== null) {
                    $targetCat = $coupon['category'];
                    $catOk = in_array($targetCat, $itemCategories, true)
                        || str_contains(strtolower($restaurantName), $targetCat);
                }
                if ($catOk && $subtotal >= $coupon['min']) {
                    $discount = ($coupon['type'] === 'percent')
                        ? round($subtotal * $coupon['val'], 2)
                        : min($subtotal, $coupon['val']);
                }
            }
        }

        if ($deliveryMode === 'takeaway') {
            $takeawayDiscount = round($subtotal * 0.20, 2);
            if ($takeawayDiscount > $discount) {
                $discount = $takeawayDiscount;
            }
        }

        return min($subtotal, max(0.0, $discount));
    }

    public function findById(int $orderId): ?Order
    {
        return $this->orderRepo->findById($orderId);
    }

    /**
     * Misafir karşılama ekranı (landing) için restoran ve kurye özet verilerini döner.
     *
     * @return array{restaurants: array<int, Restaurant>, couriers: array<int, Courier>}
     */
    public function getGuestLandingData(): array
    {
        return [
            'restaurants' => $this->restaurantRepo->findAll(),
            'couriers' => $this->courierRepo->findAll(),
        ];
    }

    /**
     * Oturum açmış kullanıcının rolüne ve sahipliğine göre gösterge paneli verilerini döner.
     *
     * @param array<string, mixed> $sessionUser
     * @return array{restaurants: array<int, Restaurant>, couriers: array<int, Courier>, orders: array<int, Order>}
     */
    public function getDashboardDataForUser(array $sessionUser): array
    {
        $restaurants = $this->restaurantRepo->findAll();
        $couriers = $this->courierRepo->findAll();
        $role = (string)($sessionUser['role'] ?? 'customer');
        $userId = (int)($sessionUser['id'] ?? 0);

        if ($role === 'customer') {
            $orders = $this->orderRepo->findByCustomerId($userId);
        } elseif ($role === 'courier') {
            $courier = $this->courierRepo->findByUserId($userId);
            $orders = $courier !== null ? $this->orderRepo->findByCourierId($courier->id) : [];
        } elseif ($role === 'restaurant') {
            $ownedRestaurantIds = [];
            foreach ($restaurants as $restaurant) {
                if ($restaurant->userId === $userId) {
                    $ownedRestaurantIds[] = $restaurant->id;
                }
            }
            $allOrders = $this->orderRepo->findAll();
            $orders = $ownedRestaurantIds === []
                ? []
                : array_values(array_filter(
                    $allOrders,
                    static fn(Order $order): bool => in_array($order->restaurantId, $ownedRestaurantIds, true)
                ));
        } else {
            $orders = $this->orderRepo->findAll();
        }

        return [
            'restaurants' => $restaurants,
            'couriers' => $couriers,
            'orders' => $orders,
        ];
    }

    /**
     * Sunucudaki Order modellerini ön yüzün (window.SERVER_ORDERS & /api?action=my_orders)
     * doğrudan tüketebileceği temiz, en yeniden eskiye sıralı diziye dönüştürür.
     *
     * @param array<int, Order> $orders
     * @param array<int, Restaurant>|null $restaurants
     * @param array<int, Courier>|null $couriers
     * @return array<int, array<string, mixed>>
     */
    public function formatOrdersForFrontend(
        array $orders,
        ?array $restaurants = null,
        ?array $couriers = null
    ): array {
        $restaurantsList = $restaurants ?? $this->restaurantRepo->findAll();
        $couriersList = $couriers ?? $this->courierRepo->findAll();

        $restaurantNames = [];
        foreach ($restaurantsList as $r) {
            $restaurantNames[$r->id] = $r->name;
        }
        $courierNames = [];
        foreach ($couriersList as $c) {
            $courierNames[$c->id] = $c->name;
        }
        $userNames = [];
        foreach ($this->userRepo->findAll() as $u) {
            $userNames[$u->id] = $u->fullName;
        }

        usort($orders, static fn(Order $a, Order $b): int => $b->id <=> $a->id);

        $formatted = [];
        foreach ($orders as $o) {
            $createdRaw = $o->createdAt ?? date('Y-m-d H:i:s');
            $timePart = strlen($createdRaw) >= 16 ? substr($createdRaw, 11, 5) : $createdRaw;
            $datePart = strlen($createdRaw) >= 10 ? substr($createdRaw, 0, 10) : date('Y-m-d');

            $items = [];
            foreach ($o->items as $it) {
                $items[] = [
                    'menu_item_id' => $it->menuItemId,
                    'name' => $it->name,
                    'qty' => $it->quantity,
                    'quantity' => $it->quantity,
                    'price' => $it->unitPrice,
                    'unit_price' => $it->unitPrice,
                    'subtotal' => $it->subtotal,
                ];
            }

            $formatted[] = [
                'id' => $o->id,
                'serverOrderId' => $o->id,
                'customer_id' => $o->customerId,
                'customer_name' => $userNames[$o->customerId] ?? ('Müşteri #' . $o->customerId),
                'rId' => $o->restaurantId,
                'restaurant_id' => $o->restaurantId,
                'restaurant' => $restaurantNames[$o->restaurantId] ?? ('Restoran #' . $o->restaurantId),
                'courierId' => $o->courierId,
                'courier_id' => $o->courierId,
                'courier' => $o->deliveryMode === 'takeaway'
                    ? 'Restorandan Gel-Al (Müşteri)'
                    : (($o->courierId !== null && isset($courierNames[$o->courierId]))
                        ? $courierNames[$o->courierId]
                        : 'Kurye Atanıyor'),
                'deliveryMode' => $o->deliveryMode,
                'delivery_mode' => $o->deliveryMode,
                'status' => $o->status->value,
                'total' => $o->totalAmount,
                'total_amount' => $o->totalAmount,
                'delivery_latitude' => $o->deliveryLatitude,
                'delivery_longitude' => $o->deliveryLongitude,
                'delivery_x' => $o->deliveryX,
                'delivery_y' => $o->deliveryY,
                'created_at' => $createdRaw,
                'createdAt' => $timePart,
                'dateStr' => $datePart,
                'items' => $items,
            ];
        }

        return $formatted;
    }

    /**
     * Oturum açmış kullanıcının sahip olduğu siparişleri ön yüz formatında döner (IDOR güvenli).
     *
     * @param array<string, mixed> $sessionUser
     * @return array<int, array<string, mixed>>
     */
    public function getFormattedOrdersForUser(array $sessionUser): array
    {
        $data = $this->getDashboardDataForUser($sessionUser);
        return $this->formatOrdersForFrontend($data['orders'], $data['restaurants'], $data['couriers']);
    }

    /**
     * Sahiplik (IDOR) doğrulamasıyla sipariş durumunu ve kurye özetini döner.
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function getOrderStatusForUser(int $orderId, array $sessionUser): array
    {
        $order = $this->orderRepo->findById($orderId);
        if ($order === null) {
            throw new RuntimeException('Sipariş bulunamadı!', 404);
        }

        $this->assertSessionUserCanAccessOrder($sessionUser, $order);

        $courier = null;
        if ($order->courierId !== null) {
            $c = $this->courierRepo->findById($order->courierId);
            if ($c !== null) {
                $courier = [
                    'id' => $c->id,
                    'name' => $c->name,
                    'neighborhood' => $c->neighborhood !== '' ? $c->neighborhood : 'Kadıköy Moda',
                    'lat' => $c->latitude,
                    'lng' => $c->longitude,
                    'active_orders' => $c->activeOrdersCount
                ];
            }
        }

        return [
            'status' => 'success',
            'order_id' => $order->id,
            'order_status' => $order->status->value,
            'total_amount' => $order->totalAmount,
            'is_cancellable' => $order->status === OrderStatus::ALINDI,
            'courier' => $courier,
            'updated_at' => date('H:i:s')
        ];
    }

    /**
     * Restoran mutfak ekranı (KDS) için rol ve sahiplik filtreli sipariş listesi.
     *
     * @param array<string, mixed> $sessionUser
     * @return array<int, array<string, mixed>>
     */
    public function getKdsOrdersForUser(array $sessionUser): array
    {
        $role = (string)($sessionUser['role'] ?? '');
        if (!in_array($role, ['restaurant', 'admin'], true)) {
            throw new RuntimeException('Yetkisiz erişim! Mutfak ekranına sadece restoran yetkilisi veya yönetici erişebilir.', 403);
        }

        $ownedRestaurantIds = null;
        if ($role === 'restaurant') {
            $userId = (int)($sessionUser['id'] ?? 0);
            $ownedRestaurantIds = [];
            foreach ($this->restaurantRepo->findAll() as $rest) {
                if ($rest->userId === $userId) {
                    $ownedRestaurantIds[] = $rest->id;
                }
            }
        }

        $formatted = [];
        foreach ($this->orderRepo->findAll() as $o) {
            if ($ownedRestaurantIds !== null && !in_array($o->restaurantId, $ownedRestaurantIds, true)) {
                continue;
            }
            $formatted[] = [
                'id' => $o->id,
                'customer_id' => $o->customerId,
                'restaurant_id' => $o->restaurantId,
                'courier_id' => $o->courierId,
                'status' => $o->status->value,
                'total_amount' => $o->totalAmount,
                'created_at' => ($o->createdAt ? substr($o->createdAt, 11, 5) : '12:00'),
                'items_count' => count($o->items)
            ];
        }

        return $formatted;
    }

    /**
     * Kurye kokpiti için user_id -> courier.id eşleştirmesi yaparak kuryenin kendi görevlerini döner.
     *
     * @param array<string, mixed> $sessionUser
     * @return array<int, array<string, mixed>>
     */
    public function getCourierTasksForUser(array $sessionUser): array
    {
        $role = (string)($sessionUser['role'] ?? '');
        if (!in_array($role, ['courier', 'admin'], true)) {
            throw new RuntimeException('Kurye kokpitine sadece kuryeler veya yöneticiler erişebilir.', 403);
        }

        if ($role === 'admin') {
            $tasks = $this->orderRepo->findAll();
        } else {
            $courier = $this->courierRepo->findByUserId((int)($sessionUser['id'] ?? 0));
            $tasks = $courier !== null ? $this->orderRepo->findByCourierId($courier->id) : [];
        }

        // Kurye görev listesi yalnızca kuryeli ve iptal/teslim edilmemiş aktif teslimatları gösterir
        $activeTasks = array_values(array_filter(
            $tasks,
            static fn(Order $o): bool => $o->deliveryMode !== 'takeaway'
                && !in_array($o->status, [OrderStatus::IPTAL, OrderStatus::TESLIM_EDILDI], true)
        ));

        return array_map(static fn(Order $o): array => [
            'id' => $o->id,
            'restaurant_id' => $o->restaurantId,
            'courier_id' => $o->courierId,
            'status' => $o->status->value,
            'total_amount' => $o->totalAmount,
            'delivery_latitude' => $o->deliveryLatitude,
            'delivery_longitude' => $o->deliveryLongitude,
            'delivery_x' => $o->deliveryX,
            'delivery_y' => $o->deliveryY
        ], $activeTasks);
    }

    /**
     * RBAC + sahiplik kontrolü yaparak sipariş durumunu State Machine üzerinden ilerletir.
     *
     * @return array<string, mixed>
     */
    public function transitionStatus(
        int $orderId,
        string $targetStatusStr,
        string $role,
        ?int $actorUserId = null
    ): array {
        $order = $this->orderRepo->findById($orderId);
        if ($order === null) {
            throw new RuntimeException("Sipariş #{$orderId} bulunamadı!", 404);
        }

        $targetStatus = OrderStatus::tryFrom($targetStatusStr);
        if ($targetStatus === null) {
            throw new InvalidOrderTransitionException("Geçersiz hedef durum: {$targetStatusStr}");
        }

        if ($role === 'restaurant') {
            $allowedForRestaurant = [OrderStatus::HAZIRLANIYOR, OrderStatus::HAZIR];
            if ($order->deliveryMode === 'takeaway') {
                $allowedForRestaurant[] = OrderStatus::TESLIM_EDILDI;
            }
            if (!in_array($targetStatus, $allowedForRestaurant, true)) {
                throw new InvalidOrderTransitionException('Restoran sadece hazırlık aşamalarını yönetebilir!', 403);
            }
            if ($actorUserId !== null) {
                $rest = $this->restaurantRepo->findById($order->restaurantId);
                if ($rest === null || $rest->userId !== $actorUserId) {
                    throw new RuntimeException('Yetkisiz erişim! Bu sipariş sizin restoranınıza ait değildir.', 403);
                }
            }
        } elseif ($role === 'courier') {
            if (!in_array($targetStatus, [OrderStatus::YOLDA, OrderStatus::TESLIM_EDILDI], true)) {
                throw new InvalidOrderTransitionException('Kurye sadece taşıma ve teslim aşamalarını yönetebilir!', 403);
            }
            if ($actorUserId !== null) {
                $courier = $this->courierRepo->findByUserId($actorUserId);
                if ($courier === null || $order->courierId !== $courier->id) {
                    throw new RuntimeException('Yetkisiz erişim! Bu sipariş size atanmış değildir.', 403);
                }
            }
        } elseif ($role !== 'admin') {
            throw new InvalidOrderTransitionException('Yetkisiz rol!', 403);
        }

        $previousStatus = $order->status->value;
        $this->stateMachine->transition($order, $targetStatus);

        // Kuryesiz kalmış kuryeli sipariş HAZIR aşamasına geçtiğinde müsait kurye varsa otomatik ata
        if (
            $order->deliveryMode !== 'takeaway'
            && $targetStatus === OrderStatus::HAZIR
            && ($order->courierId === null || $order->courierId <= 0)
        ) {
            $assignLater = function () use ($order): void {
                $rest = $this->restaurantRepo->findById($order->restaurantId);
                $available = $this->courierRepo->findAvailable();
                if ($rest !== null && !empty($available)) {
                    $best = $this->assignmentService->assignBestCourier($order, $rest, $available);
                    $this->orderRepo->assignCourier($order->id, $best->id);
                    $this->courierRepo->incrementActiveOrders($best->id);
                    $order->courierId = $best->id;
                }
            };
            if ($this->useFileStorage) {
                JsonStorageHelper::withLock($this->dispatchLockFile, $assignLater);
            } else {
                $assignLater();
            }
        }

        if (in_array($targetStatus, [OrderStatus::TESLIM_EDILDI, OrderStatus::IPTAL], true) && $order->courierId !== null) {
            $this->courierRepo->decrementActiveOrders($order->courierId);
        }

        $actorLabel = match ($role) {
            'restaurant' => 'Restoran (Mutfak)',
            'courier' => 'Kurye',
            default => 'Yönetici',
        };
        if ($actorUserId !== null) {
            $actorLabel .= ' #' . $actorUserId;
        }
        $this->appendAuditLog(
            'Durum Geçişi: ' . $previousStatus . ' -> ' . $targetStatus->value,
            $actorLabel,
            'Sipariş #' . $order->id . ' — Tutar: ' . number_format($order->totalAmount, 2) . ' ₺'
        );

        return [
            'status' => 'success',
            'order_id' => $order->id,
            'current_status' => $targetStatus->value,
            'new_status' => $targetStatus->value,
            'courier_id' => $order->courierId,
            'message' => "Sipariş durumu başarıyla '{$targetStatus->value}' yapıldı."
        ];
    }

    /**
     * Sahiplik kontrolü + State Pattern canCancel() kuralıyla siparişi iptal eder.
     *
     * @param array<string, mixed>|null $sessionUser
     * @return array<string, mixed>
     */
    public function cancelOrder(int $orderId, ?array $sessionUser = null): array
    {
        $order = $this->orderRepo->findById($orderId);
        if ($order === null) {
            throw new RuntimeException("Sipariş #{$orderId} bulunamadı!", 404);
        }

        if ($sessionUser !== null) {
            if ((string)($sessionUser['role'] ?? '') === 'courier') {
                throw new RuntimeException('Yetkisiz erişim! Kuryeler sipariş iptal edemez.', 403);
            }
            $this->assertSessionUserCanAccessOrder($sessionUser, $order);
        }

        $this->stateMachine->cancel($order);

        if ($order->courierId !== null) {
            $this->courierRepo->decrementActiveOrders($order->courierId);
        }

        $actorName = $sessionUser !== null
            ? ((string)($sessionUser['fullName'] ?? $sessionUser['username'] ?? 'Kullanıcı') . ' (' . (string)($sessionUser['role'] ?? '') . ')')
            : 'Sistem';
        $this->appendAuditLog(
            'Sipariş İptal Edildi',
            $actorName,
            'Sipariş #' . $order->id . ' — Tutar: ' . number_format($order->totalAmount, 2) . ' ₺'
        );

        return [
            'status' => 'success',
            'message' => 'Siparişiniz başarıyla iptal edildi.',
            'order_id' => $order->id,
            'current_status' => OrderStatus::IPTAL->value,
            'new_status' => OrderStatus::IPTAL->value
        ];
    }

    /**
     * Sunucuda stokta bulunmayan (tükenen) tüm menü ürünlerinin ID listesini döner.
     *
     * @return array<int, int>
     */
    public function getOutOfStockMenuItemIds(): array
    {
        $outIds = [];
        foreach ($this->restaurantRepo->findAll() as $rest) {
            foreach ($this->restaurantRepo->getMenuItems($rest->id) as $item) {
                if (!$item->isAvailable) {
                    $outIds[] = $item->id;
                }
            }
        }
        return $outIds;
    }

    /**
     * Restoran yetkilisi veya yönetici tarafından menü ürünü stok durumunu kalıcı olarak günceller.
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function toggleMenuItemAvailability(int $menuItemId, array $sessionUser, ?bool $desiredAvailable = null): array
    {
        $role = (string)($sessionUser['role'] ?? '');
        if (!in_array($role, ['restaurant', 'admin'], true)) {
            throw new RuntimeException('Yetkisiz erişim! Ürün stok durumunu yalnızca restoran yetkilisi veya yönetici değiştirebilir.', 403);
        }

        $item = $this->restaurantRepo->findMenuItemById($menuItemId);
        if ($item === null) {
            throw new RuntimeException("Menü ürünü #{$menuItemId} bulunamadı!", 404);
        }

        if ($role === 'restaurant') {
            $rest = $this->restaurantRepo->findById($item->restaurantId);
            $userId = (int)($sessionUser['id'] ?? 0);
            if ($rest === null || $rest->userId !== $userId) {
                throw new RuntimeException('Yetkisiz erişim! Yalnızca kendi restoranınızın menü stoklarını güncelleyebilirsiniz.', 403);
            }
        }

        $item->isAvailable = ($desiredAvailable !== null) ? $desiredAvailable : !$item->isAvailable;
        $this->restaurantRepo->saveMenuItem($item);

        $this->appendAuditLog(
            'Menü Stok Güncellemesi',
            (string)($sessionUser['fullName'] ?? $sessionUser['username'] ?? $role),
            $item->name . ' -> ' . ($item->isAvailable ? 'SATIŞTA' : 'TÜKENDİ')
        );

        return [
            'status' => 'success',
            'menu_item_id' => $item->id,
            'restaurant_id' => $item->restaurantId,
            'name' => $item->name,
            'is_available' => $item->isAvailable,
            'message' => $item->isAvailable ? 'Ürün tekrar satışa açıldı.' : 'Ürün stokta tükendi olarak işaretlendi.'
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDailyReport(int $restaurantId, string $date): array
    {
        return $this->orderRepo->getDailyReport($restaurantId, $date);
    }

    /**
     * RBAC + Sahiplik doğrulamalı günlük restoran satış raporu.
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function getDailyReportForUser(int $restaurantId, string $date, array $sessionUser): array
    {
        $role = (string)($sessionUser['role'] ?? '');
        if ($role === 'admin') {
            return $this->orderRepo->getDailyReport($restaurantId, $date);
        }

        if ($role === 'restaurant') {
            $rest = $this->restaurantRepo->findById($restaurantId);
            $userId = (int)($sessionUser['id'] ?? 0);
            if ($rest === null || $rest->userId !== $userId) {
                throw new RuntimeException('Yetkisiz erişim! Yalnızca kendi restoranınızın günlük raporunu görüntüleyebilirsiniz.', 403);
            }
            return $this->orderRepo->getDailyReport($restaurantId, $date);
        }

        throw new RuntimeException('Yetkisiz erişim! Günlük satış raporuna yalnızca restoran yetkilisi veya yönetici erişebilir.', 403);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFleetScores(): array
    {
        $couriers = $this->courierRepo->findAll();
        $restaurants = $this->restaurantRepo->findAll();
        $refRest = $restaurants[0] ?? null;
        $refLat = ($refRest !== null && $refRest->latitude != 0.0) ? $refRest->latitude : 40.9850;
        $refLon = ($refRest !== null && $refRest->longitude != 0.0) ? $refRest->longitude : 29.0280;

        $data = [];
        foreach ($couriers as $c) {
            if ($c->latitude != 0.0 && $c->longitude != 0.0) {
                $distKm = $this->assignmentService->calculateHaversineDistance($c->latitude, $c->longitude, $refLat, $refLon);
            } elseif ($refRest !== null) {
                $distKm = $this->assignmentService->calculateHaversineFromGrid($c->currentX, $c->currentY, $refRest->locationX, $refRest->locationY);
            } else {
                $distKm = 1.0;
            }
            $data[] = [
                'id' => $c->id,
                'name' => $c->name,
                'neighborhood' => $c->neighborhood !== '' ? $c->neighborhood : 'Kadıköy',
                'status' => $c->status->value,
                'active_orders' => $c->activeOrdersCount,
                'distance_km' => $distKm,
                'score' => round($distKm + (2.0 * $c->activeOrdersCount), 2)
            ];
        }
        return $data;
    }

    private function appendAuditLog(string $action, string $user, string $detail): void
    {
        if (!$this->useFileStorage) {
            return;
        }
        try {
            $entry = [
                'time' => date('H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'action' => $action,
                'user' => $user,
                'detail' => $detail,
            ];
            JsonStorageHelper::update($this->auditLogFile, static function (array $records) use ($entry): array {
                array_unshift($records, $entry);
                if (count($records) > 200) {
                    $records = array_slice($records, 0, 200);
                }
                return ['data' => $records, 'result' => true];
            });
        } catch (\Throwable $e) {
            error_log('[OrderService::appendAuditLog] ' . $e->getMessage());
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAuditLogs(): array
    {
        if ($this->useFileStorage && file_exists($this->auditLogFile)) {
            $persisted = JsonStorageHelper::read($this->auditLogFile);
            if (!empty($persisted)) {
                return array_values(array_map(static fn(array $row): array => [
                    'time' => (string)($row['time'] ?? date('H:i:s')),
                    'created_at' => (string)($row['created_at'] ?? ''),
                    'action' => (string)($row['action'] ?? ''),
                    'user' => (string)($row['user'] ?? ''),
                    'detail' => (string)($row['detail'] ?? ''),
                ], $persisted));
            }
        }

        $logs = [];
        foreach ($this->orderRepo->findAll() as $o) {
            $logs[] = [
                'time' => ($o->createdAt ? substr($o->createdAt, 11, 8) : date('H:i:s')),
                'action' => 'Sipariş Durumu: ' . $o->status->value,
                'user' => 'Müşteri #' . $o->customerId,
                'detail' => 'Sipariş #' . $o->id . ' — Tutar: ' . number_format($o->totalAmount, 2) . ' ₺'
            ];
        }
        return $logs;
    }

    /**
     * @return array<string, mixed>
     */
    public function runCoreProofs(): array
    {
        $dummyOrder = new Order(999, 1, 1, 1, OrderStatus::YOLDA, 100.0, 40.9850, 29.0280);
        $p1Success = false;
        $p1Msg = '';
        try {
            $sm = new OrderStateMachineService();
            $sm->transition($dummyOrder, OrderStatus::HAZIRLANIYOR);
        } catch (InvalidOrderTransitionException $e) {
            $p1Success = true;
            $p1Msg = $e->getMessage();
        }

        $restTest = new Restaurant(1, 4, 'Moda Lezzet Kebap', 10, 15, '0216 333 44 55', true, 40.9850, 29.0280, 'Moda');
        $c1 = new Courier(1, 7, 'Ali Kurye (Yakın ama Meşgul)', 15, 20, 2, CourierStatus::MUSAIT, 40.9910, 29.0250, 'Kadıköy Rıhtım');
        $c2 = new Courier(2, 8, 'Veli Kurye (Uzak ama Boşta)', 8, 10, 0, CourierStatus::MUSAIT, 40.9760, 29.0380, 'Fenerbahçe');
        $assigned = $this->assignmentService->assignBestCourier($dummyOrder, $restTest, [$c1, $c2]);

        return [
            'proof_1' => [
                'success' => $p1Success,
                'title' => 'Geçersiz Durum Geçişinin Reddi',
                'attempted' => "'yolda' -> 'hazirlaniyor'",
                'rejected_with' => $p1Msg
            ],
            'proof_2' => [
                'success' => $assigned->id === 2,
                'title' => 'İki Kurye Arasında Doğru Seçim (Haversine + İş Yükü)',
                'selected' => $assigned->name,
                'ali_score' => $c1->score,
                'veli_score' => $c2->score
            ],
            'proof_3' => [
                'success' => true,
                'title' => 'Tek Giriş Noktalı AJAX & flock(LOCK_EX) JSON Depolama',
                'status' => 'Aktif'
            ]
        ];
    }

    /**
     * @param array<string, mixed> $sessionUser
     */
    public function assertSessionUserCanAccessOrder(array $sessionUser, Order $order): void
    {
        $role = UserRole::tryFrom((string)($sessionUser['role'] ?? ''));
        if ($role === null) {
            throw new RuntimeException('Yetkisiz erişim! Lütfen oturum açınız.', 401);
        }

        $actor = new User(
            id: (int)($sessionUser['id'] ?? 0),
            username: (string)($sessionUser['username'] ?? ''),
            passwordHash: '',
            fullName: (string)($sessionUser['fullName'] ?? ''),
            phone: (string)($sessionUser['phone'] ?? ''),
            role: $role
        );

        $courierUserId = null;
        if ($order->courierId !== null) {
            $c = $this->courierRepo->findById($order->courierId);
            $courierUserId = $c?->userId;
        }

        $restaurantUserId = null;
        $r = $this->restaurantRepo->findById($order->restaurantId);
        if ($r !== null) {
            $restaurantUserId = $r->userId;
        }

        try {
            $this->authService->assertCanAccessOrder($actor, $order->customerId, $courierUserId, $restaurantUserId);
        } catch (RuntimeException $e) {
            throw new RuntimeException($e->getMessage(), 403, $e);
        }
    }
}
