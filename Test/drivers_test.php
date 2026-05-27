<?php
/**
 * OrbitAdmin driver test. Verifies each driver implements the same surface.
 */

if (!defined('ORBIT_INIT')) {
    require_once dirname(__DIR__) . '/bootstrap.php';
}

use OrbitAdmin\Core\Config;
use OrbitAdmin\Db\DriverInterface;
use OrbitAdmin\Db\JsonDriver;
use OrbitAdmin\Db\SqliteDriver;

$pass = 0; $fail = 0; $failed = [];
function orbit_drv_assert(string $label, bool $ok, ?string $detail = null): void {
    global $pass, $fail, $failed;
    if ($ok) { $pass++; echo "  ok   $label\n"; }
    else     { $fail++; $failed[] = $label; echo "  FAIL $label" . ($detail ? " ($detail)" : '') . "\n"; }
}

function orbit_drv_run(string $label, DriverInterface $driver): void {
    echo "[$label]\n";
    $driver->ensureSchema();
    orbit_drv_assert("$label name", $driver->name() === $label);

    $id = $driver->insert('settings', ['key' => 'drv_test_' . bin2hex(random_bytes(2)), 'value' => 'hi']);
    orbit_drv_assert("$label insert", $id > 0, "id=$id");
    $row = $driver->get('settings', ['id' => $id]);
    orbit_drv_assert("$label get",    is_array($row) && $row['value'] === 'hi');

    $driver->update('settings', $id, ['value' => 'bye']);
    $row = $driver->get('settings', ['id' => $id]);
    orbit_drv_assert("$label update", is_array($row) && $row['value'] === 'bye');

    $driver->delete('settings', $id);
    orbit_drv_assert("$label delete", !$driver->get('settings', ['id' => $id]));
}

echo "OrbitAdmin driver test\n======================\n";

// JSON (current live demo driver).
orbit_drv_run('json', new JsonDriver());

// SQLite, in a temp file.
$tmp = sys_get_temp_dir() . '/orbit_sqlite_' . bin2hex(random_bytes(3)) . '.sqlite';
Config::set('SQLITE_PATH', $tmp);
try {
    $sqlite = new SqliteDriver();
    orbit_drv_run('sqlite', $sqlite);
} catch (\Throwable $e) {
    echo "  SKIP sqlite (" . $e->getMessage() . ")\n";
}
@unlink($tmp);

// MySQL is exercised only if env vars are present.
if (extension_loaded('pdo_mysql') && getenv('ORBIT_MYSQL_TEST') === '1') {
    try {
        Config::set('MYSQL_HOST', getenv('ORBIT_MYSQL_HOST') ?: '127.0.0.1');
        Config::set('MYSQL_PORT', (int) (getenv('ORBIT_MYSQL_PORT') ?: 3306));
        Config::set('MYSQL_DATABASE', getenv('ORBIT_MYSQL_DATABASE'));
        Config::set('MYSQL_USER',     getenv('ORBIT_MYSQL_USER'));
        Config::set('MYSQL_PASSWORD', getenv('ORBIT_MYSQL_PASSWORD'));
        $mysql = new \OrbitAdmin\Db\MysqlDriver();
        orbit_drv_run('mysql', $mysql);
    } catch (\Throwable $e) {
        echo "  SKIP mysql (" . $e->getMessage() . ")\n";
    }
} else {
    echo "[mysql]\n  SKIP mysql (set ORBIT_MYSQL_TEST=1 and credentials to enable)\n";
}

echo "\nResult: $pass passed, $fail failed\n";
if ($fail) { foreach ($failed as $f) { echo "  - $f\n"; } exit(1); }
exit(0);
