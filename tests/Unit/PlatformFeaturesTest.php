<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Enum\CourierStatus;
use App\Enum\OrderStatus;
use App\Repository\Json\JsonCourierRepository;
use App\Repository\Json\JsonOrderRepository;
use App\Repository\Json\JsonRestaurantRepository;
use App\Repository\Json\JsonUserRepository;
use App\Service\OrderService;
use App\Service\PlatformFeatureService;
use App\Storage\JsonStorageHelper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PlatformFeaturesTest extends TestCase
{
    private string $tempDir;
    private PlatformFeatureService $service;
    private OrderService $orderService;
    private JsonCourierRepository $courierRepo;
    private JsonRestaurantRepository $restRepo;
    private JsonOrderRepository $orderRepo;
    private JsonUserRepository $userRepo;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/cravelo_feat_' . bin2hex(random_bytes(5));
        mkdir($this->tempDir, 0777, true);

        JsonStorageHelper::write($this->tempDir . '/users.json', [
            ['id' => 1, 'username' => 'ahmet_musteri', 'email' => 'ahmet@ornek.com', 'password_hash' => password_hash('hash123', PASSWORD_DEFAULT), 'full_name' => 'Ahmet Yılmaz', 'phone' => '05551112233', 'role' => 'customer', 'created_at' => '2026-10-20 10:00:00'],
            ['id' => 2, 'username' => 'ayse_musteri', 'email' => 'ayse@ornek.com', 'password_hash' => password_hash('hash123', PASSWORD_DEFAULT), 'full_name' => 'Ayşe Kaya', 'phone' => '05552223344', 'role' => 'customer', 'created_at' => '2026-10-20 10:05:00'],
            ['id' => 4, 'username' => 'restoran_lezzet', 'email' => 'lezzet@ornek.com', 'password_hash' => password_hash('hash123', PASSWORD_DEFAULT), 'full_name' => 'Lezzet Kebap', 'phone' => '02163334455', 'role' => 'restaurant', 'created_at' => '2026-10-20 09:00:00'],
            ['id' => 5, 'username' => 'restoran_burger', 'email' => 'burger@ornek.com', 'password_hash' => password_hash('hash123', PASSWORD_DEFAULT), 'full_name' => 'Burger Lab', 'phone' => '02164445566', 'role' => 'restaurant', 'created_at' => '2026-10-20 09:10:00'],
            ['id' => 7, 'username' => 'kurye_ali', 'email' => 'ali.kurye@ornek.com', 'password_hash' => password_hash('hash123', PASSWORD_DEFAULT), 'full_name' => 'Ali Kaya', 'phone' => '05554445566', 'role' => 'courier', 'created_at' => '2026-10-20 08:30:00'],
            ['id' => 8, 'username' => 'kurye_veli', 'email' => 'veli.kurye@ornek.com', 'password_hash' => password_hash('hash123', PASSWORD_DEFAULT), 'full_name' => 'Veli Demir', 'phone' => '05555556677', 'role' => 'courier', 'created_at' => '2026-10-20 08:35:00'],
            ['id' => 10, 'username' => 'admin_mustafa', 'email' => 'mustafaguresir@gmail.com', 'password_hash' => password_hash('hash123', PASSWORD_DEFAULT), 'full_name' => 'Mustafa Güreşir', 'phone' => '05557778899', 'role' => 'admin', 'created_at' => '2026-10-20 08:00:00'],
        ]);

        JsonStorageHelper::write($this->tempDir . '/restaurants.json', [
            ['id' => 1, 'user_id' => 4, 'name' => 'Lezzet Kebap Salonu', 'location_x' => 12, 'location_y' => 8, 'latitude' => 40.9850, 'longitude' => 29.0280, 'neighborhood' => 'Moda, Kadıköy', 'phone' => '0216 444 10 01', 'is_active' => true],
            ['id' => 2, 'user_id' => 5, 'name' => 'Burger Lab', 'location_x' => 16, 'location_y' => 12, 'latitude' => 40.9880, 'longitude' => 29.0310, 'neighborhood' => 'Bahariye, Kadıköy', 'phone' => '0216 444 20 02', 'is_active' => true],
        ]);

        JsonStorageHelper::write($this->tempDir . '/menu_items.json', [
            ['id' => 1, 'restaurant_id' => 1, 'name' => 'Adana Zırh Dürüm', 'description' => 'Zırh kıyması', 'price' => 190.0, 'category' => 'Kebap', 'is_available' => true],
            ['id' => 6, 'restaurant_id' => 2, 'name' => 'Smash Cheeseburger', 'description' => '140gr köfte', 'price' => 250.0, 'category' => 'Burger', 'is_available' => true],
        ]);

        JsonStorageHelper::write($this->tempDir . '/couriers.json', [
            ['id' => 1, 'user_id' => 7, 'name' => 'Ali Kaya', 'current_x' => 14, 'current_y' => 10, 'latitude' => 40.9910, 'longitude' => 29.0250, 'neighborhood' => 'Rıhtım, Kadıköy', 'active_orders_count' => 1, 'status' => 'musait'],
            ['id' => 2, 'user_id' => 8, 'name' => 'Veli Demir', 'current_x' => 30, 'current_y' => 28, 'latitude' => 40.9760, 'longitude' => 29.0380, 'neighborhood' => 'Fenerbahçe, Kadıköy', 'active_orders_count' => 0, 'status' => 'musait'],
        ]);

        JsonStorageHelper::write($this->tempDir . '/orders.json', [
            [
                'id' => 1, 'customer_id' => 1, 'restaurant_id' => 1, 'courier_id' => 1,
                'status' => 'yolda', 'total_amount' => 380.0, 'delivery_x' => 18, 'delivery_y' => 14,
                'delivery_latitude' => 40.9840, 'delivery_longitude' => 29.0270, 'delivery_mode' => 'courier',
                'estimated_delivery_time' => '15 dk', 'created_at' => date('Y-m-d H:i:s'),
                'items' => [['id' => 1, 'order_id' => 1, 'menu_item_id' => 1, 'name' => 'Adana Zırh Dürüm', 'quantity' => 2, 'unit_price' => 190.0, 'subtotal' => 380.0]],
            ],
            [
                'id' => 2, 'customer_id' => 1, 'restaurant_id' => 2, 'courier_id' => null,
                'status' => 'teslim_edildi', 'total_amount' => 200.0, 'delivery_x' => 16, 'delivery_y' => 12,
                'delivery_latitude' => 40.9880, 'delivery_longitude' => 29.0310, 'delivery_mode' => 'takeaway',
                'estimated_delivery_time' => 'Tamamlandı', 'created_at' => date('Y-m-d H:i:s'),
                'items' => [['id' => 2, 'order_id' => 2, 'menu_item_id' => 6, 'name' => 'Smash Cheeseburger', 'quantity' => 1, 'unit_price' => 200.0, 'subtotal' => 200.0]],
            ],
        ]);

        JsonStorageHelper::write($this->tempDir . '/audit_logs.json', []);
        JsonStorageHelper::write($this->tempDir . '/reviews.json', []);
        JsonStorageHelper::write($this->tempDir . '/messages.json', []);
        JsonStorageHelper::write($this->tempDir . '/user_profiles.json', []);

        $this->orderRepo = new JsonOrderRepository(
            $this->tempDir . '/orders.json',
            $this->tempDir . '/restaurants.json',
            $this->tempDir . '/users.json',
            $this->tempDir . '/couriers.json'
        );
        $this->restRepo = new JsonRestaurantRepository(
            $this->tempDir . '/restaurants.json',
            $this->tempDir . '/menu_items.json'
        );
        $this->courierRepo = new JsonCourierRepository($this->tempDir . '/couriers.json');
        $this->userRepo = new JsonUserRepository($this->tempDir . '/users.json');
        $this->orderService = new OrderService(
            orderRepo: $this->orderRepo,
            restaurantRepo: $this->restRepo,
            courierRepo: $this->courierRepo,
            userRepo: $this->userRepo,
            auditLogFile: $this->tempDir . '/audit_logs.json',
            dispatchLockFile: $this->tempDir . '/dispatch.lock'
        );
        $this->service = new PlatformFeatureService(
            orderRepo: $this->orderRepo,
            restaurantRepo: $this->restRepo,
            courierRepo: $this->courierRepo,
            userRepo: $this->userRepo,
            orderService: $this->orderService,
            storageDir: $this->tempDir
        );
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*') ?: [];
            foreach ($files as $f) {
                @unlink($f);
            }
            @rmdir($this->tempDir);
        }
    }

    public function testCourierCanUpdateOwnLocationAndStatusPersistsToJson(): void
    {
        $courierUser = ['id' => 7, 'role' => 'courier', 'fullName' => 'Ali Kaya'];
        $locRes = $this->service->updateCourierLocation($courierUser, 1, 22, 26, 40.9740, 29.0410, 'Kalamış Marina, Kadıköy');
        $this->assertSame('success', $locRes['status']);

        $stRes = $this->service->updateCourierStatus($courierUser, 1, 'mesgul');
        $this->assertSame('success', $stRes['status']);

        $reloaded = $this->courierRepo->findById(1);
        $this->assertNotNull($reloaded);
        $this->assertSame(22, $reloaded->currentX);
        $this->assertSame(26, $reloaded->currentY);
        $this->assertEqualsWithDelta(40.9740, $reloaded->latitude, 0.0001);
        $this->assertSame('Kalamış Marina, Kadıköy', $reloaded->neighborhood);
        $this->assertSame(CourierStatus::MESGUL, $reloaded->status);
    }

    public function testCourierCannotUpdateAnotherCourierProfile(): void
    {
        $courierUser = ['id' => 7, 'role' => 'courier', 'fullName' => 'Ali Kaya'];
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(403);
        $this->service->updateCourierStatus($courierUser, 2, 'offline');
    }

    public function testBusyCourierIsExcludedFromAvailablePool(): void
    {
        $courierUser = ['id' => 7, 'role' => 'courier', 'fullName' => 'Ali Kaya'];
        $this->service->updateCourierStatus($courierUser, 1, 'mesgul');

        $available = $this->courierRepo->findAvailable();
        $ids = array_map(static fn($c) => $c->id, $available);
        $this->assertNotContains(1, $ids);
        $this->assertContains(2, $ids);
    }

    public function testKdsCanCreateAndUpdateMenuItemWithOwnershipGuard(): void
    {
        $owner = ['id' => 4, 'role' => 'restaurant', 'fullName' => 'Lezzet Kebap'];
        $created = $this->service->saveMenuItem($owner, [
            'restaurant_id' => 1,
            'name' => 'Beyti Sarma',
            'price' => 310.50,
            'category' => 'Kebap',
            'description' => 'Lavaşta tereyağlı beyti',
            'is_available' => true,
        ]);
        $this->assertSame('success', $created['status']);
        $newId = (int)$created['item']['id'];
        $this->assertGreaterThan(6, $newId);

        $updated = $this->service->saveMenuItem($owner, [
            'id' => $newId,
            'restaurant_id' => 1,
            'name' => 'Özel Beyti Sarma',
            'price' => 330.00,
            'category' => 'Kebap',
            'is_available' => false,
        ]);
        $this->assertSame('Özel Beyti Sarma', $updated['item']['name']);
        $this->assertFalse($updated['item']['available']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(403);
        $this->service->saveMenuItem($owner, [
            'restaurant_id' => 2,
            'name' => 'İzinsiz Burger',
            'price' => 150.0,
            'category' => 'Burger',
        ]);
    }

    public function testToggleRestaurantActiveClosesRestaurantAndRejectsNewOrders(): void
    {
        $owner = ['id' => 4, 'role' => 'restaurant', 'fullName' => 'Lezzet Kebap'];
        $res = $this->service->toggleRestaurantActive($owner, 1, false);
        $this->assertFalse($res['is_active']);

        $this->expectException(InvalidArgumentException::class);
        $this->orderService->createOrder(
            customerId: 1,
            restaurantId: 1,
            totalAmount: 190.0,
            items: [['menu_item_id' => 1, 'name' => 'Adana Zırh Dürüm', 'quantity' => 1, 'price' => 190.0]]
        );
    }

    public function testAdminCanReassignCourierAndBalancesWorkload(): void
    {
        $admin = ['id' => 10, 'role' => 'admin', 'fullName' => 'Mustafa Güreşir'];
        $res = $this->service->reassignOrderCourier($admin, 1, 2);
        $this->assertSame('success', $res['status']);
        $this->assertSame(2, $res['courier_id']);

        $oldCourier = $this->courierRepo->findById(1);
        $newCourier = $this->courierRepo->findById(2);
        $order = $this->orderRepo->findById(1);

        $this->assertNotNull($oldCourier);
        $this->assertNotNull($newCourier);
        $this->assertNotNull($order);
        $this->assertSame(0, $oldCourier->activeOrdersCount);
        $this->assertSame(1, $newCourier->activeOrdersCount);
        $this->assertSame(2, $order->courierId);

        // Gel-Al / teslim edilmiş sipariş reddi
        try {
            $this->service->reassignOrderCourier($admin, 2, 1);
            $this->fail('Gel-Al siparişine kurye ataması reddedilmeliydi.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Gel-Al', $e->getMessage());
        }

        // Non-admin RBAC reddi
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(403);
        $this->service->reassignOrderCourier(['id' => 1, 'role' => 'customer'], 1, 1);
    }

    public function testAdminAnalyticsCalculatesRevenueAndModeBreakdown(): void
    {
        $admin = ['id' => 10, 'role' => 'admin', 'fullName' => 'Mustafa Güreşir'];
        $analytics = $this->service->getAdminAnalytics($admin);

        $this->assertSame('success', $analytics['status']);
        $this->assertSame(2, $analytics['total_orders']);
        $this->assertSame(2, $analytics['valid_orders']);
        $this->assertEqualsWithDelta(580.0, $analytics['total_revenue'], 0.01);
        $this->assertEqualsWithDelta(290.0, $analytics['avg_order_value'], 0.01);
        $this->assertSame(1, $analytics['mode_counts']['courier']);
        $this->assertSame(1, $analytics['mode_counts']['takeaway']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(403);
        $this->service->getAdminAnalytics(['id' => 1, 'role' => 'customer']);
    }

    public function testCustomerProfileAndAddressesPersistAcrossReload(): void
    {
        $cust = ['id' => 1, 'role' => 'customer', 'fullName' => 'Ahmet Yılmaz'];
        $upd = $this->service->updateUserProfile($cust, 'Ahmet K. Yılmaz', '05329998877', 'ahmet.yeni@ornek.com');
        $this->assertSame('Ahmet K. Yılmaz', $upd['user']['fullName']);

        $addrs = [
            ['id' => 1, 'title' => 'Yeni Ev', 'desc' => 'Bağdat Cad. No:42', 'x' => 20, 'y' => 24, 'lat' => 40.968, 'lon' => 29.064, 'neighborhood' => 'Caddebostan', 'isDefault' => true],
        ];
        $this->service->saveUserAddresses($cust, $addrs, ['x' => 20, 'y' => 24, 'lat' => 40.968, 'lon' => 29.064, 'name' => 'Yeni Ev']);

        $profile = $this->service->getUserProfileData($cust);
        $this->assertSame('Ahmet K. Yılmaz', $profile['fullName']);
        $this->assertSame('05329998877', $profile['phone']);
        $this->assertCount(1, $profile['addresses']);
        $this->assertSame('Yeni Ev', $profile['active_location']['name']);
    }

    public function testSubmitReviewAwardsPointsPreventsDuplicatesAndUpdatesCatalogRating(): void
    {
        $cust = ['id' => 1, 'role' => 'customer', 'fullName' => 'Ahmet Yılmaz'];
        $res = $this->service->submitReview($cust, [
            'order_id' => 2,
            'restaurant_id' => 2,
            'taste' => 5,
            'speed' => 4,
            'service' => 5,
            'tags' => ['Sıcacık Geldi'],
            'comment' => 'Harika burger!',
        ]);

        $this->assertSame('success', $res['status']);
        $this->assertSame(170, $res['wallet_points']); // 145 + 25
        $this->assertSame(1, $res['review_count']);

        $catalog = $this->service->getServerCatalogPayload();
        $burgerRest = array_values(array_filter($catalog['restaurants'], static fn($r) => $r['id'] === 2))[0];
        $this->assertSame(1, $burgerRest['review_count']);
        $this->assertSame('4.7', $burgerRest['rating']);

        // Aynı siparişe mükerrer değerlendirme engeli
        $this->expectException(InvalidArgumentException::class);
        $this->service->submitReview($cust, [
            'order_id' => 2,
            'restaurant_id' => 2,
            'taste' => 5,
            'speed' => 5,
            'service' => 5,
            'comment' => 'İkinci deneme',
        ]);
    }

    public function testBidirectionalOrderChatPersistsMessagesAndEnforcesIdor(): void
    {
        $cust = ['id' => 1, 'role' => 'customer', 'fullName' => 'Ahmet Yılmaz'];
        $courier = ['id' => 7, 'role' => 'courier', 'fullName' => 'Ali Kaya'];
        $otherCust = ['id' => 2, 'role' => 'customer', 'fullName' => 'Ayşe Kaya'];

        $m1 = $this->service->sendOrderMessage($cust, 1, 'Kapı zili bozuk, arar mısınız?');
        $this->assertSame('success', $m1['status']);

        $m2 = $this->service->sendOrderMessage($courier, 1, 'Tabii Ahmet Bey, 2 dakikaya arıyorum.');
        $this->assertSame('courier', $m2['message_item']['sender_role']);

        $thread = $this->service->getOrderMessages($cust, 1);
        $this->assertCount(2, $thread['messages']);
        $this->assertSame('Kapı zili bozuk, arar mısınız?', $thread['messages'][0]['text']);
        $this->assertSame('Tabii Ahmet Bey, 2 dakikaya arıyorum.', $thread['messages'][1]['text']);

        // IDOR: Başka müşteri sipariş mesajlarını okuyamaz
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(403);
        $this->service->getOrderMessages($otherCust, 1);
    }
}
