<?php
/**
 * OrbitAdmin smoke test. Boots the application and exercises the
 * critical paths without requiring a browser or a web server.
 */

if (!defined('ORBIT_INIT')) {
    require_once dirname(__DIR__) . '/bootstrap.php';
}

use OrbitAdmin\Activity\ActivityLog;
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Csrf;
use OrbitAdmin\Core\Mailer;
use OrbitAdmin\Core\RateLimiter;
use OrbitAdmin\Core\Validator;
use OrbitAdmin\Db\Database;

$pass = 0;
$fail = 0;
$failed = [];

function orbit_assert(string $label, bool $ok, ?string $detail = null): void {
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

echo "OrbitAdmin smoke test\n=====================\n";

echo "[autoload]\n";
orbit_assert('Helpers loaded', function_exists('e'));
orbit_assert('Auth class', class_exists('OrbitAdmin\\Core\\Auth'));
orbit_assert('Database factory', class_exists('OrbitAdmin\\Db\\Database'));

echo "[db driver]\n";
$db = Database::instance();
orbit_assert('Driver instance', $db !== null, get_class($db));
orbit_assert('Users count > 0', $db->count('users') > 0, 'count=' . $db->count('users'));
orbit_assert('Find admin', (bool) $db->get('users', ['username' => 'admin']));

echo "[activity chain]\n";
$verify = ActivityLog::verify();
orbit_assert('Chain ok', $verify['ok'] === true, json_encode($verify));

echo "[validator]\n";
$v = new Validator(['email' => 'not-an-email']);
$v->required('email')->email('email');
orbit_assert('Catches invalid email', $v->fails());

$v2 = new Validator(['email' => 'ok@example.com']);
$v2->required('email')->email('email');
orbit_assert('Accepts valid email', $v2->passes());

echo "[CSRF]\n";
$_SESSION = $_SESSION ?? [];
$token = Csrf::token();
orbit_assert('Token non-empty', strlen($token) > 30);
orbit_assert('Token matches itself', Csrf::check($token));
orbit_assert('Token rejects garbage', !Csrf::check('nope'));

echo "[rate limiter]\n";
$limiter = new RateLimiter(sys_get_temp_dir() . '/orbit_rl_' . bin2hex(random_bytes(3)) . '.json');
$r1 = $limiter->hit('test', 2, 60);
$r2 = $limiter->hit('test', 2, 60);
$r3 = $limiter->hit('test', 2, 60);
orbit_assert('First hit allowed', $r1['allowed']);
orbit_assert('Second hit allowed', $r2['allowed']);
orbit_assert('Third hit blocked', !$r3['allowed']);

echo "[mailer]\n";
$rendered = Mailer::render('Hi {{name}}, welcome to {{app}}.', ['name' => 'Astra', 'app' => 'OrbitAdmin']);
orbit_assert('Template substitution', strpos($rendered, 'Astra') !== false && strpos($rendered, 'OrbitAdmin') !== false);

echo "[json driver writes]\n";
$id = $db->insert('settings', ['key' => 'smoke_test_' . bin2hex(random_bytes(3)), 'value' => 'ok']);
orbit_assert('Insert returns id', $id > 0, 'id=' . $id);
orbit_assert('Insert persisted', (bool) $db->get('settings', ['id' => $id]));
$db->delete('settings', $id);
orbit_assert('Delete works', !$db->get('settings', ['id' => $id]));

echo "\nResult: $pass passed, $fail failed\n";
if ($fail) {
    foreach ($failed as $f) {
        echo "  - $f\n";
    }
    exit(1);
}
exit(0);
