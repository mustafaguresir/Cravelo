<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Model\Order;
use App\Model\Courier;
use App\Model\Restaurant;
use App\Enum\OrderStatus;
use App\Enum\CourierStatus;
use App\Exception\NoAvailableCourierException;
use App\Service\HaversineDistanceService;
use App\Service\CourierAssignmentService;
use App\Service\Courier\ScoreBasedAssignmentStrategy;
use App\Repository\InMemory\InMemoryCourierRepository;

/**
 * Gereksinim Eşleştirmesi:
 * - FR-04: Haversine küresel mesafe formülü (R = 6371 km)
 * - FR-05 & FR-06: Kurye puanlama (Mesafe + 2 * Aktif) ve iş yükü optimizasyonu
 * - FR-07: Boşta kurye bulunamaması (NoAvailableCourierException)
 * - FR-11: Atama sonrası iş yükü artırımı ve eşitlik kırıcı (tie-breaker)
 */
final class CourierAssignmentStrategyTest extends TestCase
{
    private CourierAssignmentService $assignmentService;
    private HaversineDistanceService $haversine;
    private ScoreBasedAssignmentStrategy $strategy;
    private InMemoryCourierRepository $courierRepo;

    protected function setUp(): void
    {
        $this->haversine = new HaversineDistanceService();
        $this->strategy = new ScoreBasedAssignmentStrategy($this->haversine);
        $this->courierRepo = new InMemoryCourierRepository();
        $this->assignmentService = new CourierAssignmentService(
            $this->courierRepo,
            null,
            $this->strategy,
            $this->haversine
        );
    }

    public function testHaversineDistanceCalculationIsAccurate(): void
    {
        // Kadıköy Moda (40.9850 N, 29.0280 E) ile Kadıköy Rıhtım (40.9910 N, 29.0250 E) arası: 0.71 km
        $distance = $this->haversine->calculate(40.9850, 29.0280, 40.9910, 29.0250);

        $this->assertGreaterThan(0.5, $distance);
        $this->assertLessThan(1.5, $distance);
        $this->assertEqualsWithDelta(0.71, $distance, 0.05);
    }

    public function testLowerWorkloadCourierIsSelectedOverCloserBusyCourier(): void
    {
        $restaurant = new Restaurant(1, 4, 'Moda Kebap', 10, 15, '02163334455', true, 40.9850, 29.0280, 'Moda');
        $order = new Order(1, 1, 1, null, OrderStatus::HAZIR, 200.0, 40.9850, 29.0280);

        // Kurye 1 (Ali): Moda Rıhtım'da (0.71 km uzakta) ancak 2 aktif siparişi var -> Skor = 0.71 + (2*2) = 4.71
        $courier1 = new Courier(1, 7, 'Ali (Yakın ama Meşgul)', 15, 20, 2, CourierStatus::MUSAIT, 40.9910, 29.0250);

        // Kurye 2 (Veli): Fenerbahçe'de (1.31 km uzakta) ancak 0 aktif siparişi var -> Skor = 1.31 + (2*0) = 1.31
        $courier2 = new Courier(2, 8, 'Veli (Uzak ama Boşta)', 8, 10, 0, CourierStatus::MUSAIT, 40.9760, 29.0380);

        $best = $this->assignmentService->assignBestCourier($order, $restaurant, [$courier1, $courier2]);

        $this->assertSame(2, $best->id, 'Formül gereğince daha uzakta olmasına rağmen iş yükü boş olan Kurye Veli seçilmelidir!');
        $this->assertEqualsWithDelta(4.71, $courier1->score, 0.05);
        $this->assertEqualsWithDelta(1.31, $courier2->score, 0.05);
    }

    public function testEmptyCandidatesListThrowsException(): void
    {
        $restaurant = new Restaurant(1, 4, 'Moda Kebap', 10, 15, '02163334455', true, 40.9850, 29.0280, 'Moda');
        $order = new Order(2, 1, 1, null, OrderStatus::HAZIR, 150.0, 40.9850, 29.0280);

        $this->expectException(NoAvailableCourierException::class);
        $this->expectExceptionMessage("Uygun kurye bulunamadı!");

        $this->assignmentService->assignBestCourier($order, $restaurant, []);
    }

    public function testSameDistanceDifferentWorkloadSelectsIdleCourier(): void
    {
        $restaurant = new Restaurant(1, 4, 'Merkez Restoran', 10, 10, '02161111111', true, 40.9800, 29.0300);
        $order = new Order(3, 1, 1, null, OrderStatus::HAZIR, 100.0, 40.9800, 29.0300);

        $cBusy = new Courier(10, 1, 'Meşgul Kurye', 10, 10, 3, CourierStatus::MUSAIT, 40.9850, 29.0350);
        $cIdle = new Courier(11, 2, 'Boş Kurye', 10, 10, 0, CourierStatus::MUSAIT, 40.9850, 29.0350);

        $selected = $this->assignmentService->assignBestCourier($order, $restaurant, [$cBusy, $cIdle]);
        $this->assertSame(11, $selected->id);
    }

    public function testEqualScorePrefersLowerActiveOrders(): void
    {
        // Restoran (40.9850, 29.0280):
        // Kurye A: aynı konumda (0.00 km) ama 1 aktif siparişi var -> Skor = 0.00 + 2.00 = 2.00
        // Kurye B: tam 2.00 km uzakta ama 0 aktif siparişi var -> Skor = 2.00 + 0.00 = 2.00
        // Eşitlik kuralı (tie-breaker): aktif siparişi daha az olan Kurye B seçilmelidir!
        $restaurant = new Restaurant(1, 4, 'Moda Kebap', 10, 15, '02163334455', true, 40.9850, 29.0280);
        $order = new Order(4, 1, 1, null, OrderStatus::HAZIR, 180.0, 40.9850, 29.0280);

        $cWithLoad = new Courier(20, 7, 'Yüklü Kurye', 10, 15, 1, CourierStatus::MUSAIT, 40.9850, 29.0280);
        $cZeroLoad = new Courier(21, 8, 'Boş Kurye', 10, 25, 0, CourierStatus::MUSAIT, 41.002986, 29.0280);

        $selected = $this->assignmentService->assignBestCourier($order, $restaurant, [$cWithLoad, $cZeroLoad]);
        $this->assertSame(21, $selected->id, 'Skor eşitliğinde aktif siparişi daha az olan kurye seçilmelidir!');
    }

    public function testWorkloadIncrement(): void
    {
        $courier = new Courier(30, 9, 'Can Öz', 10, 15, 1, CourierStatus::MUSAIT, 40.9850, 29.0280);
        $this->courierRepo->save($courier);

        $this->courierRepo->incrementActiveOrders(30);
        $updated = $this->courierRepo->findById(30);
        $this->assertNotNull($updated);
        $this->assertSame(2, $updated->activeOrdersCount);

        $this->courierRepo->decrementActiveOrders(30);
        $afterDelivery = $this->courierRepo->findById(30);
        $this->assertNotNull($afterDelivery);
        $this->assertSame(1, $afterDelivery->activeOrdersCount);
    }
}
