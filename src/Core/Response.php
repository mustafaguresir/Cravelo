<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $url, int $statusCode = 303): void
    {
        // 303 See Other: POST-Redirect-GET akışının resmi HTTP durum kodudur
        header('Location: ' . $url, true, $statusCode);
        exit;
    }

    public static function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function html(string $html, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}
