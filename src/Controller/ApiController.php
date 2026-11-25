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
use App\Service\PlatformFeatureService;
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
    private PlatformFeatureService $featureService;
    private HaversineDistanceService $haversine;

    public function __construct(
        ?OrderRepositoryInterface $orderRepo = null,
        ?RestaurantRepositoryInterface $restaurantRepo = null,
        ?CourierRepositoryInterface $courierRepo = null,
        ?UserRepositoryInterface $userRepo = null,
        ?OrderStateMachineService $stateMachine = null,
        ?CourierAssignmentService $assignmentService = null,
        ?OrderService $orderService = null,
        ?AuthService $authService = null,
        ?PlatformFeatureService $featureService = null
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
        $this->featureService = $featureService ?? new PlatformFeatureService(
            orderRepo: $orderRepo,
            restaurantRepo: $restaurantRepo,
            courierRepo: $courierRepo,
            userRepo: $resolvedUserRepo,
            orderService: $this->orderService
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

        $postOnlyActions = [
            'login',
            'register',
            'logout',
            'create_order',
            'change_status',
            'cancel_order',
            'toggle_menu_item',
            'save_menu_item',
            'toggle_restaurant_active',
            'update_courier_location',
            'update_courier_status',
            'reassign_courier',
            'update_profile',
            'save_addresses',
            'submit_review',
            'send_message',
        ];
        if (in_array($action, $postOnlyActions, true) && $request->getMethod() !== 'POST') {
            Response::json([
                'status' => 'error',
                'message' => 'Bu işlem güvenlik gereği yalnızca HTTP POST yöntemiyle çağrılabilir.'
            ], 405);
            return;
        }

        $publicActions = ['health', 'ping', 'calculate_distance', 'login', 'register', 'logout', 'get_reviews'];
        if (!in_array($action, $publicActions, true) && !Session::has('user')) {
            Response::json([
                'status' => 'error',
                'message' => 'Yetkisiz erişim! Lütfen oturum açınız.'
            ], 401);
            return;
        }

        $csrfProtectedActions = [
            'create_order',
            'change_status',
            'cancel_order',
            'logout',
            'toggle_menu_item',
            'save_menu_item',
            'toggle_restaurant_active',
            'update_courier_location',
            'update_courier_status',
            'reassign_courier',
            'update_profile',
            'save_addresses',
            'submit_review',
            'send_message',
        ];
        if (Session::has('user') && in_array($action, $csrfProtectedActions, true)) {
            $bodyForCsrf = $request->all();
            $csrfToken = (string)($request->header('X-CSRF-Token', '') ?: ($bodyForCsrf['_csrf_token'] ?? ''));
            if (!Session::validateCsrfToken($csrfToken)) {
                Response::json([
                    'status' => 'error',
                    'message' => 'Güvenlik doğrulaması (CSRF) başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.'
                ], 403);
                return;
            }
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
                Session::regenerate();
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
                    'user' => Session::get('user'),
                    'csrf_token' => Session::getCsrfToken()
                ]);
                return;

            case 'register':
                $body = $request->all();
                $countryCode = trim((string)($body['country_code'] ?? '+90'));
                if ($countryCode === '') {
                    $countryCode = '+90';
                }
                $rawPhone = trim((string)($body['phone'] ?? ''));
                $phoneCheck = AuthController::validateAndNormalizePhone($countryCode, $rawPhone);
                if (!$phoneCheck['valid']) {
                    Response::json(['status' => 'error', 'message' => $phoneCheck['error']], 400);
                    return;
                }
                try {
                    $created = $this->authService->register(
                        (string)($body['username'] ?? ''),
                        (string)($body['password'] ?? ''),
                        (string)($body['full_name'] ?? ''),
                        $phoneCheck['phone'],
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

            case 'my_orders':
                Response::json([
                    'status' => 'success',
                    'orders' => $this->orderService->getFormattedOrdersForUser($user)
                ]);
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

            case 'toggle_menu_item':
                $this->toggleMenuItem($request, $user);
                return;

            case 'save_menu_item':
                $this->handleFeatureCall(fn(): array => $this->featureService->saveMenuItem($user, $request->all()));
                return;

            case 'toggle_restaurant_active':
                $body = $request->all();
                $restId = (int)($body['restaurant_id'] ?? $body['id'] ?? 0);
                $desired = array_key_exists('is_active', $body) ? (bool)$body['is_active'] : null;
                $this->handleFeatureCall(fn(): array => $this->featureService->toggleRestaurantActive($user, $restId, $desired));
                return;

            case 'update_courier_location':
                $body = $request->all();
                $this->handleFeatureCall(fn(): array => $this->featureService->updateCourierLocation(
                    $user,
                    (int)($body['courier_id'] ?? 0),
                    (int)($body['x'] ?? 14),
                    (int)($body['y'] ?? 10),
                    (float)($body['latitude'] ?? $body['lat'] ?? 40.9850),
                    (float)($body['longitude'] ?? $body['lon'] ?? 29.0280),
                    (string)($body['neighborhood'] ?? '')
                ));
                return;

            case 'update_courier_status':
                $body = $request->all();
                $this->handleFeatureCall(fn(): array => $this->featureService->updateCourierStatus(
                    $user,
                    (int)($body['courier_id'] ?? 0),
                    (string)($body['status'] ?? 'musait')
                ));
                return;

            case 'reassign_courier':
                $body = $request->all();
                $this->handleFeatureCall(fn(): array => $this->featureService->reassignOrderCourier(
                    $user,
                    (int)($body['order_id'] ?? 0),
                    (int)($body['courier_id'] ?? 0)
                ));
                return;

            case 'admin_analytics':
                $body = $request->all();
                $dateParam = isset($body['date']) && is_string($body['date']) && trim($body['date']) !== '' ? trim($body['date']) : null;
                $this->handleFeatureCall(fn(): array => $this->featureService->getAdminAnalytics($user, $dateParam));
                return;

            case 'catalog':
                Response::json(['status' => 'success', 'catalog' => $this->featureService->getServerCatalogPayload()]);
                return;

            case 'get_profile':
                Response::json(['status' => 'success', 'profile' => $this->featureService->getUserProfileData($user)]);
                return;

            case 'update_profile':
                $body = $request->all();
                $this->handleFeatureCall(function () use ($user, $body): array {
                    $res = $this->featureService->updateUserProfile(
                        $user,
                        (string)($body['full_name'] ?? $body['fullName'] ?? ''),
                        (string)($body['phone'] ?? ''),
                        isset($body['email']) ? (string)$body['email'] : null
                    );
                    if (isset($res['user']) && is_array($res['user'])) {
                        Session::set('user', $res['user']);
                    }
                    return $res;
                });
                return;

            case 'save_addresses':
                $body = $request->all();
                $addrs = is_array($body['addresses'] ?? null) ? $body['addresses'] : [];
                $activeLoc = is_array($body['active_location'] ?? null) ? $body['active_location'] : null;
                $this->handleFeatureCall(fn(): array => $this->featureService->saveUserAddresses($user, $addrs, $activeLoc));
                return;

            case 'get_reviews':
                $body = $request->all();
                $rId = isset($body['restaurant_id']) ? (int)$body['restaurant_id'] : null;
                Response::json($this->featureService->getReviews($rId));
                return;

            case 'submit_review':
                $this->handleFeatureCall(fn(): array => $this->featureService->submitReview($user, $request->all()), 201);
                return;

            case 'get_messages':
                $body = $request->all();
                $oId = (int)($body['order_id'] ?? $body['id'] ?? 0);
                $this->handleFeatureCall(fn(): array => $this->featureService->getOrderMessages($user, $oId));
                return;

            case 'send_message':
                $body = $request->all();
                $oId = (int)($body['order_id'] ?? $body['id'] ?? 0);
                $txt = (string)($body['text'] ?? $body['message'] ?? '');
                $this->handleFeatureCall(fn(): array => $this->featureService->sendOrderMessage($user, $oId, $txt), 201);
                return;

            case 'fleet_scores':
                $this->getFleetScores($request, $user);
                return;

            case 'daily_report':
                $body = $request->all();
                $restId = (int)($body['restaurant_id'] ?? 1);
                $rawDate = trim((string)($body['date'] ?? ''));
                $date = $rawDate !== '' ? $rawDate : date('Y-m-d');
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    Response::json(['status' => 'error', 'message' => 'Geçersiz tarih formatı! Beklenen format: YYYY-MM-DD.'], 400);
                    return;
                }
                try {
                    Response::json([
                        'status' => 'success',
                        'report' => $this->orderService->getDailyReportForUser($restId, $date, $user)
                    ]);
                } catch (RuntimeException $e) {
                    $code = in_array($e->getCode(), [401, 403, 404], true) ? $e->getCode() : 403;
                    Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
                }
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
        $role = (string)($user['role'] ?? '');
        if (!in_array($role, ['customer', 'admin'], true)) {
            Response::json([
                'status' => 'error',
                'message' => 'Yetkisiz erişim! Yalnızca müşteri veya yönetici hesapları sipariş oluşturabilir.'
            ], 403);
            return;
        }

        $body = $request->all();
        $restaurantId = (int)($body['restaurant_id'] ?? 0);
        if ($restaurantId <= 0) {
            Response::json(['status' => 'error', 'message' => 'Geçerli bir restoran (restaurant_id) seçilmelidir.'], 400);
            return;
        }
        $totalAmount = (float)($body['total_amount'] ?? 0.0);
        $delX = (int)($body['del_x'] ?? 18);
        $delY = (int)($body['del_y'] ?? 14);
        $items = $body['items'] ?? [];
        if (!is_array($items) || count($items) === 0) {
            Response::json(['status' => 'error', 'message' => 'Sipariş en az bir ürün (items) içermelidir.'], 400);
            return;
        }
        $couponCode = isset($body['coupon_code']) && is_string($body['coupon_code']) ? trim($body['coupon_code']) : null;
        $deliveryFee = (float)($body['delivery_fee'] ?? 0.0);
        $tip = (float)($body['tip'] ?? 0.0);

        try {
            $deliveryMode = (string)($body['delivery_mode'] ?? 'courier');
            // Güvenlik: customerId DAİMA oturum açan kullanıcıdan alınır
            $res = $this->orderService->createOrder(
                (int)$user['id'],
                $restaurantId,
                $totalAmount,
                $delX,
                $delY,
                $items,
                (float)($body['delivery_latitude'] ?? 40.9850),
                (float)($body['delivery_longitude'] ?? 29.0280),
                $deliveryMode,
                $couponCode !== '' ? $couponCode : null,
                $deliveryFee,
                $tip
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
    private function toggleMenuItem(Request $request, array $user): void
    {
        $body = $request->all();
        $menuItemId = (int)($body['menu_item_id'] ?? $body['item_id'] ?? $body['id'] ?? 0);
        if ($menuItemId <= 0) {
            Response::json(['status' => 'error', 'message' => 'Geçerli bir ürün kimliği (menu_item_id) belirtilmelidir.'], 400);
            return;
        }
        $desiredAvailable = array_key_exists('is_available', $body) ? (bool)$body['is_available'] : null;

        try {
            $res = $this->orderService->toggleMenuItemAvailability($menuItemId, $user, $desiredAvailable);
            Response::json($res);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), [401, 403, 404], true) ? $e->getCode() : 400;
            Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
        } catch (\Throwable $e) {
            error_log('[ApiController::toggleMenuItem] ' . $e->getMessage());
            Response::json(['status' => 'error', 'message' => 'Ürün stok durumu güncellenirken hata oluştu.'], 500);
        }
    }

    /**
     * @param callable(): array<string, mixed> $fn
     */
    private function handleFeatureCall(callable $fn, int $successCode = 200): void
    {
        try {
            Response::json($fn(), $successCode);
        } catch (InvalidArgumentException $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), [401, 403, 404, 409], true) ? $e->getCode() : 400;
            Response::json(['status' => 'error', 'message' => $e->getMessage()], $code);
        } catch (\Throwable $e) {
            error_log('[ApiController::handleFeatureCall] ' . $e->getMessage());
            Response::json(['status' => 'error', 'message' => 'İşlem sırasında beklenmeyen bir hata oluştu.'], 500);
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
