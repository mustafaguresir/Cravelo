<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controller\OrderController;
use App\Service\OrderService;
use App\Service\OrderStateMachineService;
use App\Service\CourierAssignmentService;
use App\Repository\InMemory\InMemoryOrderRepository;
use App\Repository\InMemory\InMemoryCourierRepository;
use App\Repository\InMemory\InMemoryRestaurantRepository;
use App\Model\Restaurant;
use App\Model\Courier;
use App\Enum\OrderStatus;
use App\Enum\CourierStatus;

/**
 * Gereksinim Eşleştirmesi:
 * - FR-01: Yeni siparişin 'alindi' durumunda başlaması
 * - FR-10: Çoklu nesne ilişkileri & sipariş yaşam döngüsü
 * - FR-13: Restoran günlük satış raporu agregasyonu
 */
final class OrderControllerTest extends TestCase
{
    private OrderController $controller;
    private InMemoryOrderRepository $orderRepo;
    private InMemoryCourierRepository $courierRepo;
    private InMemoryRestaurantRepository $restaurantRepo;
    private OrderStateMachineService $stateMachine;

    protected function setUp(): void
    {
        $this->orderRepo = new InMemoryOrderRepository();
        $this->courierRepo = new InMemoryCourierRepository();
        $this->restaurantRepo = new InMemoryRestaurantRepository();
        $this->stateMachine = new OrderStateMachineService($this->orderRepo);
        $assignmentService = new CourierAssignmentService();

        // 1 Restoran ekle (Kadıköy Moda: 40.9850, 29.0280)
        $rest = new Restaurant(
            id: 1,
            userId: 4,
            name: 'Moda Lezzet Kebap',
            locationX: 10,
            locationY: 15,
            phone: '02163334455',
            isActive: true,
            latitude: 40.9850,
            longitude: 29.0280,
            neighborhood: 'Kadıköy Moda'
        );
        $this->restaurantRepo->save($rest);

        // 2 Kurye ekle: Ali (0.71 km, 2 görev -> 4.71) vs Veli (1.31 km, 0 görev -> 1.31)
        $courier1 = new Courier(
            id: 1,
            userId: 7,
            name: 'Kurye Ali',
            currentX: 15,
            currentY: 20,
            activeOrdersCount: 2,
            status: CourierStatus::MUSAIT,
            latitude: 40.9910,
            longitude: 29.0250,
            neighborhood: 'Kadıköy Rıhtım'
        );
        $courier2 = new Courier(
            id: 2,
            userId: 8,
            name: 'Kurye Veli',
            currentX: 8,
            currentY: 10,
            activeOrdersCount: 0,
            status: CourierStatus::MUSAIT,
            latitude: 40.9760,
            longitude: 29.0380,
            neighborhood: 'Fenerbahçe'
        );
        $this->courierRepo->save($courier1);
        $this->courierRepo->save($courier2);

        $this->controller = new OrderController(
            $this->stateMachine,
            $assignmentService,
            $this->orderRepo,
            $this->courierRepo,
            $this->restaurantRepo
        );
    }

    public function testCreateOrderInitialStateIsAlindiAndAssignsCourier(): void
    {
        $res = $this->controller->createOrder(
            customerId: 1,
            restaurantId: 1,
            totalAmount: 250.0,
            delX: 18,
            delY: 14,
            items: []
        );

        $this->assertSame('success', $res['status']);
        $this->assertSame('alindi', $res['current_status']);
        $this->assertGreaterThan(0, $res['order_id']);

        $savedOrder = $this->orderRepo->findById($res['order_id']);
        $this->assertNotNull($savedOrder);
        $this->assertSame(OrderStatus::ALINDI, $savedOrder->status);
        $this->assertSame(1, $savedOrder->customerId);
        $this->assertSame(1, $savedOrder->restaurantId);
        $this->assertSame(250.0, $savedOrder->totalAmount);
        $this->assertSame(18, $savedOrder->deliveryX);
        $this->assertSame(14, $savedOrder->deliveryY);

        // Boşta olan Kurye Veli (id: 2, skor 1.31 < 4.71) atanmış olmalıdır
        $this->assertSame(2, $savedOrder->courierId);
    }

    public function testOrderLifecycleStateTransitions(): void
    {
        $res = $this->controller->createOrder(1, 1, 150.0, 18, 14);
        $orderId = (int)$res['order_id'];

        // Restoran mutfağa alır: alindi -> hazirlaniyor
        $resMut = $this->controller->changeStatus($orderId, 'hazirlaniyor', 'restaurant');
        $this->assertIsArray($resMut);
        $this->assertSame('success', $resMut['status']);
        $this->assertSame('hazirlaniyor', $resMut['current_status']);

        // Mutfakta hazır: hazirlaniyor -> hazir
        $resHazir = $this->controller->changeStatus($orderId, 'hazir', 'restaurant');
        $this->assertIsArray($resHazir);
        $this->assertSame('hazir', $resHazir['current_status']);

        // Kurye teslim alır: hazir -> yolda
        $resYolda = $this->controller->changeStatus($orderId, 'yolda', 'courier');
        $this->assertIsArray($resYolda);
        $this->assertSame('yolda', $resYolda['current_status']);

        // Kurye teslim eder: yolda -> teslim_edildi
        $resTeslim = $this->controller->changeStatus($orderId, 'teslim_edildi', 'courier');
        $this->assertIsArray($resTeslim);
        $this->assertSame('teslim_edildi', $resTeslim['current_status']);
    }

    public function testDailySummaryCalculation(): void
    {
        $this->controller->createOrder(1, 1, 220.50, 18, 14);
        $this->controller->createOrder(2, 1, 179.50, 16, 12);
        $cancelled = $this->controller->createOrder(3, 1, 500.00, 14, 10);
        $this->controller->cancelOrder((int)$cancelled['order_id']);

        $summary = $this->controller->dailySummary(1, date('Y-m-d'));

        // İptal edilen sipariş günlük ciro ve sipariş adedine dahil edilmemelidir
        $this->assertSame(2, $summary['total_orders']);
        $this->assertEqualsWithDelta(400.0, (float)$summary['total_revenue'], 0.01);
    }

    public function testCustomerPersistedOrdersAreReturnedScopedToOwner(): void
    {
        $service = new OrderService(
            orderRepo: $this->orderRepo,
            restaurantRepo: $this->restaurantRepo,
            courierRepo: $this->courierRepo,
            stateMachine: $this->stateMachine
        );

        $service->createOrder(
            customerId: 1,
            restaurantId: 1,
            totalAmount: 195.0,
            delX: 18,
            delY: 14,
            items: [['name' => 'Adana Kebap Dürüm', 'quantity' => 1, 'price' => 195.0]]
        );
        $service->createOrder(
            customerId: 2,
            restaurantId: 1,
            totalAmount: 260.0,
            delX: 16,
            delY: 12,
            items: [['name' => 'Urfa Kebap', 'quantity' => 1, 'price' => 260.0]]
        );

        // Müşteri 1 yeniden giriş yaptığında sadece kendi siparişini görmelidir
        $cust1Orders = $service->getFormattedOrdersForUser(['id' => 1, 'role' => 'customer']);
        $this->assertCount(1, $cust1Orders);
        $this->assertSame(1, $cust1Orders[0]['customer_id']);
        $this->assertSame('alindi', $cust1Orders[0]['status']);
        $this->assertSame('Adana Kebap Dürüm', $cust1Orders[0]['items'][0]['name']);
        $this->assertSame('Kurye Veli', $cust1Orders[0]['courier']);

        // Müşteri 2 kendi siparişini görür, Müşteri 1'in siparişini asla göremez (IDOR izolasyonu)
        $cust2Orders = $service->getFormattedOrdersForUser(['id' => 2, 'role' => 'customer']);
        $this->assertCount(1, $cust2Orders);
        $this->assertSame(2, $cust2Orders[0]['customer_id']);
        $this->assertSame('Urfa Kebap', $cust2Orders[0]['items'][0]['name']);
    }

    public function testDailyReportEnforcesRbacAndRestaurantOwnership(): void
    {
        $service = new OrderService(
            orderRepo: $this->orderRepo,
            restaurantRepo: $this->restaurantRepo,
            courierRepo: $this->courierRepo,
            stateMachine: $this->stateMachine
        );

        $service->createOrder(1, 1, 240.0, 18, 14, []);
        $today = date('Y-m-d');

        // 1. Müşteri başka restoranın günlük raporunu okuyamaz (403)
        try {
            $service->getDailyReportForUser(1, $today, ['id' => 1, 'role' => 'customer']);
            $this->fail('Müşteri günlük ciro raporuna erişememelidir.');
        } catch (\RuntimeException $e) {
            $this->assertSame(403, $e->getCode());
        }

        // 2. Kurye günlük ciro raporunu okuyamaz (403)
        try {
            $service->getDailyReportForUser(1, $today, ['id' => 7, 'role' => 'courier']);
            $this->fail('Kurye günlük ciro raporuna erişememelidir.');
        } catch (\RuntimeException $e) {
            $this->assertSame(403, $e->getCode());
        }

        // 3. Farklı bir restoran kullanıcısı (#5), #4 kullanıcısının restoranını okuyamaz (403)
        try {
            $service->getDailyReportForUser(1, $today, ['id' => 5, 'role' => 'restaurant']);
            $this->fail('Farklı restoran sahibi bu rapora erişememelidir.');
        } catch (\RuntimeException $e) {
            $this->assertSame(403, $e->getCode());
        }

        // 4. Restoranın gerçek sahibi (#4) kendi raporunu okuyabilir
        $ownerReport = $service->getDailyReportForUser(1, $today, ['id' => 4, 'role' => 'restaurant']);
        $this->assertSame(1, $ownerReport['total_orders']);
        $this->assertEqualsWithDelta(240.0, (float)$ownerReport['total_revenue'], 0.01);

        // 5. Sistem yöneticisi (admin) herhangi bir restoranın raporunu okuyabilir
        $adminReport = $service->getDailyReportForUser(1, $today, ['id' => 99, 'role' => 'admin']);
        $this->assertSame(1, $adminReport['total_orders']);
        $this->assertEqualsWithDelta(240.0, (float)$adminReport['total_revenue'], 0.01);
    }

    public function testCreateOrderRejectsUnknownOrInactiveRestaurantAndNegativeItemPrice(): void
    {
        $inactiveRest = new Restaurant(
            id: 2,
            userId: 5,
            name: 'Kapalı Mutfak',
            locationX: 12,
            locationY: 12,
            phone: '02160000000',
            isActive: false,
            latitude: 40.9860,
            longitude: 29.0290,
            neighborhood: 'Moda'
        );
        $this->restaurantRepo->save($inactiveRest);

        $service = new OrderService(
            orderRepo: $this->orderRepo,
            restaurantRepo: $this->restaurantRepo,
            courierRepo: $this->courierRepo,
            stateMachine: $this->stateMachine
        );

        try {
            $service->createOrder(1, 99999, 180.0, 18, 14, []);
            $this->fail('Var olmayan restoran ID ile sipariş oluşturulamamalıdır.');
        } catch (\InvalidArgumentException) {
            $this->assertCount(0, $this->orderRepo->findAll());
        }

        try {
            $service->createOrder(1, 2, 180.0, 18, 14, []);
            $this->fail('Pasif restoran için sipariş oluşturulamamalıdır.');
        } catch (\InvalidArgumentException) {
            $this->assertCount(0, $this->orderRepo->findAll());
        }

        try {
            $service->createOrder(1, 1, 180.0, 18, 14, [['name' => 'Hatalı Ürün', 'quantity' => 1, 'price' => -25.0]]);
            $this->fail('Negatif ürün fiyatı kabul edilmemelidir.');
        } catch (\InvalidArgumentException) {
            $this->assertCount(0, $this->orderRepo->findAll());
        }
    }

    public function testTakeawayOrderSkipsCourierAssignmentAndPreservesMode(): void
    {
        $service = new OrderService(
            orderRepo: $this->orderRepo,
            restaurantRepo: $this->restaurantRepo,
            courierRepo: $this->courierRepo,
            stateMachine: $this->stateMachine
        );

        $res = $service->createOrder(
            customerId: 1,
            restaurantId: 1,
            totalAmount: 160.0,
            delX: 18,
            delY: 14,
            items: [['name' => 'Adana Dürüm', 'quantity' => 1, 'price' => 160.0]],
            deliveryMode: 'takeaway'
        );

        $this->assertSame('takeaway', $res['delivery_mode']);
        $this->assertNull($res['assigned_courier']);

        $saved = $this->orderRepo->findById((int)$res['order_id']);
        $this->assertNotNull($saved);
        $this->assertNull($saved->courierId);
        $this->assertSame('takeaway', $saved->deliveryMode);

        // Kurye iş yükü artmamış olmalıdır
        $veli = $this->courierRepo->findById(2);
        $this->assertNotNull($veli);
        $this->assertSame(0, $veli->activeOrdersCount);

        $formatted = $service->getFormattedOrdersForUser(['id' => 1, 'role' => 'customer']);
        $this->assertSame('takeaway', $formatted[0]['deliveryMode']);
        $this->assertSame('Restorandan Gel-Al (Müşteri)', $formatted[0]['courier']);

        // Restoran Gel-Al siparişini alindi -> hazirlaniyor -> hazir -> teslim_edildi yapabilir ('yolda' adımına geçilemez)
        $orderId = (int)$res['order_id'];
        $s1 = $service->transitionStatus($orderId, 'hazirlaniyor', 'restaurant', 4);
        $this->assertSame('hazirlaniyor', $s1['current_status']);

        $s2 = $service->transitionStatus($orderId, 'hazir', 'restaurant', 4);
        $this->assertSame('hazir', $s2['current_status']);

        // Gel-Al siparişinde 'yolda' (kurye) aşaması reddedilmelidir
        try {
            $service->transitionStatus($orderId, 'yolda', 'admin', 10);
            $this->fail('Gel-Al siparişi yolda aşamasına geçememelidir.');
        } catch (\App\Exception\InvalidOrderTransitionException) {
            $this->assertTrue(true);
        }

        // Restoran doğrudan teslim_edildi olarak kapatabilir
        $s3 = $service->transitionStatus($orderId, 'teslim_edildi', 'restaurant', 4);
        $this->assertSame('teslim_edildi', $s3['current_status']);
    }

    public function testFleetScoresUseRealHaversineDistanceAndAtomicJsonUpdate(): void
    {
        $service = new OrderService(
            orderRepo: $this->orderRepo,
            restaurantRepo: $this->restaurantRepo,
            courierRepo: $this->courierRepo,
            stateMachine: $this->stateMachine
        );

        $scores = $service->getFleetScores();
        $this->assertCount(2, $scores);
        // Ali (0.71 km, 2 görev -> 4.71) vs Veli (1.31 km, 0 görev -> 1.31)
        $this->assertNotEquals(5.0, $scores[0]['score']);
        $this->assertNotEquals(1.0, $scores[1]['score']);
        $this->assertLessThan($scores[0]['score'], $scores[1]['score']);

        // Atomik JsonStorageHelper::update() testi
        $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cravelo_atomic_test_' . uniqid() . '.json';
        try {
            $id1 = \App\Storage\JsonStorageHelper::update($tmpFile, static function (array $rows): array {
                $rows[] = ['id' => 1, 'val' => 'a'];
                return ['data' => $rows, 'result' => 1];
            });
            $id2 = \App\Storage\JsonStorageHelper::update($tmpFile, static function (array $rows): array {
                $rows[] = ['id' => count($rows) + 1, 'val' => 'b'];
                return ['data' => $rows, 'result' => count($rows)];
            });
            $skipped = \App\Storage\JsonStorageHelper::update($tmpFile, static function (array $rows): array {
                return ['data' => [], 'result' => 'noop', 'write' => false];
            });

            $this->assertSame(1, $id1);
            $this->assertSame(2, $id2);
            $this->assertSame('noop', $skipped);
            $persisted = \App\Storage\JsonStorageHelper::read($tmpFile);
            $this->assertCount(2, $persisted);
        } finally {
            @unlink($tmpFile);
            @unlink($tmpFile . '.lock');
        }
    }
}
