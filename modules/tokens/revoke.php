<?php
use OrbitAdmin\Activity\ActivityLog;
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireLogin();

$params = $params ?? ($GLOBALS['orbit_route_params'] ?? []);
$id = (int) ($params['id'] ?? 0);
if ($id <= 0) {
    Flash::error('Invalid token.');
    redirect('/tokens');
}

$db = Database::instance();
$db->update('tokens', $id, ['revoked_at' => date('c')]);
ActivityLog::record('token.revoke', 'token#' . $id);
Flash::success('Token revoked.');
redirect('/tokens');
