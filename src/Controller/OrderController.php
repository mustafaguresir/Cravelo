<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exception\InvalidOrderTransitionException;
use App\Repository\OrderRepositoryInterface;
use App\Repository\RestaurantRepositoryInterface;
use App\Repository\CourierRepositoryInterface;
use App\Service\OrderService;
use App\Service\OrderStateMachineService;
use App\Service\CourierAssignmentService;
use DomainException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Sipariş HTTP Controller'ı.
 * Katman kuralı gereği doğrudan Repository kullanmaz; tüm iş mantığını OrderService'e devreder.
 */
final class OrderController extends Controller
{
    private OrderService $orderService;

    /**
     * Dependency Inversion Principle uyumlu esnek kurucu metot.
     * Hem yeni OrderService enjeksiyonunu, hem Router'ın parametresiz çağrısını,
     * hem de mevcut testlerin ($sm, $cas, $oRepo, $cRepo, $rRepo) çağrısını destekler.
     */
    public function __construct(
        OrderService|OrderStateMachineService|OrderRepositoryInterface|null $arg1 = null,
        CourierAssignmentService|RestaurantRepositoryInterface|null $arg2 = null,
        OrderRepositoryInterface|CourierRepositoryInterface|null $arg3 = null,
        CourierRepositoryInterface|OrderStateMachineService|null $arg4 = null,
        RestaurantRepositoryInterface|CourierAssignmentService|null $arg5 = null
    ) {
        if ($arg1 instanceof OrderService) {
            $this->orderService = $arg1;
            return;
        }

        $stateMachine = null;
        $assignmentService = null;
        $orderRepo = null;
        $courierRepo = null;
        $restaurantRepo = null;

        foreach ([$arg1, $arg2, $arg3, $arg4, $arg5] as $arg) {
            if ($arg === null) {
                continue;
            }
            if ($arg instanceof OrderStateMachineService) {
                $stateMachine = $arg;
            } elseif ($arg instanceof CourierAssignmentService) {
                $assignmentService = $arg;
            } elseif ($arg instanceof OrderRepositoryInterface) {
                $orderRepo = $arg;
            } elseif ($arg instanceof CourierRepositoryInterface) {
                $courierRepo = $arg;
            } elseif ($arg instanceof RestaurantRepositoryInterface) {
                $restaurantRepo = $arg;
            }
        }

        $this->orderService = new OrderService(
            orderRepo: $orderRepo,
            restaurantRepo: $restaurantRepo,
            courierRepo: $courierRepo,
            stateMachine: $stateMachine,
            assignmentService: $assignmentService
        );
    }

    /**
     * Sipariş Oluşturma (Unit Test & Core Logic Uyumlu)
     *
     * @param array<int, mixed> $items
     * @return array<string, mixed>
     */
    public function createOrder(
        int $customerId,
        int $restaurantId,
        float $totalAmount,
        int $delX,
        int $delY,
        array $items = []
    ): array {
        return $this->orderService->createOrder($customerId, $restaurantId, $totalAmount, $delX, $delY, $items);
    }

    /**
     * Sipariş Oluşturma (POST /orders -> PRG Flow)
     */
    public function create(Request $request): void
    {
        if (!Session::has('user')) {
            Session::setFlash('warning', 'Sipariş verebilmek için lütfen giriş yapınız.');
            Response::redirect($request->url('/login'), 303);
            return;
        }

        $user = (array)Session::get('user');
        $restaurantId = (int)$request->post('restaurant_id', 1);
        $totalAmount = (float)$request->post('total_amount', 0.0);
        $delX = (int)$request->post('del_x', 18);
        $delY = (int)$request->post('del_y', 14);

        try {
            $result = $this->orderService->createOrder((int)$user['id'], $restaurantId, $totalAmount, $delX, $delY);
            $orderId = (int)$result['order_id'];
            Session::setFlash('success', "Siparişiniz #{$orderId} başarıyla alındı! Kuryeniz hazırlanıyor.");
            Response::redirect($request->url('/'), 303);
        } catch (InvalidArgumentException $e) {
            Session::setFlash('danger', 'Sepetiniz boş olduğu için sipariş oluşturulamadı.');
            Response::redirect($request->url('/'), 303);
        } catch (\Throwable $e) {
            error_log('[OrderController::create] ' . $e->getMessage());
            Session::setFlash('danger', 'Sipariş oluşturulurken beklenmeyen bir hata oluştu.');
            Response::redirect($request->url('/'), 303);
        }
    }

    /**
     * Durum Makinesini İlerletme (HTTP & Unit Test Uyumlu)
     *
     * @return array<string, mixed>|null
     */
    public function changeStatus(int|Request $orderId, ?string $status = null, ?string $role = null): ?array
    {
        $isHttp = ($orderId instanceof Request);
        $actorUserId = null;

        if ($isHttp) {
            $request = $orderId;
            $body = $request->all();
            $actualOrderId = (int)($body['order_id'] ?? $request->get('order_id') ?? 0);
            $status = (string)($body['target_status'] ?? $body['status'] ?? $request->get('status') ?? '');
            $user = (array)Session::get('user', []);
            $role = (string)($user['role'] ?? 'guest');
            $actorUserId = isset($user['id']) ? (int)$user['id'] : null;
        } else {
            $actualOrderId = (int)$orderId;
            $role = $role ?? 'admin';
        }

        try {
            $res = $this->orderService->transitionStatus($actualOrderId, (string)$status, $role, $actorUserId);
            if ($isHttp) {
                Response::json($res);
                return null;
            }
            return $res;
        } catch (DomainException $e) {
            if ($isHttp) {
                $code = $e->getCode() === 403 ? 403 : 400;
                Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
                return null;
            }
            throw $e;
        } catch (RuntimeException $e) {
            if ($isHttp) {
                $code = in_array($e->getCode(), [401, 403, 404], true) ? $e->getCode() : 400;
                Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
                return null;
            }
            throw $e;
        } catch (\Throwable $e) {
            error_log('[OrderController::changeStatus] ' . $e->getMessage());
            if ($isHttp) {
                Response::json(['status' => 'error', 'message' => 'Durum güncellenirken beklenmeyen bir hata oluştu.'], 500);
                return null;
            }
            throw $e;
        }
    }

    /**
     * Sipariş İptali (Yalnızca ALINDI aşamasında izin verilir, hazırlık/pişme aşamasında HTTP 400 döner)
     *
     * @return array<string, mixed>|null
     */
    public function cancelOrder(int|Request $orderId): ?array
    {
        $isHttp = ($orderId instanceof Request);
        if ($isHttp) {
            $request = $orderId;
            $body = $request->all();
            $actualOrderId = (int)($body['order_id'] ?? $body['id'] ?? 0);
            $sessionUser = Session::has('user') ? (array)Session::get('user') : null;
        } else {
            $actualOrderId = (int)$orderId;
            $sessionUser = null;
        }

        try {
            $res = $this->orderService->cancelOrder($actualOrderId, $sessionUser);
            if ($isHttp) {
                Response::json($res);
                return null;
            }
            return $res;
        } catch (InvalidOrderTransitionException|DomainException $e) {
            if ($isHttp) {
                Response::json(['status' => 'error', 'message' => $e->getMessage()], 400);
                return null;
            }
            throw $e;
        } catch (RuntimeException $e) {
            if ($isHttp) {
                $code = in_array($e->getCode(), [401, 403, 404], true) ? $e->getCode() : 400;
                Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
                return null;
            }
            throw $e;
        } catch (\Throwable $e) {
            error_log('[OrderController::cancelOrder] ' . $e->getMessage());
            if ($isHttp) {
                Response::json(['status' => 'error', 'message' => 'İptal işlemi sırasında beklenmeyen bir hata oluştu.'], 500);
                return null;
            }
            throw $e;
        }
    }

    /**
     * Günlük Restoran Satış Özeti (Unit Test & Rapor Uyumlu)
     *
     * @return array<string, mixed>
     */
    public function dailySummary(int $restaurantId, ?string $date = null): array
    {
        return $this->orderService->getDailyReport($restaurantId, $date ?? date('Y-m-d'));
    }

    /**
     * Sipariş Detayı ve IDOR Koruması (GET /orders/{id})
     */
    public function show(Request $request, string $id): void
    {
        if (!Session::has('user')) {
            Session::setFlash('warning', 'Lütfen önce giriş yapınız.');
            Response::redirect($request->url('/login'), 303);
            return;
        }

        $user = (array)Session::get('user');

        try {
            $payload = $this->orderService->getOrderStatusForUser((int)$id, $user);
            Response::json([
                'status' => 'success',
                'order' => [
                    'id' => $payload['order_id'],
                    'status' => $payload['order_status'],
                    'total_amount' => $payload['total_amount'],
                    'courier' => $payload['courier']
                ]
            ]);
        } catch (RuntimeException $e) {
            if ($e->getCode() === 404) {
                Response::html(View::render('errors/404', ['message' => 'Sipariş bulunamadı.'], 'layouts/auth'), 404);
                return;
            }
            if ($e->getCode() === 403) {
                Response::html(View::render('errors/403', ['message' => 'Yetkisiz erişim! Bu sipariş size ait değildir.'], 'layouts/auth'), 403);
                return;
            }
            error_log('[OrderController::show] ' . $e->getMessage());
            Response::html(View::render('errors/500', [], 'layouts/auth'), 500);
        }
    }
}
