<?php
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Core\Logger;
use OrbitAdmin\Core\Security;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

if (Auth::check()) {
    (new Logger())->info('logout', [
        'user_id' => Auth::id(),
        'ip' => Security::clientIp(),
    ]);
    Auth::logout();
}
Flash::info('You have been signed out.');
redirect('/login');
