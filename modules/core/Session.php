<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = Security::isHttps();
        $name = (string) Config::get('SESSION_NAME', 'orbit_sid');
        session_name($name);
        $params = [
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ];
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params($params);
        } else {
            session_set_cookie_params(0, '/', '', $secure, true);
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        if ($secure) {
            ini_set('session.cookie_secure', '1');
        }
        session_start();
        if (!isset($_SESSION['_started_at'])) {
            $_SESSION['_started_at'] = time();
            $_SESSION['_fingerprint'] = self::fingerprint();
        } elseif ($_SESSION['_fingerprint'] !== self::fingerprint()) {
            self::destroy();
            session_start();
            $_SESSION['_started_at'] = time();
            $_SESSION['_fingerprint'] = self::fingerprint();
        }
        $idleLimit = (int) Config::get('SESSION_IDLE_SECONDS', 1800);
        if (!empty($_SESSION['_last_seen']) && (time() - $_SESSION['_last_seen']) > $idleLimit) {
            self::destroy();
            session_start();
            $_SESSION['_started_at'] = time();
            $_SESSION['_fingerprint'] = self::fingerprint();
        }
        $_SESSION['_last_seen'] = time();
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $p['path'], $p['domain'], (bool) ($p['secure'] ?? false), (bool) ($p['httponly'] ?? true));
            }
            session_destroy();
        }
    }

    private static function fingerprint(): string
    {
        return hash('sha256', (Security::userAgent() ?: 'unknown') . '|' . (Config::get('APP_KEY') ?: 'k'));
    }
}
