<?php
declare(strict_types=1);

/**
 * Tek Giriş Noktası (Front Controller) Çıkış Yönlendiricisi.
 * Tüm oturum kapatma istekleri merkezi public/index.php -> AuthController::logout üzerinden işlenir.
 */
$_SERVER['REQUEST_URI'] = preg_replace('#/logout\.php$#', '/logout', (string)($_SERVER['REQUEST_URI'] ?? '/logout'));
require __DIR__ . '/index.php';
