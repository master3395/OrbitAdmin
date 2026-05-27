<?php
use OrbitAdmin\Activity\ActivityLog;
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Config;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Core\Logger;
use OrbitAdmin\Core\Security;
use OrbitAdmin\Core\Validator;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

if (Config::isInstalled()) {
    Flash::info('Installer is locked. Delete data/.installed to re-run.');
    redirect('/');
}

$params = $params ?? ($GLOBALS['orbit_route_params'] ?? []);
$step = isset($params['step']) ? (string) $params['step'] : '1';

$pageTitle = 'Install OrbitAdmin';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['finalize'])) {
        $driver = (string) ($_POST['driver'] ?? 'json');
        if (!in_array($driver, ['json', 'sqlite', 'mysql'], true)) {
            $driver = 'json';
        }
        $v = new Validator($_POST);
        $v->required('admin_username', 'Username')->regex('admin_username', '/^[a-zA-Z0-9_\-.]{3,64}$/');
        $v->required('admin_email', 'Email')->email('admin_email');
        $v->required('admin_password', 'Password')->minLen('admin_password', 8);
        if ($v->fails()) {
            $errors = $v->errors;
        } else {
            $sample = require ORBIT_ROOT . '/config.sample.php';
            $sample['APP_KEY'] = Security::randomToken(32);
            $sample['DB_DRIVER'] = $driver;
            $sample['APP_DEMO'] = false;

            if ($driver === 'mysql') {
                $sample['MYSQL_HOST']     = (string) ($_POST['mysql_host'] ?? '127.0.0.1');
                $sample['MYSQL_PORT']     = (int)    ($_POST['mysql_port'] ?? 3306);
                $sample['MYSQL_DATABASE'] = (string) ($_POST['mysql_db']   ?? '');
                $sample['MYSQL_USER']     = (string) ($_POST['mysql_user'] ?? '');
                $sample['MYSQL_PASSWORD'] = (string) ($_POST['mysql_pass'] ?? '');
            }

            $sample['BASE_URL'] = rtrim(preg_replace('#/install$#', '', (string) Config::get('BASE_URL', '/OrbitAdmin')), '/');

            $php = "<?php\nreturn " . var_export($sample, true) . ";\n";
            if (@file_put_contents(ORBIT_ROOT . '/config.php', $php, LOCK_EX) === false) {
                $errors['config'] = 'Could not write config.php. Check permissions.';
            } else {
                @chmod(ORBIT_ROOT . '/config.php', 0600);
                Database::reset();
                Config::load(ORBIT_ROOT);
                $db = Database::instance();
                $db->migrate('up');

                if (!$db->get('roles', ['name' => 'Owner'])) {
                    $db->insert('roles', ['name' => 'Owner', 'description' => 'Full access', 'permissions' => json_encode(['*'])]);
                    $db->insert('roles', ['name' => 'Editor', 'description' => 'Can modify content', 'permissions' => json_encode(['users.view','activity.view','tokens.view'])]);
                    $db->insert('roles', ['name' => 'Viewer', 'description' => 'Read-only access', 'permissions' => json_encode(['users.view','activity.view'])]);
                }

                $db->insert('users', [
                    'username'      => (string) $_POST['admin_username'],
                    'email'         => strtolower((string) $_POST['admin_email']),
                    'name'          => (string) ($_POST['admin_username'] ?? 'Owner'),
                    'password_hash' => Auth::hash((string) $_POST['admin_password']),
                    'role'          => 'Owner',
                    'active'        => 1,
                ]);

                @file_put_contents(Config::get('DATA_PATH') . '/.installed', date('c'));
                @chmod(Config::get('DATA_PATH') . '/.installed', 0600);

                ActivityLog::record('install.finalize', 'orbitadmin', ['driver' => $driver]);
                (new Logger())->info('install.finalize', ['driver' => $driver]);

                Flash::success('OrbitAdmin is ready. Sign in with the account you just created.');
                redirect('/login');
            }
        }
    }
}

$serverChecks = [
    'PHP >= 7.4'        => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO loaded'        => extension_loaded('pdo'),
    'JSON ext'          => function_exists('json_encode'),
    'OpenSSL'           => extension_loaded('openssl'),
    'mb_string'         => extension_loaded('mbstring'),
    'data/ writable'    => is_writable(ORBIT_ROOT . '/data'),
    'logs/ writable'    => is_writable(ORBIT_ROOT . '/logs'),
    'config writable'   => is_writable(ORBIT_ROOT),
];
$allOk = !in_array(false, array_values($serverChecks), true);

ob_start();
?>
<h1>Install OrbitAdmin</h1>
<p class="lead-muted">Three steps to a working install.</p>

<div class="orbit-card">
    <h3 style="margin-top:0">1. Server readiness</h3>
    <table class="orbit-table">
        <?php foreach ($serverChecks as $label => $ok): ?>
            <tr>
                <td><?= e((string) $label) ?></td>
                <td><?= $ok ? '<span class="orbit-badge green">OK</span>' : '<span class="orbit-badge danger">Missing</span>' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<form method="post" class="orbit-card orbit-form" style="margin-top:14px">
    <?= csrf_field() ?>
    <input type="hidden" name="finalize" value="1">
    <h3 style="margin-top:0">2. Database</h3>
    <div class="mb-3">
        <label class="form-label" for="driver">Driver</label>
        <select id="driver" name="driver" class="form-select">
            <option value="json">JSON (no setup required)</option>
            <option value="sqlite">SQLite (single file)</option>
            <option value="mysql">MySQL / MariaDB</option>
        </select>
    </div>

    <div id="mysql-fields" style="display:none">
        <div class="orbit-grid">
            <div><label class="form-label">Host</label><input class="form-control" name="mysql_host" value="127.0.0.1"></div>
            <div><label class="form-label">Port</label><input class="form-control" name="mysql_port" value="3306"></div>
            <div><label class="form-label">Database</label><input class="form-control" name="mysql_db"></div>
            <div><label class="form-label">User</label><input class="form-control" name="mysql_user"></div>
            <div><label class="form-label">Password</label><input class="form-control" name="mysql_pass" type="password"></div>
        </div>
    </div>

    <h3 style="margin-top:14px">3. First admin user</h3>
    <div class="orbit-grid">
        <div>
            <label class="form-label" for="admin_username">Username</label>
            <input id="admin_username" name="admin_username" class="form-control" required value="admin">
            <?php if (isset($errors['admin_username'])): ?><div class="text-danger small mt-1"><?= e($errors['admin_username']) ?></div><?php endif; ?>
        </div>
        <div>
            <label class="form-label" for="admin_email">Email</label>
            <input id="admin_email" name="admin_email" type="email" class="form-control" required>
            <?php if (isset($errors['admin_email'])): ?><div class="text-danger small mt-1"><?= e($errors['admin_email']) ?></div><?php endif; ?>
        </div>
        <div>
            <label class="form-label" for="admin_password">Password (min 8)</label>
            <input id="admin_password" name="admin_password" type="password" class="form-control" required>
            <?php if (isset($errors['admin_password'])): ?><div class="text-danger small mt-1"><?= e($errors['admin_password']) ?></div><?php endif; ?>
        </div>
    </div>

    <div style="margin-top:18px">
        <button class="orbit-btn" <?= $allOk ? '' : 'disabled' ?>><i class="bi bi-rocket-takeoff"></i> Finalise install</button>
        <?php if (!$allOk): ?>
            <span class="orbit-badge danger">Resolve the missing items above first.</span>
        <?php endif; ?>
    </div>

    <?php if (isset($errors['config'])): ?>
        <div class="orbit-alert danger" style="margin-top:14px"><?= e($errors['config']) ?></div>
    <?php endif; ?>
</form>

<?php
$inlineScript = "var s=document.getElementById('driver'),m=document.getElementById('mysql-fields');"
              . "function t(){m.style.display=s.value==='mysql'?'block':'none';}s.addEventListener('change',t);t();";
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
