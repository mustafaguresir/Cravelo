<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Enum\UserRole;
use App\Exception\InvalidOrderTransitionException;
use App\Exception\NoAvailableCourierException;
use App\Repository\CourierRepositoryInterface;
use App\Repository\Json\JsonUserRepository;
use App\Repository\OrderRepositoryInterface;
use App\Repository\RestaurantRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\AuthService;
use App\Service\CourierAssignmentService;
use App\Service\HaversineDistanceService;
use App\Service\OrderService;
use App\Service\OrderStateMachineService;
use DomainException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Tek Giriş Noktalı (Front Controller) AJAX & JSON API Controller'ı.
 * Doğrudan Repository çağırmaz; tüm işlemleri OrderService ve AuthService üzerinden yürütür.
 */
final class ApiController extends Controller
{
    private OrderService $orderService;
    private AuthService $authService;
    private HaversineDistanceService $haversine;

    public function __construct(
        ?OrderRepositoryInterface $orderRepo = null,
        ?RestaurantRepositoryInterface $restaurantRepo = null,
        ?CourierRepositoryInterface $courierRepo = null,
        ?UserRepositoryInterface $userRepo = null,
        ?OrderStateMachineService $stateMachine = null,
        ?CourierAssignmentService $assignmentService = null,
        ?OrderService $orderService = null,
        ?AuthService $authService = null
    ) {
        $resolvedUserRepo = $userRepo ?? new JsonUserRepository();
        $this->authService = $authService ?? new AuthService($resolvedUserRepo);
        $this->orderService = $orderService ?? new OrderService(
            orderRepo: $orderRepo,
            restaurantRepo: $restaurantRepo,
            courierRepo: $courierRepo,
            userRepo: $resolvedUserRepo,
            stateMachine: $stateMachine,
            assignmentService: $assignmentService,
            authService: $this->authService
        );
        $this->haversine = new HaversineDistanceService();
    }

    public function handle(Request $request): void
    {
        $action = (string)($request->get('action') ?? $request->post('action') ?? '');
        if ($action === '' && $request->isJson()) {
            $data = $request->getJson();
            $action = (string)($data['action'] ?? '');
        }

        $publicActions = ['health', 'ping', 'calculate_distance', 'login', 'register', 'logout'];
        if (!in_array($action, $publicActions, true) && !Session::has('user')) {
            Response::json([
                'status' => 'error',
                'message' => 'Yetkisiz erişim! Lütfen oturum açınız.'
            ], 401);
            return;
        }

        $user = Session::has('user') ? (array)Session::get('user') : [];

        switch ($action) {
            case 'health':
            case 'ping':
                Response::json([
                    'status' => 'success',
                    'stage' => 'Aşama 2: Veritabanısız Çalışan Prototip',
                    'storage_engine' => 'JSON Files (storage/*.json) with flock(LOCK_EX)',
                    'php_version' => PHP_VERSION
                ]);
                return;

            case 'calculate_distance':
                $body = $request->all();
                $lat1 = (float)($body['lat1'] ?? $request->get('lat1', 0));
                $lon1 = (float)($body['lon1'] ?? $request->get('lon1', 0));
                $lat2 = (float)($body['lat2'] ?? $request->get('lat2', 0));
                $lon2 = (float)($body['lon2'] ?? $request->get('lon2', 0));
                Response::json([
                    'status' => 'success',
                    'formula' => 'Haversine Great-Circle Distance',
                    'distance_km' => $this->haversine->calculate($lat1, $lon1, $lat2, $lon2)
                ]);
                return;

            case 'login':
                $body = $request->all();
                $logged = $this->authService->login((string)($body['username'] ?? ''), (string)($body['password'] ?? ''));
                if ($logged === null) {
                    Response::json(['status' => 'error', 'message' => 'Hatalı kullanıcı adı veya parola!'], 401);
                    return;
                }
                Session::set('user', [
                    'id' => $logged->id,
                    'username' => $logged->username,
                    'role' => $logged->role->value,
                    'fullName' => $logged->fullName,
                    'phone' => $logged->phone
                ]);
                Response::json([
                    'status' => 'success',
                    'message' => 'Giriş başarılı!',
                    'user' => Session::get('user')
                ]);
                return;

            case 'register':
                $body = $request->all();
                try {
                    $created = $this->authService->register(
                        (string)($body['username'] ?? ''),
                        (string)($body['password'] ?? ''),
                        (string)($body['full_name'] ?? ''),
                        (string)($body['phone'] ?? ''),
                        UserRole::CUSTOMER
                    );
                    Response::json(['status' => 'success', 'message' => 'Kayıt oluşturuldu.', 'user_id' => $created->id], 201);
                } catch (InvalidArgumentException $e) {
                    Response::json(['status' => 'error', 'message' => $e->getMessage()], 400);
                }
                return;

            case 'logout':
                Session::destroy();
                Response::json(['status' => 'success', 'message' => 'Çıkış yapıldı.']);
                return;

            case 'me':
                Response::json(['status' => 'authenticated', 'user' => $user]);
                return;

            case 'get_order':
            case 'get_order_status':
                $this->getOrderStatus($request, $user);
                return;

            case 'get_kds_orders':
                $this->getKdsOrders($request, $user);
                return;

            case 'get_courier_tasks':
                $this->getCourierTasks($request, $user);
                return;

            case 'change_status':
                $this->changeStatus($request, $user);
                return;

            case 'cancel_order':
                $this->cancelOrder($request, $user);
                return;

            case 'create_order':
                $this->createOrder($request, $user);
                return;

            case 'fleet_scores':
                $this->getFleetScores($request, $user);
                return;

            case 'daily_report':
                $body = $request->all();
                $restId = (int)($body['restaurant_id'] ?? 1);
                $date = (string)($body['date'] ?? date('Y-m-d'));
                Response::json(['status' => 'success', 'report' => $this->orderService->getDailyReport($restId, $date)]);
                return;

            case 'run_proofs':
                Response::json(['status' => 'success', 'proofs' => $this->orderService->runCoreProofs()]);
                return;

            default:
                Response::json([
                    'status' => 'error',
                    'message' => 'Bilinmeyen API aksiyonu: ' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8')
                ], 400);
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    private function getOrderStatus(Request $request, array $user): void
    {
        $body = $request->all();
        $orderId = (int)($body['id'] ?? $body['order_id'] ?? 0);
        try {
            Response::json($this->orderService->getOrderStatusForUser($orderId, $user));
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), [401, 403, 404], true) ? $e->getCode() : 400;
            Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
        } catch (\Throwable $e) {
            error_log('[ApiController::getOrderStatus] ' . $e->getMessage());
            Response::json(['status' => 'error', 'message' => 'Sipariş durumu alınırken hata oluştu.'], 500);
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    private function getKdsOrders(Request $request, array $user): void
    {
        try {
            $orders = $this->orderService->getKdsOrdersForUser($user);
            Response::json(['status' => 'success', 'orders' => $orders, 'count' => count($orders)]);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), [401, 403, 404], true) ? $e->getCode() : 400;
            Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
        } catch (\Throwable $e) {
            error_log('[ApiController::getKdsOrders] ' . $e->getMessage());
            Response::json(['status' => 'error', 'message' => 'Mutfak siparişleri alınırken hata oluştu.'], 500);
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    private function getCourierTasks(Request $request, array $user): void
    {
        try {
            $tasks = $this->orderService->getCourierTasksForUser($user);
            Response::json(['status' => 'success', 'tasks' => $tasks, 'count' => count($tasks)]);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), [401, 403, 404], true) ? $e->getCode() : 400;
            Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
        } catch (\Throwable $e) {
            error_log('[ApiController::getCourierTasks] ' . $e->getMessage());
            Response::json(['status' => 'error', 'message' => 'Kurye görevleri alınırken hata oluştu.'], 500);
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    private function changeStatus(Request $request, array $user): void
    {
        $body = $request->all();
        $orderId = (int)($body['order_id'] ?? 0);
        $targetStatusStr = (string)($body['target_status'] ?? $body['status'] ?? '');

        try {
            // Güvenlik: Rol ve kullanıcı kimliği DAİMA sunucu oturumundan alınır (role spoofing engeli)
            $res = $this->orderService->transitionStatus(
                $orderId,
                $targetStatusStr,
                (string)($user['role'] ?? 'guest'),
                isset($user['id']) ? (int)$user['id'] : null
            );
            Response::json($res);
        } catch (InvalidOrderTransitionException|DomainException $e) {
            $code = $e->getCode() === 403 ? 403 : 400;
            Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
        } catch (NoAvailableCourierException $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 409);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), [401, 403, 404], true) ? $e->getCode() : 400;
            Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
        } catch (\Throwable $e) {
            error_log('[ApiController::changeStatus] ' . $e->getMessage());
            Response::json(['status' => 'error', 'message' => 'Sipariş durumu güncellenirken hata oluştu.'], 500);
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    private function cancelOrder(Request $request, array $user): void
    {
        $body = $request->all();
        $orderId = (int)($body['order_id'] ?? $body['id'] ?? 0);
        try {
            Response::json($this->orderService->cancelOrder($orderId, $user));
        } catch (InvalidOrderTransitionException|DomainException $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), [401, 403, 404], true) ? $e->getCode() : 400;
            Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
        } catch (\Throwable $e) {
            error_log('[ApiController::cancelOrder] ' . $e->getMessage());
            Response::json(['status' => 'error', 'message' => 'Sipariş iptal edilirken hata oluştu.'], 500);
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    private function createOrder(Request $request, array $user): void
    {
        $body = $request->all();
        $restaurantId = (int)($body['restaurant_id'] ?? 1);
        $totalAmount = (float)($body['total_amount'] ?? 0.0);
        $delX = (int)($body['del_x'] ?? 18);
        $delY = (int)($body['del_y'] ?? 14);
        $items = (array)($body['items'] ?? []);

        try {
            // Güvenlik: customerId DAİMA oturum açan kullanıcıdan alınır
            $res = $this->orderService->createOrder(
                (int)$user['id'],
                $restaurantId,
                $totalAmount,
                $delX,
                $delY,
                $items,
                (float)($body['delivery_latitude'] ?? 40.9850),
                (float)($body['delivery_longitude'] ?? 29.0280)
            );
            $res['message'] = 'Siparişiniz başarıyla alındı!';
            Response::json($res, 201);
        } catch (InvalidArgumentException $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (NoAvailableCourierException $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            error_log('[ApiController::createOrder] ' . $e->getMessage());
            Response::json(['status' => 'error', 'message' => 'Sipariş oluşturulurken hata oluştu.'], 500);
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    private function getFleetScores(Request $request, array $user): void
    {
        if (($user['role'] ?? '') !== 'admin') {
            Response::json(['status' => 'error', 'message' => 'Yalnızca yöneticiler filo puanlarını görebilir.'], 403);
            return;
        }

        Response::json(['status' => 'success', 'fleet' => $this->orderService->getFleetScores()]);
    }

    public function handleOrderStatus(Request $request): void
    {
        if (!Session::has('user')) {
            Response::json(['status' => 'error', 'message' => 'Yetkisiz erişim! Lütfen oturum açınız.'], 401);
            return;
        }
        $this->getOrderStatus($request, (array)Session::get('user'));
    }

    public function handleKdsOrders(Request $request): void
    {
        if (!Session::has('user')) {
            Response::json(['status' => 'error', 'message' => 'Yetkisiz erişim! Lütfen oturum açınız.'], 401);
            return;
        }
        $this->getKdsOrders($request, (array)Session::get('user'));
    }

    public function handleCourierTasks(Request $request): void
    {
        if (!Session::has('user')) {
            Response::json(['status' => 'error', 'message' => 'Yetkisiz erişim! Lütfen oturum açınız.'], 401);
            return;
        }
        $this->getCourierTasks($request, (array)Session::get('user'));
    }

    public function handleAuditLogs(Request $request): void
    {
        if (!Session::has('user')) {
            Response::json(['status' => 'error', 'message' => 'Yetkisiz erişim! Lütfen oturum açınız.'], 401);
            return;
        }
        $user = (array)Session::get('user');
        if (($user['role'] ?? '') !== 'admin') {
            Response::json(['status' => 'error', 'message' => 'Denetim kayıtlarına sadece yöneticiler erişebilir!'], 403);
            return;
        }

        Response::json([
            'status' => 'success',
            'audit_logs' => $this->orderService->getAuditLogs()
        ]);
    }
}
