<?php
/**
 * OrbitAdmin front controller.
 * All HTTP requests inside /OrbitAdmin/ funnel through this file.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Config;
use OrbitAdmin\Core\Csrf;
use OrbitAdmin\Core\Router;
use OrbitAdmin\Core\Url;

Csrf::verifyRequest();

$router = new Router();
$router->setBasePrefix((string) Config::get('BASE_URL', ''));

$module = static function (string $path): string {
    return ORBIT_ROOT . '/modules/' . ltrim($path, '/');
};

// Installer guard: if not installed, force users to the wizard (except assets).
if (!Config::isInstalled() && !Config::isDemo()) {
    $req = $router->currentRequest();
    if (!preg_match('#^/install#', $req['path']) && !preg_match('#^/assets/#', $req['path'])) {
        redirect('/install');
    }
}

$router->any('/install',                    $module('installer/install.php'));
$router->any('/install/step/{step}',        $module('installer/install.php'));

$router->any('/login',                      $module('auth/login.php'));
$router->any('/logout',                     $module('auth/logout.php'));

$router->any('/',                           $module('dashboard/index.php'));
$router->any('/dashboard',                  $module('dashboard/index.php'));
$router->get('/api/dashboard/stats',        $module('dashboard/api_stats.php'));

$router->any('/users',                      $module('users/list.php'));
$router->any('/users/create',               $module('users/create.php'));
$router->any('/users/{id}/edit',            $module('users/edit.php'));
$router->post('/users/{id}/delete',         $module('users/delete.php'));

$router->any('/roles',                      $module('roles/list.php'));
$router->any('/roles/{id}/edit',            $module('roles/edit.php'));

$router->any('/activity',                   $module('activity/log.php'));

$router->any('/tokens',                     $module('tokens/list.php'));
$router->any('/tokens/create',              $module('tokens/create.php'));
$router->post('/tokens/{id}/revoke',        $module('tokens/revoke.php'));

$router->any('/emails',                     $module('emails/templates.php'));
$router->any('/emails/{id}/edit',           $module('emails/edit.php'));

$router->any('/files',                      $module('files/manager.php'));
$router->post('/files/upload',              $module('files/manager.php'));
$router->post('/files/delete',              $module('files/manager.php'));

$router->any('/system',                     $module('system/info.php'));

$router->any('/settings',                   $module('settings/index.php'));
$router->any('/profile',                    $module('auth/profile.php'));

$router->dispatch();
