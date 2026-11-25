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
}
