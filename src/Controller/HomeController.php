<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Repository\CourierRepositoryInterface;
use App\Repository\OrderRepositoryInterface;
use App\Repository\RestaurantRepositoryInterface;
use App\Service\OrderService;

/**
 * Ana Sayfa & Rol Bazlı Dashboard Controller'ı.
 * Katman kuralı gereği doğrudan Repository çağırmaz; tüm veri erişimini OrderService'e devreder.
 */
final class HomeController extends Controller
{
    private OrderService $orderService;

    public function __construct(
        ?RestaurantRepositoryInterface $restaurantRepo = null,
        ?OrderRepositoryInterface $orderRepo = null,
        ?CourierRepositoryInterface $courierRepo = null,
        ?OrderService $orderService = null
    ) {
        $this->orderService = $orderService ?? new OrderService(
            orderRepo: $orderRepo,
            restaurantRepo: $restaurantRepo,
            courierRepo: $courierRepo
        );
    }

    public function index(Request $request): void
    {
        // Şartname R1 & R2: Oturum açmamış ziyaretçiyi karşılama (landing / auth gate) ekranı karşılar
        if (!Session::has('user')) {
            $guestData = $this->orderService->getGuestLandingData();

            $this->render('landing', [
                'pageTitle' => 'Cravelo — Hoş Geldiniz | Akıllı Yemek & Kurye Sistemi',
                'restaurants' => $guestData['restaurants'],
                'couriers' => $guestData['couriers'],
            ], 'layouts/main');
            return;
        }

        $user = (array)Session::get('user');
        $dashboardData = $this->orderService->getDashboardDataForUser($user);

        // Plan Section 2 & 3.C.2: Role göre özelleşmiş dashboard görünümü yüklenir
        $role = (string)($user['role'] ?? 'customer');
        $viewMap = [
            'customer' => 'dashboard/customer',
            'restaurant' => 'dashboard/restaurant',
            'courier' => 'dashboard/courier',
            'admin' => 'dashboard/admin',
        ];
        $targetView = $viewMap[$role] ?? 'dashboard/customer';

        $serializedOrders = $this->orderService->formatOrdersForFrontend(
            $dashboardData['orders'],
            $dashboardData['restaurants'],
            $dashboardData['couriers']
        );

        $ownedRestaurantIds = [];
        if ($role === 'restaurant') {
            $userId = (int)($user['id'] ?? 0);
            foreach ($dashboardData['restaurants'] as $rest) {
                if ($rest->userId === $userId) {
                    $ownedRestaurantIds[] = $rest->id;
                }
            }
        }
        $outOfStockItemIds = $this->orderService->getOutOfStockMenuItemIds();

        $this->render($targetView, [
            'pageTitle' => 'Cravelo — ' . ucfirst($role) . ' Paneli',
            'user' => $user,
            'restaurants' => $dashboardData['restaurants'],
            'couriers' => $dashboardData['couriers'],
            'orders' => $dashboardData['orders'],
            'serializedOrders' => $serializedOrders,
            'ownedRestaurantIds' => $ownedRestaurantIds,
            'outOfStockItemIds' => $outOfStockItemIds,
        ], 'layouts/main');
    }
}
