<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Enum\OrderStatus;
use App\Model\Order;
use App\Model\OrderItem;
use App\Repository\Json\JsonOrderRepository;
use App\Storage\JsonStorageHelper;

/**
 * Gereksinim Eşleştirmesi:
 * - FR-08, FR-09, FR-10: JSON veri deposunda ilişkisel okuma ve atomik koşullu güncelleme
 * - FR-13: Günlük restoran satış raporu
 * İzole geçici dosyalarda çalışır; gerçek storage/*.json verilerine dokunmaz.
 */
final class JsonOrderRepositoryTest extends TestCase
{
    private string $tmpDir;
    private JsonOrderRepository $repo;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cravelo_repo_test_' . uniqid('', true);
        mkdir($this->tmpDir, 0777, true);

        $ordersFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'orders.json';
        $restsFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'restaurants.json';
        $usersFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'users.json';
        $couriersFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'couriers.json';

        JsonStorageHelper::write($restsFile, [
            ['id' => 1, 'user_id' => 4, 'name' => 'Moda Lezzet Kebap', 'location_x' => 10, 'location_y' => 15]
        ]);
        JsonStorageHelper::write($usersFile, [
            ['id' => 1, 'username' => 'ahmet_musteri', 'full_name' => 'Ahmet Yılmaz', 'phone' => '05551112233', 'role' => 'customer']
        ]);
        JsonStorageHelper::write($couriersFile, [
            ['id' => 2, 'user_id' => 8, 'name' => 'Veli Kurye (Fenerbahçe)', 'active_orders_count' => 0, 'status' => 'musait']
        ]);
        JsonStorageHelper::write($ordersFile, []);

        $this->repo = new JsonOrderRepository($ordersFile, $restsFile, $usersFile, $couriersFile);
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

    public function testFindByIdWithRelations(): void
    {
        $item = new OrderItem(1, 0, 1, 'Adana Kebap Dürüm', 2, 195.0, 390.0);
        $order = new Order(
            id: 0,
            customerId: 1,
            restaurantId: 1,
            courierId: 2,
            status: OrderStatus::ALINDI,
            totalAmount: 390.0,
            deliveryLatitude: 40.9860,
            deliveryLongitude: 29.0290,
            estimatedDeliveryTime: '25 dk',
            createdAt: '2026-11-25 12:00:00'
        );

        $orderId = $this->repo->saveWithTransaction($order, [$item]);
        $details = $this->repo->getOrderWithDetails($orderId);

        $this->assertNotNull($details);
        $this->assertSame('Ahmet Yılmaz', $details['customer_name']);
        $this->assertSame('Moda Lezzet Kebap', $details['restaurant_name']);
        $this->assertSame('Veli Kurye (Fenerbahçe)', $details['courier_name']);
        $this->assertCount(1, $details['items']);
        $this->assertSame('Adana Kebap Dürüm', $details['items'][0]['name']);
    }

    public function testConditionalStatusUpdatePreventsStaleOverwrite(): void
    {
        $order = new Order(0, 1, 1, 2, OrderStatus::ALINDI, 200.0, 40.9850, 29.0280, null, '2026-11-25 13:00:00');
        $orderId = $this->repo->save($order);

        $this->assertTrue($this->repo->updateStatusConditional($orderId, OrderStatus::ALINDI, OrderStatus::HAZIRLANIYOR));
        // Eski durum artık ALINDI değil; ikinci eşzamanlı güncelleme reddedilmelidir
        $this->assertFalse($this->repo->updateStatusConditional($orderId, OrderStatus::ALINDI, OrderStatus::IPTAL));
    }

    public function testDailyReportFiltersByRestaurantAndDate(): void
    {
        $o1 = new Order(0, 1, 1, 2, OrderStatus::TESLIM_EDILDI, 300.0, 40.9850, 29.0280, null, '2026-11-25 10:00:00');
        $o2 = new Order(0, 1, 1, 2, OrderStatus::TESLIM_EDILDI, 150.0, 40.9850, 29.0280, null, '2026-11-25 14:30:00');
        $oCancelled = new Order(0, 1, 1, 2, OrderStatus::IPTAL, 999.0, 40.9850, 29.0280, null, '2026-11-25 15:00:00');
        $oOld = new Order(0, 1, 1, 2, OrderStatus::TESLIM_EDILDI, 500.0, 40.9850, 29.0280, null, '2026-11-20 11:00:00');

        $this->repo->save($o1);
        $this->repo->save($o2);
        $this->repo->save($oCancelled);
        $this->repo->save($oOld);

        // İptal edilen ve eski tarihli siparişler günlük ciroya dahil edilmemelidir
        $report = $this->repo->getDailyReport(1, '2026-11-25');
        $this->assertSame(2, $report['total_orders']);
        $this->assertEqualsWithDelta(450.0, (float)$report['total_revenue'], 0.01);
    }
}
