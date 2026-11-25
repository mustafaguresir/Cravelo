<?php
/**
 * Cravelo — Aşama 2 Front Controller (Tek Giriş Noktası)
 *
 * Şartname Gereksinimleri:
 * - Tarayıcıdan erişilen PHP dosyaları tek bir giriş noktasından yönlendirilmelidir.
 * - Router, Controller, Service, Repository ve View sorumlulukları ayrılmıştır.
 * - Form hatalarında anlaşılır mesajlar ve eski değerlerin korunması (old input).
 * - POST-Redirect-GET (PRG) akışı ve flash mesajlar.
 * - XAMPP / WAMP alt dizin ve kök dizin tam uyumluluğu.
 */

declare(strict_types=1);

// Composer Autoloader veya Yedek PSR-4 Yükleyici
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $base_dir = __DIR__ . '/../src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Controller\AuthController;
use App\Controller\HomeController;
use App\Controller\OrderController;
use App\Controller\ApiController;

// Oturumu Başlat
Session::start();

// İstek ve Yönlendiriciyi Başlat
$request = new Request();
$router = new Router();

// Şartname Madde 1 & Madde 4: Aktif Front Controller Auth Guard (PRG 303 Yönlendirmesi)
$requestPath = $request->getPath();
$requiresAuthWebRoute = ($requestPath === '/orders' || str_starts_with($requestPath, '/orders/'));

if (!Session::has('user') && $requiresAuthWebRoute) {
    Session::setFlash('warning', 'Bu sayfaya erişebilmek için lütfen giriş yapınız.');
    Response::redirect($request->url('/login'), 303);
}

// --- ROTA TANIMLARI (ROUTES) ---

// 1. Ana Sayfa & Dashboard (Auth Guard Korumalı)
$router->get('/', [HomeController::class, 'index']);
$router->get('/index.php', [HomeController::class, 'index']);

// 2. Kimlik Doğrulama & Oturum (PRG Flow)
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/logout.php', [AuthController::class, 'logout']);
$router->post('/logout.php', [AuthController::class, 'logout']);

// 3. Sipariş Akışı & Detayı
$router->post('/orders', [OrderController::class, 'create']);
$router->get('/orders/{id}', [OrderController::class, 'show']);

// 4. Kısmi Yenileme (AJAX) Uç Noktaları — Temiz API Rotaları (Plan Section 3.C.4)
$router->get('/api/order-status', [ApiController::class, 'handleOrderStatus']);
$router->post('/api/order-status', [ApiController::class, 'handleOrderStatus']);
$router->get('/api/kds-orders', [ApiController::class, 'handleKdsOrders']);
$router->post('/api/kds-orders', [ApiController::class, 'handleKdsOrders']);
$router->get('/api/courier-tasks', [ApiController::class, 'handleCourierTasks']);
$router->post('/api/courier-tasks', [ApiController::class, 'handleCourierTasks']);
$router->get('/api/audit-logs', [ApiController::class, 'handleAuditLogs']);
$router->post('/api/audit-logs', [ApiController::class, 'handleAuditLogs']);

// Geriye Dönük Uyumluluk Rotaları
$router->get('/api', [ApiController::class, 'handle']);
$router->post('/api', [ApiController::class, 'handle']);
$router->get('/api.php', [ApiController::class, 'handle']);
$router->post('/api.php', [ApiController::class, 'handle']);

// İsteği İlgili Controller'a Yönlendir
$router->dispatch($request);
