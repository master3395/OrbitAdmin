<?php
/**
 * Config loader for OrbitAdmin.
 * Reads config.php (preferred) or config.sample.php (first boot).
 */

namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class Config
{
    /** @var array<string,mixed> */
    private static array $data = [];

    public static function load(string $root): void
    {
        $file = $root . '/config.php';
        $sample = $root . '/config.sample.php';
        if (is_file($file)) {
            $cfg = require $file;
        } elseif (is_file($sample)) {
            $cfg = require $sample;
        } else {
            $cfg = [];
        }
        if (!is_array($cfg)) {
            $cfg = [];
        }
        $cfg['BASE_PATH'] = $root;
        if (!isset($cfg['DATA_PATH'])) {
            $cfg['DATA_PATH'] = $root . '/data';
        }
        if (!isset($cfg['LOG_PATH'])) {
            $cfg['LOG_PATH'] = $root . '/logs';
        }
        self::$data = $cfg;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return array_key_exists($key, self::$data) ? self::$data[$key] : $default;
    }

    /** @param mixed $value */
    public static function set(string $key, $value): void
    {
        self::$data[$key] = $value;
    }

    /** @return array<string,mixed> */
    public static function all(): array
    {
        return self::$data;
    }

    public static function isInstalled(): bool
    {
        $marker = self::get('DATA_PATH') . '/.installed';
        return is_file($marker);
    }

    public static function isDemo(): bool
    {
        return (bool) self::get('APP_DEMO', false);
    }
}
