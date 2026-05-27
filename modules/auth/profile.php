<?php
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Config;
use OrbitAdmin\Core\Csrf;
use OrbitAdmin\Core\Demo;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Core\Logger;
use OrbitAdmin\Core\Validator;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireLogin();
$db = Database::instance();
$me = Auth::user();
$userId = (int) ($me['id'] ?? 0);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'profile') {
        if (!Demo::guard('edit', ['user_id' => $userId])) {
            redirect('/profile');
        }
        $v = new Validator($_POST);
        $v->required('name', 'Name')->maxLen('name', 190);
        $v->required('email', 'Email')->email('email');
        if ($v->fails()) {
            $errors = $v->errors;
        } else {
            $db->update('users', $userId, [
                'name'  => trim((string) $_POST['name']),
                'email' => strtolower(trim((string) $_POST['email'])),
            ]);
            unset($_SESSION['_user_cache']);
            $_SESSION['user_name'] = trim((string) $_POST['name']);
            Flash::success('Profile updated.');
            (new Logger())->info('profile.update', ['user_id' => $userId]);
            redirect('/profile');
        }
    } elseif ($action === 'password') {
        if (!Demo::guard('password', ['user_id' => $userId])) {
            redirect('/profile');
        }
        $v = new Validator($_POST);
        $v->required('current_password', 'Current password');
        $v->required('new_password', 'New password')->minLen('new_password', 8);
        if ($v->fails()) {
            $errors = $v->errors;
        } else {
            $hash = (string) ($me['password_hash'] ?? '');
            $current = $db->get('users', ['id' => $userId])['password_hash'] ?? '';
            if (!password_verify((string) $_POST['current_password'], (string) $current)) {
                $errors['current_password'] = 'Current password does not match.';
            } else {
                $db->update('users', $userId, [
                    'password_hash' => Auth::hash((string) $_POST['new_password']),
                ]);
                Flash::success('Password changed.');
                (new Logger())->info('profile.password_change', ['user_id' => $userId]);
                redirect('/profile');
            }
        }
    }
}

$pageTitle = 'Profile';
ob_start();
?>
<h1>Profile</h1>
<p class="lead-muted">Update your personal info and password.</p>

<div class="orbit-grid-2">
    <div class="orbit-card orbit-form">
        <h3 style="margin-top:0">Personal info</h3>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">
            <div class="mb-3">
                <label class="form-label" for="name">Display name</label>
                <input id="name" name="name" class="form-control" value="<?= e((string) ($me['name'] ?? '')) ?>" maxlength="190">
                <?php if (isset($errors['name'])): ?><div class="text-danger small mt-1"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input id="email" name="email" type="email" class="form-control" value="<?= e((string) ($me['email'] ?? '')) ?>">
                <?php if (isset($errors['email'])): ?><div class="text-danger small mt-1"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input class="form-control" value="<?= e((string) ($me['username'] ?? '')) ?>" disabled>
            </div>
            <button class="orbit-btn"><i class="bi bi-save"></i> Save</button>
        </form>
    </div>

    <div class="orbit-card orbit-form">
        <h3 style="margin-top:0">Password</h3>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <div class="mb-3">
                <label class="form-label" for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password">
                <?php if (isset($errors['current_password'])): ?><div class="text-danger small mt-1"><?= e($errors['current_password']) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="new_password">New password (min 8)</label>
                <input id="new_password" name="new_password" type="password" class="form-control" autocomplete="new-password">
                <?php if (isset($errors['new_password'])): ?><div class="text-danger small mt-1"><?= e($errors['new_password']) ?></div><?php endif; ?>
            </div>
            <button class="orbit-btn"><i class="bi bi-shield-check"></i> Change password</button>
        </form>
    </div>
</div>

<div class="orbit-card" style="margin-top:18px">
    <h3 style="margin-top:0">Account</h3>
    <table class="orbit-table">
        <tr><th>Role</th><td><span class="orbit-badge cyan"><?= e((string) ($me['role'] ?? 'Viewer')) ?></span></td></tr>
        <tr><th>Active</th><td><?= !empty($me['active']) ? '<span class="orbit-badge green">Yes</span>' : '<span class="orbit-badge danger">No</span>' ?></td></tr>
        <tr><th>Created</th><td><?= e(format_datetime((string) ($me['created_at'] ?? ''))) ?></td></tr>
        <tr><th>Last login</th><td><?= e(format_datetime((string) ($me['last_login_at'] ?? ''))) ?></td></tr>
        <tr><th>Last IP</th><td><code><?= e((string) ($me['last_login_ip'] ?? '')) ?></code></td></tr>
    </table>
</div>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
