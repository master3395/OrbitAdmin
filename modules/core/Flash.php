<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class Flash
{
    private const KEY = '_flash';

    public static function add(string $type, string $message): void
    {
        $_SESSION[self::KEY][] = ['type' => $type, 'message' => $message];
    }

    public static function success(string $m): void { self::add('success', $m); }
    public static function error(string $m): void   { self::add('danger',  $m); }
    public static function info(string $m): void    { self::add('info',    $m); }
    public static function warn(string $m): void    { self::add('warning', $m); }

    /** @return array<int,array{type:string,message:string}> */
    public static function pull(): array
    {
        $items = $_SESSION[self::KEY] ?? [];
        unset($_SESSION[self::KEY]);
        return is_array($items) ? $items : [];
    }
}
