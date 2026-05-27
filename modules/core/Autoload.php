<?php
/**
 * OrbitAdmin minimal PSR-4-style autoloader.
 *
 * Maps:
 *   OrbitAdmin\Core\Foo   -> modules/core/Foo.php
 *   OrbitAdmin\Db\Foo     -> modules/db/Foo.php
 *   OrbitAdmin\Auth\Foo   -> modules/auth/Foo.php
 *   ... and so on for every modules/<lower>/ namespace.
 */

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'OrbitAdmin\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);
    if (count($parts) < 2) {
        return;
    }
    $sub = strtolower(array_shift($parts));
    $file = __DIR__ . '/../' . $sub . '/' . implode('/', $parts) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
