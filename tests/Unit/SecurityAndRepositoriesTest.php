<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Core\Request;
use App\Core\Session;
use App\Enum\CourierStatus;
use App\Enum\OrderStatus;
use App\Enum\UserRole;
use App\Exception\InvalidOrderTransitionException;
use App\Model\Courier;
use App\Model\MenuItem;
use App\Model\Order;
use App\Model\Restaurant;
use App\Model\User;
use App\Repository\InMemory\InMemoryCourierRepository;
use App\Repository\InMemory\InMemoryOrderRepository;
use App\Repository\InMemory\InMemoryRestaurantRepository;
use App\Repository\InMemory\InMemoryUserRepository;
use App\Repository\Json\JsonCourierRepository;
use App\Repository\Json\JsonOrderRepository;
use App\Repository\Json\JsonRestaurantRepository;
use App\Repository\Json\JsonUserRepository;
use App\Service\AuthService;
use App\Service\OrderService;
use App\Service\OrderStateMachineService;
use App\Storage\JsonStorageHelper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Derin Denetim Doğrulama Testleri (CRV-01 .. CRV-16):
 * - Sunucu tarafı menü fiyat/stok/adet/aidiyet doğrulaması
 * - Kurye cancelOrder 403 engeli ve iptal geçişinde kurye yükü azaltımı
 * - Kuryesiz siparişin yolda geçiş reddi ve HAZIR geçişinde otomatik atama
 * - Kurye görev filtresi, eksik restoran fail-closed kontrolü
 * - JsonUser/Restaurant/CourierRepository, toggleMenuItemAvailability, gerçek audit_logs.json
 * - Request::getJson() scalar güvenliği, InMemoryOrderRepository paritesi ve CSRF doğrulaması
 */
final class SecurityAndRepositoriesTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cravelo_sec_test_' . uniqid('', true);
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            $files = glob($this->tmpDir . DIRECTORY_SEPARATOR . '*') ?: [];
            foreach ($files as $f) {
                @unlink($f);
            }
            @rmdir($this->tmpDir);
        }
    }

    public function testCreateOrderEnforcesCatalogPricingStockAndQuantity(): void
    {
        $orderRepo = new InMemoryOrderRepository();
        $courierRepo = new InMemoryCourierRepository();
        $restaurantRepo = new InMemoryRestaurantRepository();

        $restaurantRepo->save(new Restaurant(1, 4, 'Lezzet Kebap Salonu', 12, 8, '0216 444 10 01', true, 40.9850, 29.0280, 'Moda'));
        $restaurantRepo->saveMenuItem(new MenuItem(1, 1, 'Adana Zırh Dürüm', 'Zırh kıyması', 190.0, 'Kebap', true));
        $restaurantRepo->saveMenuItem(new MenuItem(2, 1, 'Urfa Kebap Porsiyon', 'Acısız', 240.0, 'Kebap', false));
        $courierRepo->save(new Courier(1, 7, 'Ali Kaya', 14, 10, 0, CourierStatus::MUSAIT, 40.9910, 29.0250));

        $service = new OrderService(
            orderRepo: $orderRepo,
            restaurantRepo: $restaurantRepo,
            courierRepo: $courierRepo,
            stateMachine: new OrderStateMachineService($orderRepo)
        );

        // 1. Manipüle edilmiş düşük birim fiyat reddedilmelidir
        try {
            $service->createOrder(1, 1, 0.01, 18, 14, [
                ['menu_item_id' => 1, 'name' => 'Adana Zırh Dürüm', 'quantity' => 2, 'price' => 0.01]
            ]);
            $this->fail('Menü fiyatından düşük birim fiyat reddedilmelidir.');
        } catch (InvalidArgumentException) {
            $this->assertCount(0, $orderRepo->findAll());
        }

        // 2. Başka restorana ait / bilinmeyen ürün ID reddedilmelidir
        try {
            $service->createOrder(1, 1, 250.0, 18, 14, [
                ['menu_item_id' => 99, 'name' => 'Sahte Ürün', 'quantity' => 1, 'price' => 250.0]
            ]);
            $this->fail('Restoran menüsünde olmayan ürün reddedilmelidir.');
        } catch (InvalidArgumentException) {
            $this->assertCount(0, $orderRepo->findAll());
        }

        // 3. Stokta tükenen ürün reddedilmelidir
        try {
            $service->createOrder(1, 1, 240.0, 18, 14, [
                ['menu_item_id' => 2, 'name' => 'Urfa Kebap Porsiyon', 'quantity' => 1, 'price' => 240.0]
            ]);
            $this->fail('Stokta olmayan (tükendi) ürün sipariş edilememelidir.');
        } catch (InvalidArgumentException) {
            $this->assertCount(0, $orderRepo->findAll());
        }

        // 4. Sıfır veya negatif adet reddedilmelidir (max(1,...) sessiz dönüşümü yapılmamalı)
        try {
            $service->createOrder(1, 1, 190.0, 18, 14, [
                ['menu_item_id' => 1, 'name' => 'Adana Zırh Dürüm', 'quantity' => 0, 'price' => 190.0]
            ]);
            $this->fail('Sıfır adet reddedilmelidir.');
        } catch (InvalidArgumentException) {
            $this->assertCount(0, $orderRepo->findAll());
        }

        // 5. İstemci düşük total_amount (0.01) gönderse bile sunucu gerçek tutarı (2 * 190 - %30 kupon = 266.0) hesaplar
        $res = $service->createOrder(
            customerId: 1,
            restaurantId: 1,
            totalAmount: 0.01,
            delX: 18,
            delY: 14,
            items: [['menu_item_id' => 1, 'name' => 'Adana Zırh Dürüm', 'quantity' => 2, 'price' => 190.0]],
            couponCode: 'CRAVELO30'
        );

        $this->assertSame('success', $res['status']);
        $this->assertEqualsWithDelta(266.0, (float)$res['total_amount'], 0.01);
        $saved = $orderRepo->findById((int)$res['order_id']);
        $this->assertNotNull($saved);
        $this->assertEqualsWithDelta(266.0, $saved->totalAmount, 0.01);
    }

    public function testCourierCannotCancelOrderAndAdminCancelTransitionDecrementsLoad(): void
    {
        $orderRepo = new InMemoryOrderRepository();
        $courierRepo = new InMemoryCourierRepository();
        $restaurantRepo = new InMemoryRestaurantRepository();

        $restaurantRepo->save(new Restaurant(1, 4, 'Lezzet Kebap Salonu', 12, 8, '0216', true, 40.9850, 29.0280));
        $courierRepo->save(new Courier(1, 7, 'Ali Kaya', 14, 10, 0, CourierStatus::MUSAIT, 40.9910, 29.0250));

        $service = new OrderService(
            orderRepo: $orderRepo,
            restaurantRepo: $restaurantRepo,
            courierRepo: $courierRepo,
            stateMachine: new OrderStateMachineService($orderRepo)
        );

        $created = $service->createOrder(1, 1, 200.0, 18, 14, []);
        $orderId = (int)$created['order_id'];
        $this->assertSame(1, $courierRepo->findById(1)?->activeOrdersCount);

        // Kurye kendisine atanmış olsa bile müşterinin siparişini iptal edemez (403)
        try {
            $service->cancelOrder($orderId, ['id' => 7, 'role' => 'courier', 'username' => 'kurye_ali']);
            $this->fail('Kurye rolü sipariş iptal edememelidir.');
        } catch (RuntimeException $e) {
            $this->assertSame(403, $e->getCode());
        }

        // Admin transitionStatus('iptal') çağrısında kuryenin aktif görev sayacı 1 -> 0 düşmelidir
        $res = $service->transitionStatus($orderId, 'iptal', 'admin', 10);
        $this->assertSame('iptal', $res['current_status']);
        $this->assertSame(0, $courierRepo->findById(1)?->activeOrdersCount);
    }

    public function testReadyStateBlocksUnassignedCourierAndAutoAssignsOnHazir(): void
    {
        $orderRepo = new InMemoryOrderRepository();
        $courierRepo = new InMemoryCourierRepository();
        $restaurantRepo = new InMemoryRestaurantRepository();
        $sm = new OrderStateMachineService($orderRepo);

        $restaurantRepo->save(new Restaurant(1, 4, 'Lezzet Kebap Salonu', 12, 8, '0216', true, 40.9850, 29.0280));

        // 1. Kuryesiz (courierId: null) kuryeli sipariş HAZIR -> YOLDA geçişi kesinlikle reddedilmelidir
        $unassignedOrder = new Order(10, 1, 1, null, OrderStatus::HAZIR, 180.0, 40.9850, 29.0280, deliveryMode: 'courier');
        $orderRepo->save($unassignedOrder);
        try {
            $sm->transition($unassignedOrder, OrderStatus::YOLDA);
            $this->fail('Kuryesiz sipariş yolda durumuna geçememelidir.');
        } catch (InvalidOrderTransitionException) {
            $this->assertSame(OrderStatus::HAZIR, $unassignedOrder->status);
        }

        // 2. Başlangıçta kurye yokken açılan sipariş, müsait kurye eklendikten sonra HAZIR geçişinde otomatik kurye almalıdır
        $service = new OrderService(
            orderRepo: $orderRepo,
            restaurantRepo: $restaurantRepo,
            courierRepo: $courierRepo,
            stateMachine: $sm
        );
        $created = $service->createOrder(1, 1, 220.0, 18, 14, []);
        $orderId = (int)$created['order_id'];
        $this->assertNull($created['assigned_courier']);

        $service->transitionStatus($orderId, 'hazirlaniyor', 'restaurant', 4);
        $courierRepo->save(new Courier(2, 8, 'Veli Demir', 30, 28, 0, CourierStatus::MUSAIT, 40.9750, 29.0350));

        $hazirRes = $service->transitionStatus($orderId, 'hazir', 'restaurant', 4);
        $this->assertSame('hazir', $hazirRes['current_status']);
        $this->assertSame(2, $hazirRes['courier_id']);
        $this->assertSame(1, $courierRepo->findById(2)?->activeOrdersCount);
    }

    public function testTransitionStatusFailsClosedAndCourierTasksFilterInactiveOrders(): void
    {
        $orderRepo = new InMemoryOrderRepository();
        $courierRepo = new InMemoryCourierRepository();
        $restaurantRepo = new InMemoryRestaurantRepository();
        $courierRepo->save(new Courier(1, 7, 'Ali Kaya', 14, 10, 1, CourierStatus::MUSAIT, 40.9910, 29.0250));

        // Restoran kaydı olmayan bir sipariş için restoran yetkilisi 403 almalıdır (fail-closed)
        $orphanOrder = new Order(50, 1, 999, 1, OrderStatus::ALINDI, 150.0, 40.9850, 29.0280);
        $orderRepo->save($orphanOrder);

        // Kurye görev filtresi: iptal, teslim_edildi ve takeaway siparişleri dışlanmalı; aktif kuryeli sipariş kalmalı
        $orderRepo->save(new Order(51, 1, 1, 1, OrderStatus::IPTAL, 120.0, 40.9850, 29.0280, deliveryMode: 'courier'));
        $orderRepo->save(new Order(52, 1, 1, 1, OrderStatus::TESLIM_EDILDI, 130.0, 40.9850, 29.0280, deliveryMode: 'courier'));
        $orderRepo->save(new Order(53, 1, 1, 1, OrderStatus::HAZIR, 140.0, 40.9850, 29.0280, deliveryMode: 'takeaway'));
        $orderRepo->save(new Order(54, 1, 1, 1, OrderStatus::HAZIR, 160.0, 40.9850, 29.0280, deliveryMode: 'courier'));

        $service = new OrderService(
            orderRepo: $orderRepo,
            restaurantRepo: $restaurantRepo,
            courierRepo: $courierRepo,
            stateMachine: new OrderStateMachineService($orderRepo)
        );

        try {
            $service->transitionStatus(50, 'hazirlaniyor', 'restaurant', 4);
            $this->fail('Restoran kaydı bulunmayan siparişte restoran rolü 403 ile engellenmelidir.');
        } catch (RuntimeException $e) {
            $this->assertSame(403, $e->getCode());
        }

        $tasks = $service->getCourierTasksForUser(['id' => 7, 'role' => 'courier']);
        $taskIds = array_column($tasks, 'id');
        $this->assertContains(50, $taskIds);
        $this->assertContains(54, $taskIds);
        $this->assertNotContains(51, $taskIds);
        $this->assertNotContains(52, $taskIds);
        $this->assertNotContains(53, $taskIds);
    }

    public function testJsonRepositoriesMenuStockToggleAuditLogsAndAuthValidation(): void
    {
        $usersFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'users.json';
        $restsFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'restaurants.json';
        $menuFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'menu_items.json';
        $couriersFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'couriers.json';
        $ordersFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'orders.json';
        $auditFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'audit_logs.json';
        $lockFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'dispatch.lock';

        $userRepo = new JsonUserRepository($usersFile);
        $restRepo = new JsonRestaurantRepository($restsFile, $menuFile);
        $courierRepo = new JsonCourierRepository($couriersFile);
        $orderRepo = new JsonOrderRepository($ordersFile, $restsFile, $usersFile, $couriersFile);

        // 1. JsonUserRepository & AuthService boş full_name + atomik mükerrer kullanıcı adı engeli
        $auth = new AuthService($userRepo);
        try {
            $auth->register('yeni_uye', 'parola123', '   ', '05551112233', UserRole::CUSTOMER);
            $this->fail('Boş Ad Soyad reddedilmelidir.');
        } catch (InvalidArgumentException) {
            $this->assertCount(0, $userRepo->findAll());
        }

        $u1 = $auth->register('zeynep_test', 'parola123', 'Zeynep Test', '05551112233', UserRole::CUSTOMER);
        $this->assertSame(1, $u1->id);

        try {
            $userRepo->save(new User(0, 'ZEYNEP_TEST', password_hash('parola123', PASSWORD_DEFAULT), 'Çift Kayıt', '0555', UserRole::CUSTOMER));
            $this->fail('JsonUserRepository LOCK_EX altında mükerrer kullanıcı adını reddetmelidir.');
        } catch (InvalidArgumentException) {
            $this->assertCount(1, $userRepo->findAll());
        }

        // 2. JsonCourierRepository::findByUserId(0) sahte eşleşme yapmamalıdır
        $courierRepo->save(new Courier(4, 0, 'Mehmet Arslan', 10, 6, 0, CourierStatus::MESGUL, 40.9950, 29.0280));
        $courierRepo->save(new Courier(1, 7, 'Ali Kaya', 14, 10, 0, CourierStatus::MUSAIT, 40.9910, 29.0250));
        $this->assertNull($courierRepo->findByUserId(0));
        $this->assertSame(1, $courierRepo->findByUserId(7)?->id);

        // 3. JsonRestaurantRepository + toggleMenuItemAvailability RBAC & kalıcı kayıt + gerçek audit_logs.json
        $restRepo->save(new Restaurant(1, 4, 'Lezzet Kebap Salonu', 12, 8, '0216', true, 40.9850, 29.0280));
        $restRepo->saveMenuItem(new MenuItem(1, 1, 'Adana Zırh Dürüm', 'Zırh kıyması', 190.0, 'Kebap', true));

        $service = new OrderService(
            orderRepo: $orderRepo,
            restaurantRepo: $restRepo,
            courierRepo: $courierRepo,
            userRepo: $userRepo,
            auditLogFile: $auditFile,
            dispatchLockFile: $lockFile
        );

        // Başka restoran sahibi (#5) Restoran #1'in ürün stoğunu değiştiremez (403)
        try {
            $service->toggleMenuItemAvailability(1, ['id' => 5, 'role' => 'restaurant']);
            $this->fail('Başka restoran yetkilisi stok değiştirememelidir.');
        } catch (RuntimeException $e) {
            $this->assertSame(403, $e->getCode());
        }

        // Gerçek restoran sahibi (#4) stoğu kapatır -> storage/menu_items.json kalıcı olarak güncellenir
        $toggled = $service->toggleMenuItemAvailability(1, ['id' => 4, 'role' => 'restaurant', 'fullName' => 'Lezzet Kebap'], false);
        $this->assertFalse($toggled['is_available']);
        $this->assertSame([1], $service->getOutOfStockMenuItemIds());

        // Gerçek denetim günlüğü diske yazılmış ve okunabiliyor olmalıdır
        $logs = $service->getAuditLogs();
        $this->assertNotEmpty($logs);
        $this->assertStringContainsString('Menü Stok Güncellemesi', $logs[0]['action']);
    }

    public function testCsrfValidationInMemoryParityAndWithLock(): void
    {
        Session::start();
        $token = Session::getCsrfToken();
        $this->assertTrue(Session::validateCsrfToken($token));
        $this->assertFalse(Session::validateCsrfToken(''));
        $this->assertFalse(Session::validateCsrfToken('invalid_token_xyz'));

        // InMemoryOrderRepository customer_phone & boş date='' paritesi
        $memRepo = new InMemoryOrderRepository();
        $memRepo->save(new Order(1, 1, 1, null, OrderStatus::ALINDI, 175.0, 40.9850, 29.0280, createdAt: date('Y-m-d H:i:s')));
        $details = $memRepo->getOrderWithDetails(1);
        $this->assertNotNull($details);
        $this->assertArrayHasKey('customer_phone', $details);
        $report = $memRepo->getDailyReport(1, '');
        $this->assertSame(date('Y-m-d'), $report['date']);
        $this->assertSame(1, $report['total_orders']);

        // JsonStorageHelper::withLock & Request::getJson() boş/scalar güvenliği
        $lockPath = $this->tmpDir . DIRECTORY_SEPARATOR . 'shared.lock';
        $res = JsonStorageHelper::withLock($lockPath, static fn(): string => 'locked_ok');
        $this->assertSame('locked_ok', $res);

        $req = new Request();
        $this->assertIsArray($req->getJson());
    }
}
