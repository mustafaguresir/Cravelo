<?php
declare(strict_types=1);

/**
 * Tek Giriş Noktası (Front Controller) Geriye Dönük Uyumluluk Yönlendiricisi.
 * Tüm API istekleri merkezi public/index.php Front Controller ve ApiController üzerinden işlenir.
 */
if (empty($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/api';
}
require __DIR__ . '/index.php';
