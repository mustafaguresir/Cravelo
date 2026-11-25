<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Cravelo — Temel MVC Kontrolcüsü (Base Controller)
 *
 * Sorumluluklar:
 * - Şablon render etme (render)
 * - PRG uyumlu 303 yönlendirmesi (redirect)
 * - JSON yanıt üretme (json)
 */
abstract class Controller
{
    /**
     * View şablonunu layout içine gömerek render eder ve HTML çıktısı verir.
     */
    protected function render(string $template, array $data = [], ?string $layout = 'layouts/main'): void
    {
        $html = View::render($template, $data, $layout);
        Response::html($html);
    }

    /**
     * PRG (Post-Redirect-Get) uyumlu yönlendirme yapar.
     * Standart HTTP 303 See Other durum kodunu kullanır.
     */
    protected function redirect(mixed $requestOrPath, ?string $path = null, int $statusCode = 303): void
    {
        if ($requestOrPath instanceof Request) {
            $url = $requestOrPath->url($path ?? '');
        } else {
            $url = Request::url((string)$requestOrPath);
        }

        Response::redirect($url, $statusCode);
    }

    /**
     * JSON formatında HTTP yanıtı döndürür.
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        Response::json($data, $statusCode);
    }
}
