<?php
/**
 * OrbitAdmin bootstrap. Sets up paths, autoloader, config, session,
 * security headers, language, and router. Loaded by public/index.php
 * (web) and bin/orbit (CLI).
 */

if (!defined('ORBIT_INIT')) {
    define('ORBIT_INIT', true);
}

if (!defined('ORBIT_ROOT')) {
    define('ORBIT_ROOT', __DIR__);
}

if (!defined('ORBIT_START_TS')) {
    define('ORBIT_START_TS', microtime(true));
}

require_once ORBIT_ROOT . '/modules/core/Autoload.php';
require_once ORBIT_ROOT . '/modules/core/Helpers.php';

use OrbitAdmin\Core\Config;
use OrbitAdmin\Core\Lang;
use OrbitAdmin\Core\Logger;
use OrbitAdmin\Core\Security;
use OrbitAdmin\Core\Session;
use OrbitAdmin\Core\View;

Config::load(ORBIT_ROOT);

date_default_timezone_set((string) Config::get('APP_TIMEZONE', 'Europe/Oslo'));
mb_internal_encoding('UTF-8');

set_error_handler(static function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (\Throwable $e): void {
    try {
        (new Logger())->error('uncaught: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    } catch (\Throwable $ignored) {
        // last-resort error path; do not surface to the user
    }
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, '[ORBIT ERROR] ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    $view = ORBIT_ROOT . '/views/pages/errors/500.php';
    if (is_file($view)) {
        $errorMessage = Config::get('APP_DEBUG') ? $e->getMessage() : 'Something went wrong.';
        require $view;
    } else {
        echo '500 Internal Server Error';
    }
    exit(1);
});

if (PHP_SAPI !== 'cli') {
    Security::applyHeaders();
    Session::start();
}

Lang::init((string) Config::get('APP_LOCALE', 'en'));

View::share([
    'appName'      => (string) Config::get('APP_NAME', 'OrbitAdmin'),
    'appTagline'   => (string) Config::get('APP_TAGLINE', ''),
    'appVersion'   => (string) Config::get('APP_VERSION', '0.1.0'),
    'appLocale'    => (string) Config::get('APP_LOCALE', 'en'),
    'appDemo'      => (bool)   Config::get('APP_DEMO', false),
    'cspNonce'     => Security::nonce(),
    'baseUrl'      => rtrim((string) Config::get('BASE_URL', ''), '/'),
]);

if (!function_exists('orbit_base_path')) {
    function orbit_base_path(string $sub = ''): string
    {
        return rtrim(ORBIT_ROOT, '/') . ($sub === '' ? '' : '/' . ltrim($sub, '/'));
    }
}
