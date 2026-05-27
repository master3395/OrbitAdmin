<?php
namespace OrbitAdmin\Core {

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Global helper functions exposed as procedural shims.
 * Required by views and controllers.
 */
final class Helpers
{
    public static function dateFormat($timestamp, string $format = 'd/m/Y'): string
    {
        if ($timestamp === null || $timestamp === '') {
            return '';
        }
        if (is_numeric($timestamp)) {
            $ts = (int) $timestamp;
        } else {
            $ts = strtotime((string) $timestamp);
            if ($ts === false) {
                return (string) $timestamp;
            }
        }
        return date($format, $ts);
    }

    public static function dateTimeFormat($timestamp): string
    {
        return self::dateFormat($timestamp, 'd/m/Y H:i');
    }

    public static function dateProse($timestamp): string
    {
        $ts = is_numeric($timestamp) ? (int) $timestamp : strtotime((string) $timestamp);
        if ($ts === false) { return ''; }
        $months = ['', 'januar', 'februar', 'mars', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'desember'];
        return date('j', $ts) . '. ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts) . ' kl. ' . date('H:i', $ts);
    }

    public static function jsonResponse($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $path, int $code = 302): void
    {
        header('Location: ' . Url::to($path), true, $code);
        exit;
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function shortBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $b = (float) $bytes;
        while ($b >= 1024 && $i < count($units) - 1) {
            $b /= 1024;
            $i++;
        }
        return sprintf('%.1f %s', $b, $units[$i]);
    }
}

} // namespace OrbitAdmin\Core

namespace {
use OrbitAdmin\Core\Csrf;
use OrbitAdmin\Core\Helpers;
use OrbitAdmin\Core\Lang;
use OrbitAdmin\Core\Url;

// Procedural shims used in views for short syntax.
if (!function_exists('e')) {
    function e(?string $value): string { return Helpers::e($value); }
}
if (!function_exists('format_date')) {
    function format_date($timestamp, string $format = 'd/m/Y'): string { return Helpers::dateFormat($timestamp, $format); }
}
if (!function_exists('format_datetime')) {
    function format_datetime($timestamp): string { return Helpers::dateTimeFormat($timestamp); }
}
if (!function_exists('format_date_prose')) {
    function format_date_prose($timestamp): string { return Helpers::dateProse($timestamp); }
}
if (!function_exists('url_to')) {
    function url_to(string $path): string { return Url::to($path); }
}
if (!function_exists('asset')) {
    function asset(string $path): string { return Url::asset($path); }
}
if (!function_exists('json_response')) {
    function json_response($data, int $status = 200): void { Helpers::jsonResponse($data, $status); }
}
if (!function_exists('redirect')) {
    function redirect(string $path, int $code = 302): void { Helpers::redirect($path, $code); }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string { return Csrf::field(); }
}
if (!function_exists('csrf_token')) {
    function csrf_token(): string { return Csrf::token(); }
}
if (!function_exists('__')) {
    function __(string $key, ?string $default = null): string { return Lang::get($key, $default); }
}
}

