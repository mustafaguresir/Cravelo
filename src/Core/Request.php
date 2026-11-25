<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private string $method;
    private string $path;
    private array $getParams;
    private array $postParams;
    private ?array $jsonData = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->getParams = $_GET;
        $this->postParams = $_POST;
        $this->path = $this->detectPath();
    }

    public static function detectBasePath(): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = dirname($scriptName);
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            return '';
        }
        $dir = preg_replace('#(^|/)public$#', '', $dir);
        if ($dir === '' || $dir === '/' || $dir === '\\' || $dir === '.') {
            return '';
        }
        return rtrim($dir, '/');
    }

    private function detectPath(): string
    {
        // 1. Query param fallback (?r=/login veya ?route=/orders)
        if (!empty($_GET['r'])) {
            return '/' . trim((string)$_GET['r'], '/');
        }
        if (!empty($_GET['route'])) {
            return '/' . trim((string)$_GET['route'], '/');
        }

        // 2. REQUEST_URI tespiti
        $rawUri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsed = parse_url($rawUri, PHP_URL_PATH) ?: '/';

        // BasePath'i URI başından temizle (XAMPP alt klasör desteği)
        $base = self::detectBasePath();
        if ($base !== '' && ($parsed === $base || str_starts_with($parsed, $base . '/'))) {
            $parsed = substr($parsed, strlen($base));
        } else {
            $rawDir = dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''));
            if ($rawDir !== '/' && $rawDir !== '\\' && $rawDir !== '.' && ($parsed === $rawDir || str_starts_with($parsed, $rawDir . '/'))) {
                $parsed = substr($parsed, strlen($rawDir));
            }
        }

        // Eğer public/ ile başlıyorsa temizle
        if (str_starts_with($parsed, '/public/')) {
            $parsed = substr($parsed, 7);
        } elseif ($parsed === '/public') {
            $parsed = '/';
        }

        $parsed = '/' . trim($parsed, '/');
        return $parsed;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public static function getBasePath(): string
    {
        return self::detectBasePath();
    }

    public static function url(string $path = ''): string
    {
        $base = self::detectBasePath();
        $clean = ltrim($path, '/');
        return ($base === '') ? ('/' . $clean) : ($base . '/' . $clean);
    }

    public static function asset(string $path = ''): string
    {
        return self::url($path);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    public function all(): array
    {
        if ($this->isJson()) {
            return array_merge($this->getParams, $this->getJson());
        }
        return array_merge($this->getParams, $this->postParams);
    }

    public function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($contentType, 'application/json');
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$serverKey]) && is_string($_SERVER[$serverKey])) {
            return $_SERVER[$serverKey];
        }
        return $default;
    }

    public function getJson(): array
    {
        if ($this->jsonData === null) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '', true);
            $this->jsonData = is_array($decoded) ? $decoded : [];
        }
        return $this->jsonData;
    }
}
