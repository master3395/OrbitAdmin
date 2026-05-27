<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * URL helper. Builds URLs aware of the BASE_URL prefix
 * (e.g. /OrbitAdmin when hosted under a sub-directory).
 */
final class Url
{
    public static function base(): string
    {
        $base = (string) Config::get('BASE_URL', '');
        return rtrim($base, '/');
    }

    public static function to(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return self::base() . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return self::base() . '/assets/' . ltrim($path, '/');
    }

    public static function current(): string
    {
        return (string) ($_SERVER['REQUEST_URI'] ?? '/');
    }

    public static function isActive(string $path): bool
    {
        $current = parse_url(self::current(), PHP_URL_PATH) ?: '/';
        $target = self::to($path);
        return $current === $target || strpos($current, rtrim($target, '/') . '/') === 0;
    }
}
