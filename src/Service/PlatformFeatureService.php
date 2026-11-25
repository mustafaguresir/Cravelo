<?php
declare(strict_types=1);

namespace App\Service;

use App\Enum\CourierStatus;
use App\Enum\OrderStatus;
use App\Model\MenuItem;
use App\Repository\CourierRepositoryInterface;
use App\Repository\Json\JsonCourierRepository;
use App\Repository\Json\JsonOrderRepository;
use App\Repository\Json\JsonRestaurantRepository;
use App\Repository\Json\JsonUserRepository;
use App\Repository\OrderRepositoryInterface;
use App\Repository\RestaurantRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Storage\JsonStorageHelper;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cravelo — Genişletilmiş Platform Özellik Servisi
 *
 * Sorumluluklar:
 * - Kurye canlı konum ve vardiya (müsait/meşgul/offline) kalıcılığı
 * - KDS tam menü CRUD (ekle/güncelle) ve restoran aktif/pasif yönetimi
 * - Yönetici kurye yeniden atama ve çok boyutlu platform analitiği
 * - Müşteri profili, adres defteri ve Cravelo Puan sunucu kalıcılığı
 * - Gerçek değerlendirme/yorum sistemi (storage/reviews.json)
 * - Çift yönlü IDOR korumalı müşteri <-> kurye mesajlaşması (storage/messages.json)
 * - Tek kaynaklı sunucu katalog enjeksiyonu (Single Source of Truth)
 */
final class PlatformFeatureService
{
    private OrderRepositoryInterface $orderRepo;
    private RestaurantRepositoryInterface $restaurantRepo;
    private CourierRepositoryInterface $courierRepo;
    private UserRepositoryInterface $userRepo;
    private OrderService $orderService;
    private string $storageDir;
    private string $reviewsFile;
    private string $messagesFile;
    private string $profilesFile;
    private string $auditLogFile;
    private string $dispatchLockFile;

    public function __construct(
        ?OrderRepositoryInterface $orderRepo = null,
        ?RestaurantRepositoryInterface $restaurantRepo = null,
        ?CourierRepositoryInterface $courierRepo = null,
        ?UserRepositoryInterface $userRepo = null,
        ?OrderService $orderService = null,
        ?string $storageDir = null
    ) {
        $this->storageDir = $storageDir ?? (dirname(__DIR__, 2) . '/storage');
        $this->orderRepo = $orderRepo ?? new JsonOrderRepository(
            $this->storageDir . '/orders.json',
            $this->storageDir . '/restaurants.json',
            $this->storageDir . '/users.json',
            $this->storageDir . '/couriers.json'
        );
        $this->restaurantRepo = $restaurantRepo ?? new JsonRestaurantRepository(
            $this->storageDir . '/restaurants.json',
            $this->storageDir . '/menu_items.json'
        );
        $this->courierRepo = $courierRepo ?? new JsonCourierRepository($this->storageDir . '/couriers.json');
        $this->userRepo = $userRepo ?? new JsonUserRepository($this->storageDir . '/users.json');
        $this->orderService = $orderService ?? new OrderService(
            orderRepo: $this->orderRepo,
            restaurantRepo: $this->restaurantRepo,
            courierRepo: $this->courierRepo,
            userRepo: $this->userRepo,
            auditLogFile: $this->storageDir . '/audit_logs.json',
            dispatchLockFile: $this->storageDir . '/dispatch.lock'
        );
        $this->reviewsFile = $this->storageDir . '/reviews.json';
        $this->messagesFile = $this->storageDir . '/messages.json';
        $this->profilesFile = $this->storageDir . '/user_profiles.json';
        $this->auditLogFile = $this->storageDir . '/audit_logs.json';
        $this->dispatchLockFile = $this->storageDir . '/dispatch.lock';
    }

    /**
     * Tek kaynaklı (Single Source of Truth) restoran, menü, kurye ve dinamik değerlendirme kataloğu.
     *
     * @return array<string, mixed>
     */
    public function getServerCatalogPayload(): array
    {
        $reviewSummary = $this->getReviewSummaryByRestaurant();
        $restaurants = [];
        $menuItems = [];

        foreach ($this->restaurantRepo->findAll() as $r) {
            $rev = $reviewSummary[$r->id] ?? ['avg' => null, 'count' => 0];
            $restaurants[] = [
                'id' => $r->id,
                'user_id' => $r->userId,
                'name' => $r->name,
                'x' => $r->locationX,
                'y' => $r->locationY,
                'lat' => $r->latitude,
                'lon' => $r->longitude,
                'neighborhood' => $r->neighborhood,
                'phone' => $r->phone,
                'is_active' => $r->isActive,
                'rating' => $rev['avg'] !== null ? number_format($rev['avg'], 1) : null,
                'review_count' => $rev['count'],
            ];

            foreach ($this->restaurantRepo->getMenuItems($r->id) as $m) {
                $menuItems[] = [
                    'id' => $m->id,
                    'rId' => $m->restaurantId,
                    'restaurant_id' => $m->restaurantId,
                    'name' => $m->name,
                    'desc' => $m->description,
                    'price' => $m->price,
                    'category' => $m->category,
                    'available' => $m->isAvailable,
                    'img' => $m->image,
                ];
            }
        }

        $couriers = [];
        foreach ($this->courierRepo->findAll() as $c) {
            $couriers[] = [
                'id' => $c->id,
                'user_id' => $c->userId,
                'name' => $c->name,
                'x' => $c->currentX,
                'y' => $c->currentY,
                'lat' => $c->latitude,
                'lon' => $c->longitude,
                'neighborhood' => $c->neighborhood,
                'active' => $c->activeOrdersCount,
                'status' => $c->status->value,
            ];
        }

        return [
            'restaurants' => $restaurants,
            'menu_items' => $menuItems,
            'couriers' => $couriers,
            'reviews' => $this->getReviews(null)['reviews'],
        ];
    }

    /**
     * Kurye canlı konum güncelleme (storage/couriers.json kalıcı kayıt).
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function updateCourierLocation(
        array $sessionUser,
        int $courierId,
        int $x,
        int $y,
        float $lat,
        float $lon,
        string $neighborhood
    ): array {
        $courier = $this->resolveAuthorizedCourier($sessionUser, $courierId);

        if (!is_finite($lat) || !is_finite($lon) || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            throw new InvalidArgumentException('Geçersiz coğrafi koordinat gönderildi.');
        }

        $courier->currentX = max(0, min(40, $x));
        $courier->currentY = max(0, min(40, $y));
        $courier->latitude = round($lat, 6);
        $courier->longitude = round($lon, 6);
        $cleanNb = trim($neighborhood);
        if ($cleanNb !== '') {
            $courier->neighborhood = $cleanNb;
        }
        $this->courierRepo->save($courier);

        $this->appendAudit(
            'Kurye Konumu Güncellendi',
            (string)($sessionUser['fullName'] ?? $courier->name),
            $courier->name . ' -> ' . ($courier->neighborhood !== '' ? $courier->neighborhood : 'Kadıköy')
        );

        return [
            'status' => 'success',
            'message' => 'Kurye konumu sunucuda güncellendi.',
            'courier' => $courier->toArray(),
        ];
    }

    /**
     * Kurye vardiya / müsaitlik durumu güncelleme (musait / mesgul / offline).
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function updateCourierStatus(array $sessionUser, int $courierId, string $statusStr): array
    {
        $courier = $this->resolveAuthorizedCourier($sessionUser, $courierId);
        $newStatus = CourierStatus::tryFrom(strtolower(trim($statusStr)));
        if ($newStatus === null) {
            throw new InvalidArgumentException("Geçersiz kurye durumu: {$statusStr}");
        }

        $courier->status = $newStatus;
        $this->courierRepo->save($courier);

        $label = match ($newStatus) {
            CourierStatus::MUSAIT => 'MÜSAİT',
            CourierStatus::MESGUL => 'MEŞGUL / MOLA',
            CourierStatus::OFFLINE => 'ÇEVRİMDIŞI',
        };

        $this->appendAudit(
            'Kurye Vardiya Durumu: ' . $label,
            (string)($sessionUser['fullName'] ?? $courier->name),
            $courier->name . ' durumu ' . $label . ' olarak kaydedildi'
        );

        return [
            'status' => 'success',
            'message' => "Vardiya durumunuz '{$label}' olarak kaydedildi.",
            'courier' => $courier->toArray(),
        ];
    }

    /**
     * KDS & Yönetici tam menü ürünü ekleme / güncelleme (CRUD).
     *
     * @param array<string, mixed> $sessionUser
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function saveMenuItem(array $sessionUser, array $payload): array
    {
        $role = (string)($sessionUser['role'] ?? '');
        if (!in_array($role, ['restaurant', 'admin'], true)) {
            throw new RuntimeException('Yetkisiz erişim! Menü düzenleme yetkisi yalnızca restoran veya yöneticiye aittir.', 403);
        }

        $itemId = (int)($payload['id'] ?? $payload['menu_item_id'] ?? 0);
        $restaurantId = (int)($payload['restaurant_id'] ?? $payload['rId'] ?? 0);
        $name = trim((string)($payload['name'] ?? ''));
        $description = trim((string)($payload['description'] ?? $payload['desc'] ?? ''));
        $price = (float)($payload['price'] ?? 0.0);
        $category = trim((string)($payload['category'] ?? 'Ana Yemek'));
        $image = isset($payload['image']) && is_string($payload['image']) && trim($payload['image']) !== ''
            ? trim($payload['image'])
            : null;
        $isAvailable = array_key_exists('is_available', $payload) ? (bool)$payload['is_available'] : true;

        if ($name === '' || strlen($name) < 2) {
            throw new InvalidArgumentException('Ürün adı en az 2 karakter olmalıdır.');
        }
        if (!is_finite($price) || $price <= 0 || $price > 50000) {
            throw new InvalidArgumentException('Ürün fiyatı 0 ile 50.000 ₺ arasında geçerli bir tutar olmalıdır.');
        }

        if ($itemId > 0) {
            $existing = $this->restaurantRepo->findMenuItemById($itemId);
            if ($existing === null) {
                throw new RuntimeException("Menü ürünü #{$itemId} bulunamadı.", 404);
            }
            $restaurantId = $existing->restaurantId;
            if ($image === null) {
                $image = $existing->image;
            }
        }

        if ($restaurantId <= 0) {
            throw new InvalidArgumentException('Geçerli bir restoran kimliği (restaurant_id) seçilmelidir.');
        }

        $rest = $this->restaurantRepo->findById($restaurantId);
        if ($rest === null) {
            throw new RuntimeException("Restoran #{$restaurantId} bulunamadı.", 404);
        }

        if ($role === 'restaurant' && $rest->userId !== (int)($sessionUser['id'] ?? 0)) {
            throw new RuntimeException('Yetkisiz erişim! Yalnızca kendi restoranınızın menüsünü düzenleyebilirsiniz.', 403);
        }

        $item = new MenuItem(
            id: $itemId,
            restaurantId: $restaurantId,
            name: $name,
            description: $description !== '' ? $description : 'Şefin özenle hazırladığı özel lezzet',
            price: round($price, 2),
            category: $category !== '' ? $category : 'Ana Yemek',
            isAvailable: $isAvailable,
            image: $image
        );

        $savedId = $this->restaurantRepo->saveMenuItem($item);
        $item->id = $savedId;

        $this->appendAudit(
            $itemId > 0 ? 'Menü Ürünü Güncellendi' : 'Yeni Menü Ürünü Eklendi',
            (string)($sessionUser['fullName'] ?? $role),
            $rest->name . ' — ' . $item->name . ' (' . number_format($item->price, 2) . ' ₺)'
        );

        return [
            'status' => 'success',
            'message' => $itemId > 0 ? 'Menü ürünü başarıyla güncellendi.' : 'Yeni ürün menüye eklendi.',
            'item' => [
                'id' => $item->id,
                'rId' => $item->restaurantId,
                'restaurant_id' => $item->restaurantId,
                'name' => $item->name,
                'desc' => $item->description,
                'price' => $item->price,
                'category' => $item->category,
                'available' => $item->isAvailable,
                'img' => $item->image,
            ],
        ];
    }

    /**
     * Restoran şube aktif/pasif durumunu kalıcı olarak değiştirir (KDS veya Yönetici).
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function toggleRestaurantActive(array $sessionUser, int $restaurantId, ?bool $desiredActive = null): array
    {
        $role = (string)($sessionUser['role'] ?? '');
        if (!in_array($role, ['restaurant', 'admin'], true)) {
            throw new RuntimeException('Yetkisiz erişim! Restoran durumunu yalnızca restoran sahibi veya yönetici değiştirebilir.', 403);
        }

        $rest = $this->restaurantRepo->findById($restaurantId);
        if ($rest === null) {
            throw new RuntimeException("Restoran #{$restaurantId} bulunamadı.", 404);
        }

        if ($role === 'restaurant' && $rest->userId !== (int)($sessionUser['id'] ?? 0)) {
            throw new RuntimeException('Yetkisiz erişim! Yalnızca kendi restoranınızı açıp kapatabilirsiniz.', 403);
        }

        $rest->isActive = ($desiredActive !== null) ? $desiredActive : !$rest->isActive;
        $this->restaurantRepo->save($rest);

        $this->appendAudit(
            'Restoran Durumu: ' . ($rest->isActive ? 'AÇIK' : 'KAPALI'),
            (string)($sessionUser['fullName'] ?? $role),
            $rest->name . ' sipariş alımına ' . ($rest->isActive ? 'açıldı' : 'kapatıldı')
        );

        return [
            'status' => 'success',
            'restaurant_id' => $rest->id,
            'name' => $rest->name,
            'is_active' => $rest->isActive,
            'message' => $rest->isActive ? "{$rest->name} sipariş alımına açıldı." : "{$rest->name} geçici olarak kapatıldı.",
        ];
    }

    /**
     * Yönetici (Admin) manuel kurye yeniden atama işlemi (iş yükü atomik dengelenir).
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function reassignOrderCourier(array $sessionUser, int $orderId, int $newCourierId): array
    {
        if ((string)($sessionUser['role'] ?? '') !== 'admin') {
            throw new RuntimeException('Yetkisiz erişim! Manuel kurye ataması yalnızca yönetici tarafından yapılabilir.', 403);
        }

        $exec = function () use ($orderId, $newCourierId, $sessionUser): array {
            $order = $this->orderRepo->findById($orderId);
            if ($order === null) {
                throw new RuntimeException("Sipariş #{$orderId} bulunamadı.", 404);
            }
            if ($order->deliveryMode === 'takeaway') {
                throw new InvalidArgumentException('Gel-Al siparişlerine kurye atanamaz.');
            }
            if (in_array($order->status, [OrderStatus::TESLIM_EDILDI, OrderStatus::IPTAL], true)) {
                throw new InvalidArgumentException('Tamamlanmış veya iptal edilmiş siparişte kurye değiştirilemez.');
            }

            $newCourier = $this->courierRepo->findById($newCourierId);
            if ($newCourier === null) {
                throw new RuntimeException("Kurye #{$newCourierId} bulunamadı.", 404);
            }

            $oldCourierId = $order->courierId;
            if ($oldCourierId !== null && $oldCourierId !== $newCourier->id) {
                $this->courierRepo->decrementActiveOrders($oldCourierId);
            }
            if ($oldCourierId !== $newCourier->id) {
                $this->orderRepo->assignCourier($order->id, $newCourier->id);
                $this->courierRepo->incrementActiveOrders($newCourier->id);
            }

            $this->appendAudit(
                'Yönetici Kurye Yeniden Ataması',
                (string)($sessionUser['fullName'] ?? 'Yönetici'),
                "Sipariş #{$order->id} -> {$newCourier->name}"
            );

            return [
                'status' => 'success',
                'order_id' => $order->id,
                'courier_id' => $newCourier->id,
                'courier_name' => $newCourier->name,
                'message' => "Sipariş #{$order->id} kuryesi '{$newCourier->name}' olarak güncellendi.",
            ];
        };

        return ($this->orderRepo instanceof JsonOrderRepository)
            ? JsonStorageHelper::withLock($this->dispatchLockFile, $exec)
            : $exec();
    }

    /**
     * Yönetici çok boyutlu platform analitiği (restoran ciro, durum dağılımı, mod kırılımı, filo).
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function getAdminAnalytics(array $sessionUser, ?string $dateFilter = null): array
    {
        if ((string)($sessionUser['role'] ?? '') !== 'admin') {
            throw new RuntimeException('Yetkisiz erişim! Platform analitiğine yalnızca yöneticiler erişebilir.', 403);
        }

        $orders = $this->orderRepo->findAll();
        $restaurants = $this->restaurantRepo->findAll();
        $restNames = [];
        $byRestaurant = [];
        foreach ($restaurants as $r) {
            $restNames[$r->id] = $r->name;
            $byRestaurant[$r->id] = [
                'restaurant_id' => $r->id,
                'name' => $r->name,
                'is_active' => $r->isActive,
                'orders' => 0,
                'revenue' => 0.0,
            ];
        }

        $statusCounts = [
            'alindi' => 0,
            'hazirlaniyor' => 0,
            'hazir' => 0,
            'yolda' => 0,
            'teslim_edildi' => 0,
            'iptal' => 0,
        ];
        $modeCounts = ['courier' => 0, 'takeaway' => 0];
        $totalRevenue = 0.0;
        $validOrdersCount = 0;

        $cleanDate = ($dateFilter !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($dateFilter))) ? trim($dateFilter) : null;

        foreach ($orders as $o) {
            if ($cleanDate !== null && $o->createdAt !== null && !str_starts_with($o->createdAt, $cleanDate)) {
                continue;
            }
            $stKey = $o->status->value;
            $statusCounts[$stKey] = ($statusCounts[$stKey] ?? 0) + 1;
            $modeKey = ($o->deliveryMode === 'takeaway') ? 'takeaway' : 'courier';
            $modeCounts[$modeKey]++;

            if ($o->status !== OrderStatus::IPTAL) {
                $validOrdersCount++;
                $totalRevenue += $o->totalAmount;
                if (!isset($byRestaurant[$o->restaurantId])) {
                    $byRestaurant[$o->restaurantId] = [
                        'restaurant_id' => $o->restaurantId,
                        'name' => $restNames[$o->restaurantId] ?? ('Restoran #' . $o->restaurantId),
                        'is_active' => true,
                        'orders' => 0,
                        'revenue' => 0.0,
                    ];
                }
                $byRestaurant[$o->restaurantId]['orders']++;
                $byRestaurant[$o->restaurantId]['revenue'] = round($byRestaurant[$o->restaurantId]['revenue'] + $o->totalAmount, 2);
            }
        }

        $restaurantStats = array_values($byRestaurant);
        usort($restaurantStats, static fn(array $a, array $b): int => $b['revenue'] <=> $a['revenue']);

        return [
            'status' => 'success',
            'date' => $cleanDate ?? 'all',
            'total_orders' => count($orders),
            'valid_orders' => $validOrdersCount,
            'total_revenue' => round($totalRevenue, 2),
            'avg_order_value' => $validOrdersCount > 0 ? round($totalRevenue / $validOrdersCount, 2) : 0.0,
            'status_counts' => $statusCounts,
            'mode_counts' => $modeCounts,
            'by_restaurant' => $restaurantStats,
            'fleet' => $this->orderService->getFleetScores(),
        ];
    }

    /**
     * Kullanıcı profili, adresleri ve cüzdan puanı sunucu kaydını döner.
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function getUserProfileData(array $sessionUser): array
    {
        $userId = (int)($sessionUser['id'] ?? 0);
        if ($userId <= 0) {
            return ['wallet_points' => 145, 'addresses' => [], 'active_location' => null];
        }

        $user = $this->userRepo->findById($userId);
        $profiles = file_exists($this->profilesFile) ? JsonStorageHelper::read($this->profilesFile) : [];
        $profile = null;
        foreach ($profiles as $row) {
            if ((int)($row['user_id'] ?? 0) === $userId) {
                $profile = $row;
                break;
            }
        }

        return [
            'user_id' => $userId,
            'fullName' => $user !== null ? $user->fullName : (string)($sessionUser['fullName'] ?? ''),
            'phone' => $user !== null ? $user->phone : (string)($sessionUser['phone'] ?? ''),
            'email' => $user !== null ? ($user->email ?? '') : (string)($sessionUser['email'] ?? ''),
            'wallet_points' => is_array($profile) && isset($profile['wallet_points']) ? (int)$profile['wallet_points'] : 145,
            'addresses' => is_array($profile) && is_array($profile['addresses'] ?? null) ? $profile['addresses'] : [],
            'active_location' => is_array($profile) && is_array($profile['active_location'] ?? null) ? $profile['active_location'] : null,
        ];
    }

    /**
     * Kullanıcı profil bilgilerini storage/users.json dosyasında kalıcı olarak günceller.
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function updateUserProfile(array $sessionUser, string $fullName, string $phone, ?string $email = null): array
    {
        $userId = (int)($sessionUser['id'] ?? 0);
        $user = $this->userRepo->findById($userId);
        if ($user === null) {
            throw new RuntimeException('Kullanıcı hesabı bulunamadı.', 404);
        }

        $cleanName = trim($fullName);
        if ($cleanName === '' || strlen($cleanName) < 2) {
            throw new InvalidArgumentException('Ad Soyad en az 2 karakter olmalıdır.');
        }

        $user->fullName = $cleanName;
        $user->phone = trim($phone);
        if ($email !== null && trim($email) !== '') {
            $user->email = trim($email);
        }
        $this->userRepo->save($user);

        return [
            'status' => 'success',
            'message' => 'Profil bilgileriniz sunucuda kalıcı olarak güncellendi.',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role->value,
                'fullName' => $user->fullName,
                'phone' => $user->phone,
                'email' => $user->email ?? '',
            ],
        ];
    }

    /**
     * Müşteri kayıtlı adreslerini ve aktif konumunu storage/user_profiles.json dosyasına yazar.
     *
     * @param array<string, mixed> $sessionUser
     * @param array<int, mixed> $addresses
     * @param array<string, mixed>|null $activeLocation
     * @return array<string, mixed>
     */
    public function saveUserAddresses(array $sessionUser, array $addresses, ?array $activeLocation = null): array
    {
        $userId = (int)($sessionUser['id'] ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('Yetkisiz erişim! Lütfen oturum açınız.', 401);
        }

        $sanitized = [];
        foreach (array_slice($addresses, 0, 20) as $idx => $a) {
            if (!is_array($a)) {
                continue;
            }
            $title = trim((string)($a['title'] ?? 'Adres'));
            $desc = trim((string)($a['desc'] ?? 'Kadıköy'));
            if ($title === '') {
                continue;
            }
            $sanitized[] = [
                'id' => (int)($a['id'] ?? ($idx + 1)),
                'title' => substr($title, 0, 80),
                'desc' => substr($desc, 0, 200),
                'x' => (int)($a['x'] ?? 18),
                'y' => (int)($a['y'] ?? 14),
                'lat' => round((float)($a['lat'] ?? 40.9840), 6),
                'lon' => round((float)($a['lon'] ?? 29.0270), 6),
                'neighborhood' => substr(trim((string)($a['neighborhood'] ?? 'Kadıköy')), 0, 80),
                'isDefault' => (bool)($a['isDefault'] ?? false),
            ];
        }

        $saved = JsonStorageHelper::update($this->profilesFile, static function (array $records) use ($userId, $sanitized, $activeLocation): array {
            $found = -1;
            foreach ($records as $i => $row) {
                if ((int)($row['user_id'] ?? 0) === $userId) {
                    $found = $i;
                    break;
                }
            }
            $entry = $found >= 0 ? $records[$found] : ['user_id' => $userId, 'wallet_points' => 145];
            $entry['addresses'] = $sanitized;
            if ($activeLocation !== null) {
                $entry['active_location'] = [
                    'x' => (int)($activeLocation['x'] ?? 18),
                    'y' => (int)($activeLocation['y'] ?? 14),
                    'lat' => round((float)($activeLocation['lat'] ?? 40.9840), 6),
                    'lon' => round((float)($activeLocation['lon'] ?? 29.0270), 6),
                    'name' => substr(trim((string)($activeLocation['name'] ?? 'Evim (Moda)')), 0, 80),
                ];
            }
            $entry['updated_at'] = date('Y-m-d H:i:s');
            if ($found >= 0) {
                $records[$found] = $entry;
            } else {
                $records[] = $entry;
            }
            return ['data' => $records, 'result' => $entry];
        });

        return [
            'status' => 'success',
            'message' => 'Teslimat adresleriniz sunucuya kaydedildi.',
            'profile' => $saved,
        ];
    }

    /**
     * Müşteri değerlendirmesi kaydeder, mükerrer puanlamayı engeller ve +25 Cravelo Puan ekler.
     *
     * @param array<string, mixed> $sessionUser
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function submitReview(array $sessionUser, array $payload): array
    {
        $role = (string)($sessionUser['role'] ?? '');
        $userId = (int)($sessionUser['id'] ?? 0);
        if (!in_array($role, ['customer', 'admin'], true) || $userId <= 0) {
            throw new RuntimeException('Değerlendirme yalnızca oturum açmış müşteriler tarafından yapılabilir.', 403);
        }

        $orderId = (int)($payload['order_id'] ?? 0);
        $restaurantId = (int)($payload['restaurant_id'] ?? 0);

        if ($orderId > 0) {
            $order = $this->orderRepo->findById($orderId);
            if ($order !== null) {
                if ($role === 'customer' && $order->customerId !== $userId) {
                    throw new RuntimeException('Yalnızca kendi siparişinizi değerlendirebilirsiniz.', 403);
                }
                $restaurantId = $order->restaurantId;
            }
        }

        if ($restaurantId <= 0 || $this->restaurantRepo->findById($restaurantId) === null) {
            throw new InvalidArgumentException('Geçerli bir restoran seçilmelidir.');
        }

        $taste = max(1, min(5, (int)($payload['taste'] ?? 5)));
        $speed = max(1, min(5, (int)($payload['speed'] ?? 5)));
        $service = max(1, min(5, (int)($payload['service'] ?? 5)));
        $avg = round(($taste + $speed + $service) / 3.0, 2);
        $comment = substr(trim((string)($payload['comment'] ?? '')), 0, 500);
        $rawTags = is_array($payload['tags'] ?? null) ? $payload['tags'] : [];
        $tags = [];
        foreach (array_slice($rawTags, 0, 8) as $t) {
            $cleanTag = substr(trim((string)$t), 0, 60);
            if ($cleanTag !== '') {
                $tags[] = $cleanTag;
            }
        }

        $authorName = (string)($sessionUser['fullName'] ?? $sessionUser['username'] ?? 'Cravelo Müşterisi');

        $savedReview = JsonStorageHelper::update($this->reviewsFile, static function (array $records) use ($orderId, $restaurantId, $userId, $authorName, $taste, $speed, $service, $avg, $comment, $tags): array {
            $maxId = 0;
            foreach ($records as $row) {
                $rid = (int)($row['id'] ?? 0);
                if ($rid > $maxId) {
                    $maxId = $rid;
                }
                if ($orderId > 0 && (int)($row['order_id'] ?? 0) === $orderId && (int)($row['user_id'] ?? 0) === $userId) {
                    throw new InvalidArgumentException('Bu sipariş için daha önce değerlendirme yaptınız.');
                }
            }
            $entry = [
                'id' => $maxId + 1,
                'order_id' => $orderId,
                'restaurant_id' => $restaurantId,
                'user_id' => $userId,
                'author' => $authorName,
                'taste' => $taste,
                'speed' => $speed,
                'service' => $service,
                'rating' => $avg,
                'tags' => $tags,
                'comment' => $comment,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            array_unshift($records, $entry);
            return ['data' => $records, 'result' => $entry];
        });

        // Sunucu tarafında +25 Cravelo Puan ödülü işle
        $newPoints = $this->adjustWalletPoints($userId, 25);
        $summary = $this->getReviewSummaryByRestaurant()[$restaurantId] ?? ['avg' => $avg, 'count' => 1];

        $this->appendAudit(
            'Müşteri Değerlendirmesi (' . number_format($avg, 1) . '★)',
            $authorName,
            "Restoran #{$restaurantId}" . ($orderId > 0 ? " / Sipariş #{$orderId}" : '')
        );

        return [
            'status' => 'success',
            'message' => 'Değerlendirmeniz kaydedildi ve +25 Cravelo Puan hesabınıza eklendi!',
            'review' => $savedReview,
            'wallet_points' => $newPoints,
            'restaurant_rating' => $summary['avg'] !== null ? number_format($summary['avg'], 1) : number_format($avg, 1),
            'review_count' => $summary['count'],
        ];
    }

    /**
     * Restorana veya tüm platforma ait değerlendirmeleri döner.
     *
     * @return array{status: string, reviews: array<int, array<string, mixed>>, summary: array<int, array{avg: ?float, count: int}>}
     */
    public function getReviews(?int $restaurantId = null): array
    {
        $all = file_exists($this->reviewsFile) ? JsonStorageHelper::read($this->reviewsFile) : [];
        $filtered = ($restaurantId !== null && $restaurantId > 0)
            ? array_values(array_filter($all, static fn(array $r): bool => (int)($r['restaurant_id'] ?? 0) === $restaurantId))
            : $all;

        return [
            'status' => 'success',
            'reviews' => $filtered,
            'summary' => $this->getReviewSummaryByRestaurant(),
        ];
    }

    /**
     * IDOR korumalı sipariş mesaj geçmişini döner (Müşteri <-> Kurye / Restoran / Admin).
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function getOrderMessages(array $sessionUser, int $orderId): array
    {
        $order = $this->orderRepo->findById($orderId);
        if ($order === null) {
            throw new RuntimeException("Sipariş #{$orderId} bulunamadı.", 404);
        }
        $this->orderService->assertSessionUserCanAccessOrder($sessionUser, $order);

        $all = file_exists($this->messagesFile) ? JsonStorageHelper::read($this->messagesFile) : [];
        $orderMsgs = array_values(array_filter(
            $all,
            static fn(array $m): bool => (int)($m['order_id'] ?? 0) === $orderId
        ));

        return [
            'status' => 'success',
            'order_id' => $orderId,
            'messages' => $orderMsgs,
        ];
    }

    /**
     * IDOR korumalı çift yönlü sipariş mesajı kaydeder (storage/messages.json).
     *
     * @param array<string, mixed> $sessionUser
     * @return array<string, mixed>
     */
    public function sendOrderMessage(array $sessionUser, int $orderId, string $text): array
    {
        $order = $this->orderRepo->findById($orderId);
        if ($order === null) {
            throw new RuntimeException("Sipariş #{$orderId} bulunamadı.", 404);
        }
        $this->orderService->assertSessionUserCanAccessOrder($sessionUser, $order);

        $cleanText = trim($text);
        if ($cleanText === '') {
            throw new InvalidArgumentException('Mesaj metni boş olamaz.');
        }
        if (strlen($cleanText) > 500) {
            $cleanText = substr($cleanText, 0, 500);
        }

        $senderId = (int)($sessionUser['id'] ?? 0);
        $senderRole = (string)($sessionUser['role'] ?? 'customer');
        $senderName = (string)($sessionUser['fullName'] ?? $sessionUser['username'] ?? 'Kullanıcı');

        $saved = JsonStorageHelper::update($this->messagesFile, static function (array $records) use ($orderId, $senderId, $senderRole, $senderName, $cleanText): array {
            $maxId = 0;
            foreach ($records as $row) {
                $mid = (int)($row['id'] ?? 0);
                if ($mid > $maxId) {
                    $maxId = $mid;
                }
            }
            $entry = [
                'id' => $maxId + 1,
                'order_id' => $orderId,
                'sender_id' => $senderId,
                'sender_role' => $senderRole,
                'sender_name' => $senderName,
                'text' => $cleanText,
                'time' => date('H:i'),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $records[] = $entry;
            if (count($records) > 1000) {
                $records = array_slice($records, -1000);
            }
            return ['data' => $records, 'result' => $entry];
        });

        return [
            'status' => 'success',
            'order_id' => $orderId,
            'message_item' => $saved,
        ];
    }

    /**
     * @param array<string, mixed> $sessionUser
     */
    private function resolveAuthorizedCourier(array $sessionUser, int $courierId): \App\Model\Courier
    {
        $role = (string)($sessionUser['role'] ?? '');
        if (!in_array($role, ['courier', 'admin'], true)) {
            throw new RuntimeException('Yetkisiz erişim! Bu işlem yalnızca kurye veya yönetici tarafından yapılabilir.', 403);
        }

        if ($role === 'courier') {
            $own = $this->courierRepo->findByUserId((int)($sessionUser['id'] ?? 0));
            if ($own === null) {
                throw new RuntimeException('Hesabınıza tanımlı bir kurye profili bulunamadı.', 404);
            }
            if ($courierId > 0 && $courierId !== $own->id) {
                throw new RuntimeException('Yetkisiz erişim! Yalnızca kendi kurye profilinizi güncelleyebilirsiniz.', 403);
            }
            return $own;
        }

        $target = $this->courierRepo->findById($courierId);
        if ($target === null) {
            throw new RuntimeException("Kurye #{$courierId} bulunamadı.", 404);
        }
        return $target;
    }

    /**
     * @return array<int, array{avg: ?float, count: int}>
     */
    private function getReviewSummaryByRestaurant(): array
    {
        $all = file_exists($this->reviewsFile) ? JsonStorageHelper::read($this->reviewsFile) : [];
        $buckets = [];
        foreach ($all as $r) {
            $rId = (int)($r['restaurant_id'] ?? 0);
            if ($rId <= 0) {
                continue;
            }
            if (!isset($buckets[$rId])) {
                $buckets[$rId] = ['sum' => 0.0, 'count' => 0];
            }
            $buckets[$rId]['sum'] += (float)($r['rating'] ?? 5.0);
            $buckets[$rId]['count']++;
        }

        $summary = [];
        foreach ($buckets as $rId => $b) {
            $summary[$rId] = [
                'avg' => round($b['sum'] / $b['count'], 2),
                'count' => $b['count'],
            ];
        }
        return $summary;
    }

    private function adjustWalletPoints(int $userId, int $delta): int
    {
        return (int)JsonStorageHelper::update($this->profilesFile, static function (array $records) use ($userId, $delta): array {
            $found = -1;
            foreach ($records as $i => $row) {
                if ((int)($row['user_id'] ?? 0) === $userId) {
                    $found = $i;
                    break;
                }
            }
            $entry = $found >= 0 ? $records[$found] : ['user_id' => $userId, 'wallet_points' => 145, 'addresses' => []];
            $current = (int)($entry['wallet_points'] ?? 145);
            $next = max(0, $current + $delta);
            $entry['wallet_points'] = $next;
            $entry['updated_at'] = date('Y-m-d H:i:s');
            if ($found >= 0) {
                $records[$found] = $entry;
            } else {
                $records[] = $entry;
            }
            return ['data' => $records, 'result' => $next];
        });
    }

    private function appendAudit(string $action, string $user, string $detail): void
    {
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
            error_log('[PlatformFeatureService::appendAudit] ' . $e->getMessage());
        }
    }
}
