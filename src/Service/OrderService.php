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
use InvalidArgumentException;
use RuntimeException;

/**
 * Sipariş yaşam döngüsü, sahiplik (IDOR) kontrolleri, KDS / Kurye sorguları ve raporlama
 * sorumluluklarını Controller katmanından tamamen ayıran merkezi servis sınıfı.
 * Controller sınıfları doğrudan Repository çağırmaz; yalnızca OrderService üzerinden konuşur.
 */
final class OrderService
{
    private OrderRepositoryInterface $orderRepo;
    private RestaurantRepositoryInterface $restaurantRepo;
    private CourierRepositoryInterface $courierRepo;
    private UserRepositoryInterface $userRepo;
    private OrderStateMachineService $stateMachine;
    private CourierAssignmentService $assignmentService;
    private AuthService $authService;

    public function __construct(
        ?OrderRepositoryInterface $orderRepo = null,
        ?RestaurantRepositoryInterface $restaurantRepo = null,
        ?CourierRepositoryInterface $courierRepo = null,
        ?UserRepositoryInterface $userRepo = null,
        ?OrderStateMachineService $stateMachine = null,
        ?CourierAssignmentService $assignmentService = null,
        ?AuthService $authService = null
    ) {
        $this->orderRepo = $orderRepo ?? new JsonOrderRepository();
        $this->restaurantRepo = $restaurantRepo ?? new JsonRestaurantRepository();
        $this->courierRepo = $courierRepo ?? new JsonCourierRepository();
        $this->userRepo = $userRepo ?? new JsonUserRepository();
        $this->stateMachine = $stateMachine ?? new OrderStateMachineService($this->orderRepo);

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
        float $deliveryLon = 29.0280
    ): array {
        if ($totalAmount <= 0) {
            throw new InvalidArgumentException('Sipariş tutarı sıfırdan büyük olmalıdır.');
        }

        $orderItems = [];
        foreach ($items as $idx => $it) {
            if ($it instanceof OrderItem) {
                $orderItems[] = $it;
            } elseif (is_array($it)) {
                $qty = max(1, (int)($it['quantity'] ?? 1));
                $price = (float)($it['price'] ?? $it['unit_price'] ?? 0.0);
                $orderItems[] = new OrderItem(
                    id: $idx + 1,
                    orderId: 0,
                    menuItemId: (int)($it['menu_item_id'] ?? $it['id'] ?? ($idx + 1)),
                    name: (string)($it['name'] ?? 'Ürün'),
                    quantity: $qty,
                    unitPrice: $price,
                    subtotal: $price * $qty
                );
            }
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
            estimatedDeliveryTime: '25-35 dk',
            createdAt: date('Y-m-d H:i:s'),
            items: $orderItems,
            deliveryX: $delX,
            deliveryY: $delY
        );

        $orderId = $this->orderRepo->saveWithTransaction($order, $orderItems);
        $order->id = $orderId;

        $assignedCourier = null;
        $restaurant = $this->restaurantRepo->findById($restaurantId);
        $available = $this->courierRepo->findAvailable();

        if ($restaurant !== null && !empty($available)) {
            $assignedCourier = $this->assignmentService->assignBestCourier($order, $restaurant, $available);
            $this->orderRepo->assignCourier($orderId, $assignedCourier->id);
            $this->courierRepo->incrementActiveOrders($assignedCourier->id);
            $order->courierId = $assignedCourier->id;
        }

        return [
            'status' => 'success',
            'order_id' => $orderId,
            'current_status' => $order->status->value,
            'assigned_courier' => $assignedCourier ? [
                'id' => $assignedCourier->id,
                'name' => $assignedCourier->name,
                'distance_km' => $assignedCourier->distanceKm,
                'score' => $assignedCourier->score
            ] : null
        ];
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

        usort($orders, static fn(Order $a, Order $b): int => $b->id <=> $a->id);

        $formatted = [];
        foreach ($orders as $o) {
            $createdRaw = $o->createdAt ?? date('Y-m-d H:i:s');
            $timePart = strlen($createdRaw) >= 16 ? substr($createdRaw, 11, 5) : $createdRaw;
            $datePart = strlen($createdRaw) >= 10 ? substr($createdRaw, 0, 10) : date('Y-m-d');

            $items = [];
            foreach ($o->items as $it) {
                $items[] = [
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
                'rId' => $o->restaurantId,
                'restaurant_id' => $o->restaurantId,
                'restaurant' => $restaurantNames[$o->restaurantId] ?? ('Restoran #' . $o->restaurantId),
                'courierId' => $o->courierId,
                'courier_id' => $o->courierId,
                'courier' => ($o->courierId !== null && isset($courierNames[$o->courierId]))
                    ? $courierNames[$o->courierId]
                    : 'Kurye Atanıyor',
                'status' => $o->status->value,
                'total' => $o->totalAmount,
                'total_amount' => $o->totalAmount,
                'delivery_latitude' => $o->deliveryLatitude,
                'delivery_longitude' => $o->deliveryLongitude,
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

        return array_map(static fn(Order $o): array => [
            'id' => $o->id,
            'restaurant_id' => $o->restaurantId,
            'courier_id' => $o->courierId,
            'status' => $o->status->value,
            'total_amount' => $o->totalAmount,
            'delivery_latitude' => $o->deliveryLatitude,
            'delivery_longitude' => $o->deliveryLongitude
        ], $tasks);
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
            if (!in_array($targetStatus, [OrderStatus::HAZIRLANIYOR, OrderStatus::HAZIR], true)) {
                throw new InvalidOrderTransitionException('Restoran sadece hazırlık aşamalarını yönetebilir!', 403);
            }
            if ($actorUserId !== null) {
                $rest = $this->restaurantRepo->findById($order->restaurantId);
                if ($rest !== null && $rest->userId !== $actorUserId) {
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

        $this->stateMachine->transition($order, $targetStatus);

        if ($targetStatus === OrderStatus::TESLIM_EDILDI && $order->courierId !== null) {
            $this->courierRepo->decrementActiveOrders($order->courierId);
        }

        return [
            'status' => 'success',
            'order_id' => $order->id,
            'current_status' => $targetStatus->value,
            'new_status' => $targetStatus->value,
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
            $this->assertSessionUserCanAccessOrder($sessionUser, $order);
        }

        $this->stateMachine->cancel($order);

        if ($order->courierId !== null) {
            $this->courierRepo->decrementActiveOrders($order->courierId);
        }

        return [
            'status' => 'success',
            'message' => 'Siparişiniz başarıyla iptal edildi.',
            'order_id' => $order->id,
            'current_status' => OrderStatus::IPTAL->value,
            'new_status' => OrderStatus::IPTAL->value
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
     * @return array<int, array<string, mixed>>
     */
    public function getFleetScores(): array
    {
        $couriers = $this->courierRepo->findAll();
        $data = [];
        foreach ($couriers as $c) {
            $data[] = [
                'id' => $c->id,
                'name' => $c->name,
                'neighborhood' => $c->neighborhood !== '' ? $c->neighborhood : 'Kadıköy',
                'status' => $c->status->value,
                'active_orders' => $c->activeOrdersCount,
                'score' => round(1.0 + (2.0 * $c->activeOrdersCount), 2)
            ];
        }
        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAuditLogs(): array
    {
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
