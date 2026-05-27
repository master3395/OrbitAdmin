<?php
namespace OrbitAdmin\Core;

use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $db = Database::instance();
        $user = $db->get('users', ['username' => $username]);
        if (!$user) {
            $user = $db->get('users', ['email' => $username]);
        }
        if (!$user) {
            return false;
        }
        if (empty($user['active'])) {
            return false;
        }
        if (!password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return false;
        }
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $db->update('users', (int) $user['id'], [
                'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            ]);
        }
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'] ?? 'Viewer';
        $_SESSION['user_name'] = $user['name'] ?? $user['username'];
        $db->update('users', (int) $user['id'], [
            'last_login_at' => date('c'),
            'last_login_ip' => Security::clientIp(),
        ]);
        Csrf::rotate();
        return true;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        $cached = $_SESSION['_user_cache'] ?? null;
        if ($cached && (int) ($cached['id'] ?? 0) === (int) $_SESSION['user_id']) {
            return $cached;
        }
        $db = Database::instance();
        $user = $db->get('users', ['id' => (int) $_SESSION['user_id']]);
        if ($user) {
            unset($user['password_hash']);
            $_SESSION['_user_cache'] = $user;
        }
        return $user ?: null;
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['user_id'] : null;
    }

    public static function role(): string
    {
        return (string) ($_SESSION['user_role'] ?? 'Viewer');
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $next = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: ' . Url::to('/login?next=' . urlencode($next)));
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            $view = Config::get('BASE_PATH') . '/views/pages/errors/403.php';
            if (is_file($view)) {
                require $view;
            } else {
                echo '403 Forbidden';
            }
            exit;
        }
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
