<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
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
        session_unset();
        session_destroy();
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
