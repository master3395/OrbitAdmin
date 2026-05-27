<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = Security::randomToken(32);
        }
        return $_SESSION[self::KEY];
    }

    public static function rotate(): void
    {
        $_SESSION[self::KEY] = Security::randomToken(32);
    }

    public static function check(?string $supplied): bool
    {
        $expected = $_SESSION[self::KEY] ?? '';
        if (!is_string($supplied) || $supplied === '' || $expected === '') {
            return false;
        }
        return hash_equals($expected, $supplied);
    }

    public static function verifyRequest(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }
        $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!self::check(is_string($token) ? $token : null)) {
            http_response_code(419);
            header('Content-Type: text/plain; charset=utf-8');
            echo "CSRF token mismatch. Please refresh and try again.";
            exit;
        }
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
