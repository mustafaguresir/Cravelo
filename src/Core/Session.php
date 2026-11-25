<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                @ini_set('session.use_strict_mode', '1');
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
                @session_set_cookie_params([
                    'lifetime' => 0,
                    'path' => '/',
                    'secure' => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
            @session_start();
        }
    }

    public static function regenerate(): void
    {
        self::start();
        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (!headers_sent() && (bool)ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            $cookieName = session_name();
            if ($cookieName !== false) {
                @setcookie($cookieName, '', [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => 'Lax',
                ]);
            }
        }
        @session_unset();
        @session_destroy();
    }

    public static function setFlash(string $type, string $message): void
    {
        self::start();
        $_SESSION['__flash'] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): ?array
    {
        self::start();
        if (isset($_SESSION['__flash'])) {
            $flash = $_SESSION['__flash'];
            unset($_SESSION['__flash']);
            return $flash;
        }
        return null;
    }

    public static function setOldInput(array $data): void
    {
        self::start();
        // Parolaları güvenlik gereği old input'a eklemiyoruz
        unset($data['password'], $data['password_confirm']);
        $_SESSION['__old_input'] = $data;
    }

    public static function getOldInput(?string $key = null, mixed $default = ''): mixed
    {
        self::start();
        if ($key === null) {
            return $_SESSION['__old_input'] ?? [];
        }
        return (string)($_SESSION['__old_input'][$key] ?? (string)($default ?? ''));
    }

    public static function clearOldInput(): void
    {
        self::start();
        unset($_SESSION['__old_input']);
    }

    public static function getCsrfToken(): string
    {
        self::start();
        if (empty($_SESSION['__csrf_token'])) {
            $_SESSION['__csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['__csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        self::start();
        if (empty($_SESSION['__csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['__csrf_token'], $token);
    }
}
