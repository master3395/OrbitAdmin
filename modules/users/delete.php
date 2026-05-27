<?php
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Demo;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Core\Logger;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireRole('Owner');

$params = $params ?? ($GLOBALS['orbit_route_params'] ?? []);
$id = (int) ($params['id'] ?? 0);

if ($id <= 0) {
    Flash::error('Invalid user.');
    redirect('/users');
}
if ($id === (int) Auth::id()) {
    Flash::warn('You cannot delete your own account.');
    redirect('/users');
}
if (!Demo::guard('delete', ['user_id' => $id])) {
    redirect('/users');
}

$db = Database::instance();
$db->delete('users', $id);
(new Logger())->info('users.delete', ['id' => $id]);
Flash::success('User deleted.');
redirect('/users');
