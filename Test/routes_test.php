<?php
/**
 * OrbitAdmin route test. Verifies the Router resolves the right handler
 * for representative paths (without actually firing controllers).
 */

if (!defined('ORBIT_INIT')) {
    require_once dirname(__DIR__) . '/bootstrap.php';
}

use OrbitAdmin\Core\Router;

$pass = 0;
$fail = 0;
$failed = [];

function orbit_route_assert(string $label, bool $ok, ?string $detail = null): void {
    global $pass, $fail, $failed;
    if ($ok) {
        $pass++;
        echo "  ok   $label\n";
    } else {
        $fail++;
        $failed[] = $label;
        echo "  FAIL $label" . ($detail ? " ($detail)" : '') . "\n";
    }
}

echo "OrbitAdmin route test\n=====================\n";

$router = new Router();
$router->setBasePrefix('/OrbitAdmin');

$captured = null;
$cases = [
    '/'              => 'root',
    '/dashboard'     => 'dashboard',
    '/login'         => 'login',
    '/users'         => 'users.list',
    '/users/42/edit' => 'users.edit',
    '/api/dashboard/stats' => 'api.dashboard.stats',
    '/tokens/7/revoke'     => 'tokens.revoke',
    '/install'             => 'install',
    '/install/step/2'      => 'install.step',
];
foreach ($cases as $path => $name) {
    $router->any($path, static function ($params) use (&$captured, $name) {
        $captured = ['name' => $name, 'params' => $params];
    });
}

foreach ($cases as $path => $name) {
    $captured = null;
    $_SERVER['REQUEST_URI'] = '/OrbitAdmin' . $path;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $reqRouter = new Router();
    $reqRouter->setBasePrefix('/OrbitAdmin');
    foreach ($cases as $p => $n) {
        $reqRouter->any($p, static function ($params) use (&$captured, $n) {
            $captured = ['name' => $n, 'params' => $params];
        });
    }
    $reqRouter->dispatch();
    orbit_route_assert("matches $path", $captured && $captured['name'] === $name, $captured ? json_encode($captured) : 'no match');
}

// Verify params capture
$_SERVER['REQUEST_URI'] = '/OrbitAdmin/users/99/edit';
$captured = null;
$paramRouter = new Router();
$paramRouter->setBasePrefix('/OrbitAdmin');
$paramRouter->get('/users/{id}/edit', static function ($params) use (&$captured) {
    $captured = $params;
});
$paramRouter->dispatch();
orbit_route_assert('captures {id}', is_array($captured) && (int) ($captured['id'] ?? 0) === 99, json_encode($captured));

echo "\nResult: $pass passed, $fail failed\n";
if ($fail) {
    foreach ($failed as $f) {
        echo "  - $f\n";
    }
    exit(1);
}
exit(0);
