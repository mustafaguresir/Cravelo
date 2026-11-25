<?php
/**
 * Cravelo Aşama 2: Mimari Hizalama ve Otomatik Doğrulama Test Paketi (PHP)
 * Referans Plan: xampp_wamp_mvc_full_architecture_plan.md
 */

declare(strict_types=1);

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $rel = substr($class, strlen('App\\'));
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', $rel) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once __DIR__ . '/../src/Core/Session.php';
require_once __DIR__ . '/../src/Core/Request.php';
require_once __DIR__ . '/../src/Core/Response.php';
require_once __DIR__ . '/../src/Core/View.php';
require_once __DIR__ . '/../src/Core/Controller.php';
require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Enum/OrderStatus.php';
require_once __DIR__ . '/../src/Enum/CourierStatus.php';
require_once __DIR__ . '/../src/Enum/UserRole.php';
require_once __DIR__ . '/../src/Model/Order.php';
require_once __DIR__ . '/../src/Model/OrderItem.php';
require_once __DIR__ . '/../src/Model/Courier.php';
require_once __DIR__ . '/../src/Model/Restaurant.php';
require_once __DIR__ . '/../src/Model/User.php';
require_once __DIR__ . '/../src/Repository/OrderRepositoryInterface.php';
require_once __DIR__ . '/../src/Repository/CourierRepositoryInterface.php';
require_once __DIR__ . '/../src/Repository/RestaurantRepositoryInterface.php';
require_once __DIR__ . '/../src/Repository/UserRepositoryInterface.php';
require_once __DIR__ . '/../src/Repository/InMemory/InMemoryOrderRepository.php';
require_once __DIR__ . '/../src/Repository/InMemory/InMemoryCourierRepository.php';
require_once __DIR__ . '/../src/Repository/InMemory/InMemoryRestaurantRepository.php';
require_once __DIR__ . '/../src/Repository/InMemory/InMemoryUserRepository.php';
require_once __DIR__ . '/../src/Repository/Json/JsonOrderRepository.php';
require_once __DIR__ . '/../src/Repository/Json/JsonCourierRepository.php';
require_once __DIR__ . '/../src/Repository/Json/JsonRestaurantRepository.php';
require_once __DIR__ . '/../src/Repository/Json/JsonUserRepository.php';
require_once __DIR__ . '/../src/Service/HaversineDistanceService.php';
require_once __DIR__ . '/../src/Service/Courier/AssignmentStrategyInterface.php';
require_once __DIR__ . '/../src/Service/Courier/ScoreBasedAssignmentStrategy.php';
require_once __DIR__ . '/../src/Service/CourierAssignmentService.php';
require_once __DIR__ . '/../src/Service/OrderStateMachineService.php';
require_once __DIR__ . '/../src/Service/AuthService.php';
require_once __DIR__ . '/../src/Storage/JsonStorageHelper.php';
require_once __DIR__ . '/../src/Controller/AuthController.php';
require_once __DIR__ . '/../src/Controller/HomeController.php';
require_once __DIR__ . '/../src/Controller/OrderController.php';
require_once __DIR__ . '/../src/Controller/ApiController.php';

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Enum\OrderStatus;
use App\Enum\CourierStatus;
use App\Model\Order;
use App\Model\Courier;
use App\Model\Restaurant;
use App\Repository\InMemory\InMemoryOrderRepository;
use App\Repository\InMemory\InMemoryCourierRepository;
use App\Repository\InMemory\InMemoryRestaurantRepository;
use App\Service\OrderStateMachineService;
use App\Service\CourierAssignmentService;
use App\Controller\OrderController;
use App\Storage\JsonStorageHelper;

$passed = 0;
$failed = 0;

function run_check(string $name, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo " [✓ GEÇTİ] {$name}\n";
        if ($detail) echo "          ↳ {$detail}\n";
        $passed++;
    } else {
        echo " [✗ BAŞARISIZ] {$name}\n";
        if ($detail) echo "          ↳ {$detail}\n";
        $failed++;
    }
}

echo "======================================================================\n";
echo "  CRAVELO M1: MİMARİ DOĞRULAMA TESTLERİ (STANDALONE PHP TEST RUNNER)\n";
echo "======================================================================\n";

// 1. Base Path & URL
$_SERVER['SCRIPT_NAME'] = '/yemek-kurye-sistemi/public/index.php';
$_SERVER['REQUEST_URI'] = '/yemek-kurye-sistemi/login';
$basePath = Request::getBasePath();
$url = Request::url('/login');
run_check(
    "TEST 1: Request Base Path & URL Üretimi",
    $basePath === '/yemek-kurye-sistemi' && $url === '/yemek-kurye-sistemi/login',
    "Base: {$basePath}, URL: {$url}"
);

// 2. View Helper Fonksiyonları
run_check(
    "TEST 2: View Global Helper Tanımları (e, old, base_url, asset_url)",
    function_exists('e') && function_exists('old') && function_exists('base_url') && function_exists('asset_url'),
    "XSS ve URL yardımcıları küresel olarak tanımlı"
);

// 3. Session Flash Yaşam Döngüsü
Session::setFlash('success', 'Sipariş tamamlandı');
$flash1 = Session::getFlash();
$flash2 = Session::getFlash();
run_check(
    "TEST 3: Session Flash Tek Seferlik Yaşam Döngüsü",
    $flash1 !== null && $flash1['type'] === 'success' && $flash2 === null,
    "Flash mesaj okunduktan sonra derhal silinir"
);

// 4. Session Old Input Parola Arındırma
Session::setOldInput([
    'username' => 'ahmet_musteri',
    'phone' => '05551112233',
    'password' => 'secret123',
    'password_confirm' => 'secret123'
]);
$oldUser = Session::getOldInput('username');
$oldPass = Session::getOldInput('password');
$oldAll = Session::getOldInput();
Session::clearOldInput();
run_check(
    "TEST 4: Session Old Input Parola Filtreleme",
    $oldUser === 'ahmet_musteri' && $oldPass === '' && !isset($oldAll['password']),
    "Parola ve parola tekrarı Session'a kaydedilmez"
);

// 5. JsonStorageHelper Kilit ve Atomik Takas
$testFile = sys_get_temp_dir() . '/gustogo_php_test_' . uniqid() . '.json';
$written = JsonStorageHelper::saveWithLock($testFile, [['id' => 1, 'name' => 'Pizza']]);
$readData = JsonStorageHelper::read($testFile);
@unlink($testFile);
@unlink($testFile . '.lock');
run_check(
    "TEST 5: JsonStorageHelper::saveWithLock ve read",
    $written === true && count($readData) === 1 && $readData[0]['name'] === 'Pizza',
    "flock(LOCK_EX) ve tempnam takası ile güvenli yazma tamamlandı"
);

// 6. OrderController DIP ve Durum İlerlemesi
$oRepo = new InMemoryOrderRepository();
$cRepo = new InMemoryCourierRepository();
$rRepo = new InMemoryRestaurantRepository();
$sm = new OrderStateMachineService($oRepo);
$cas = new CourierAssignmentService();

$rRepo->save(new Restaurant(1, 10, 'Moda Kebap', 15, 12, '555-0101', true, 40.9850, 29.0280));
$cRepo->save(new Courier(1, 20, 'Kurye Ali', 16, 12, 2, CourierStatus::MUSAIT, 40.9910, 29.0250));
$cRepo->save(new Courier(2, 21, 'Kurye Veli', 14, 15, 0, CourierStatus::MUSAIT, 40.9750, 29.0350));

$ctrl = new OrderController($sm, $cas, $oRepo, $cRepo, $rRepo);
$res = $ctrl->createOrder(1, 1, 200.0, 18, 14);
$orderId = $res['order_id'];

$step1 = $ctrl->changeStatus($orderId, 'hazirlaniyor', 'restaurant');
$step2 = $ctrl->changeStatus($orderId, 'hazir', 'restaurant');
$step3 = $ctrl->changeStatus($orderId, 'yolda', 'courier');
$step4 = $ctrl->changeStatus($orderId, 'teslim_edildi', 'courier');

run_check(
    "TEST 6: OrderController Arayüz Bağımlılığı ve Sıralı Durum İlerletme",
    $res['status'] === 'success' &&
    $step1['current_status'] === 'hazirlaniyor' &&
    $step2['current_status'] === 'hazir' &&
    $step3['current_status'] === 'yolda' &&
    $step4['current_status'] === 'teslim_edildi',
    "alindi -> hazirlaniyor -> hazir -> yolda -> teslim_edildi adımları başarıyla tamamlandı"
);

// 7. OrderController Pişen Yemeğin İptal Yasağı
$orderId2 = $ctrl->createOrder(1, 1, 150.0, 18, 14)['order_id'];
$ctrl->changeStatus($orderId2, 'hazirlaniyor', 'restaurant');
$cancelBlocked = false;
try {
    $ctrl->cancelOrder($orderId2);
} catch (\DomainException $e) {
    $cancelBlocked = true;
}
run_check(
    "TEST 7: OrderController Pişen Yemeğin İptal Yasağı (DomainException)",
    $cancelBlocked,
    "Hazırlanmakta olan siparişin iptali engellendi"
);

// 8. OrderController Alındı Aşamasında İptal İzni
$orderId3 = $ctrl->createOrder(1, 1, 120.0, 18, 14)['order_id'];
$cancelRes = $ctrl->cancelOrder($orderId3);
run_check(
    "TEST 8: OrderController 'alindi' Durumundaki Siparişin İptal İzni",
    $cancelRes['status'] === 'success' && $cancelRes['current_status'] === 'iptal',
    "İlk aşamadaki sipariş başarıyla iptal edildi"
);

// 9. Trailing Slash Normalizasyonu (/orders/ -> /orders, /login/ -> /login)
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$_SERVER['REQUEST_URI'] = '/orders/';
$_GET = [];
$req1 = new Request();
$p1 = $req1->getPath();

$_SERVER['REQUEST_URI'] = '/login/';
$req2 = new Request();
$p2 = $req2->getPath();

$_SERVER['REQUEST_URI'] = '/api/order-status/';
$req3 = new Request();
$p3 = $req3->getPath();

$_SERVER['REQUEST_URI'] = '/';
$req4 = new Request();
$p4 = $req4->getPath();

run_check(
    "TEST 9: Request Trailing Slash Normalizasyonu (/orders/ -> /orders)",
    $p1 === '/orders' && $p2 === '/login' && $p3 === '/api/order-status' && $p4 === '/',
    "Rotadaki gereksiz sondaki taksimler ('/') normalize edildi: {$p1}, {$p2}, {$p3}, {$p4}"
);

// 10. Ön Ek Sınır Çakışması Koruması (/app vs /apples)
$_SERVER['SCRIPT_NAME'] = '/app/public/index.php';
$_SERVER['REQUEST_URI'] = '/apples';
$_GET = [];
$reqApples = new Request();
$pApples = $reqApples->getPath();

$_SERVER['REQUEST_URI'] = '/app/orders/';
$reqAppOrders = new Request();
$pAppOrders = $reqAppOrders->getPath();

run_check(
    "TEST 10: Request Base Path Sınır Çakışması Koruması (/app vs /apples)",
    $pApples === '/apples' && $pAppOrders === '/orders',
    "/apples rota budanması engellendi: {$pApples}, /app/orders/ -> {$pAppOrders}"
);

// 11. Bağıl SCRIPT_NAME Tespiti ('public/index.php' -> '')
$_SERVER['SCRIPT_NAME'] = 'public/index.php';
$relBase = Request::getBasePath();
$_SERVER['SCRIPT_NAME'] = 'my-app/public/index.php';
$relAppBase = Request::getBasePath();
run_check(
    "TEST 11: Bağıl SCRIPT_NAME Base Path Tespiti ('public/index.php' -> '')",
    $relBase === '' && $relAppBase === 'my-app',
    "public/index.php -> '{$relBase}', my-app/public/index.php -> '{$relAppBase}'"
);

// 12. JsonStorageHelper Kilit Sırası ve Güvenli Okuma
$testNonExistent = sys_get_temp_dir() . '/gustogo_nonexistent_' . uniqid() . '.json';
$emptyRes = JsonStorageHelper::read($testNonExistent);
@unlink($testNonExistent . '.lock');

$testValid = sys_get_temp_dir() . '/gustogo_valid_' . uniqid() . '.json';
JsonStorageHelper::saveWithLock($testValid, ['status' => 'ok']);
$validRes = JsonStorageHelper::read($testValid);
@unlink($testValid);
@unlink($testValid . '.lock');

run_check(
    "TEST 12: JsonStorageHelper Kilit Sırası ve Güvenli Okuma",
    $emptyRes === [] && isset($validRes['status']) && $validRes['status'] === 'ok',
    "Kilit korumalı okuma başarılı ve yarış durumuna karşı korumalı"
);

echo "======================================================================\n";
echo "  SONUÇ: {$passed} Test Geçti, {$failed} Başarısız\n";
echo "======================================================================\n";

exit($failed === 0 ? 0 : 1);
