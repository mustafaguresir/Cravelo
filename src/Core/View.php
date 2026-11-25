<?php
declare(strict_types=1);

namespace {
    if (!function_exists('e')) {
        function e(mixed $value): string
        {
            return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
        }
    }

    if (!function_exists('old')) {
        function old(string $key, string $default = ''): string
        {
            return (string)\App\Core\Session::getOldInput($key, $default);
        }
    }

    if (!function_exists('base_url')) {
        function base_url(string $path = ''): string
        {
            return \App\Core\Request::url($path);
        }
    }

    if (!function_exists('asset_url')) {
        function asset_url(string $path = ''): string
        {
            return \App\Core\Request::url($path);
        }
    }
}

namespace App\Core {

final class View
{
    private static string $viewsPath = __DIR__ . '/../../views/';

    public static function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $viewFile = self::$viewsPath . ltrim($template, '/') . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View şablonu bulunamadı: {$template} ({$viewFile})");
        }

        // Global Görünüm Değişkenleri
        $data['request'] = new Request();
        $data['basePath'] = $data['request']->getBasePath();
        $data['currentUser'] = Session::get('user');
        $data['flash'] = Session::getFlash();
        $data['oldInput'] = Session::getOldInput() ?? [];

        // Şablonu Çıktı Tamponunda Render Et
        $content = self::renderFile($viewFile, $data);

        // Layout Varsa İçine Göm
        if ($layout !== null) {
            $layoutFile = self::$viewsPath . ltrim($layout, '/') . '.php';
            if (file_exists($layoutFile)) {
                $data['content'] = $content;
                return self::renderFile($layoutFile, $data);
            }
        }

        return $content;
    }

    private static function renderFile(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean() ?: '';
    }
}
}
