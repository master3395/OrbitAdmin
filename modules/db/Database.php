<?php
namespace OrbitAdmin\Db;

use OrbitAdmin\Core\Config;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class Database
{
    private static ?DriverInterface $instance = null;

    public static function instance(): DriverInterface
    {
        if (self::$instance instanceof DriverInterface) {
            return self::$instance;
        }
        $driver = (string) Config::get('DB_DRIVER', 'json');
        switch ($driver) {
            case 'mysql':
                self::$instance = new MysqlDriver();
                break;
            case 'sqlite':
                self::$instance = new SqliteDriver();
                break;
            case 'json':
            default:
                self::$instance = new JsonDriver();
                break;
        }
        self::$instance->ensureSchema();
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
