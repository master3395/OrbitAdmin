<?php
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Demo;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Core\Logger;
use OrbitAdmin\Core\Url;
use OrbitAdmin\Core\Validator;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireRole('Owner', 'Editor');

$db = Database::instance();
$errors = [];
$old = ['username' => '', 'email' => '', 'name' => '', 'role' => 'Viewer', 'active' => '1'];
$roles = $db->all('roles', [], 'name asc');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = new Validator($_POST);
    $v->required('username', 'Username')->regex('username', '/^[a-zA-Z0-9_\-.]{3,64}$/', 'Username');
    $v->required('email', 'Email')->email('email');
    $v->required('password', 'Password')->minLen('password', 8);
    $v->required('role', 'Role');
    if ($v->passes()) {
        $u = (string) $_POST['username'];
        $em = strtolower((string) $_POST['email']);
        if ($db->get('users', ['username' => $u])) {
            $errors['username'] = 'Username already exists.';
        } elseif ($db->get('users', ['email' => $em])) {
            $errors['email'] = 'Email already exists.';
        } else {
            $id = $db->insert('users', [
                'username' => $u,
                'email' => $em,
                'name' => trim((string) ($_POST['name'] ?? '')) ?: $u,
                'password_hash' => Auth::hash((string) $_POST['password']),
                'role' => (string) $_POST['role'],
                'active' => isset($_POST['active']) ? 1 : 0,
            ]);
            (new Logger())->info('users.create', ['id' => $id]);
            Flash::success('User created.');
            redirect('/users');
        }
    } else {
        $errors = $v->errors;
    }
    $old = array_merge($old, $_POST);
}

$pageTitle = 'New user';
ob_start();
?>
<h1>New user</h1>
<p class="lead-muted">Create a new account and assign a role.</p>

<form method="post" class="orbit-card orbit-form" style="max-width:680px">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <input id="username" name="username" class="form-control" value="<?= e((string) $old['username']) ?>" required>
        <?php if (isset($errors['username'])): ?><div class="text-danger small mt-1"><?= e($errors['username']) ?></div><?php endif; ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input id="email" name="email" type="email" class="form-control" value="<?= e((string) $old['email']) ?>" required>
        <?php if (isset($errors['email'])): ?><div class="text-danger small mt-1"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="name">Display name</label>
        <input id="name" name="name" class="form-control" value="<?= e((string) $old['name']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">Password (min 8)</label>
        <input id="password" name="password" type="password" class="form-control" required>
        <?php if (isset($errors['password'])): ?><div class="text-danger small mt-1"><?= e($errors['password']) ?></div><?php endif; ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="role">Role</label>
        <select id="role" name="role" class="form-select">
            <?php foreach ($roles as $r): ?>
                <option value="<?= e((string) $r['name']) ?>" <?= $old['role'] === $r['name'] ? 'selected' : '' ?>><?= e((string) $r['name']) ?></option>
            <?php endforeach; ?>
            <?php if (empty($roles)): ?>
                <option value="Owner">Owner</option>
                <option value="Editor">Editor</option>
                <option value="Viewer" selected>Viewer</option>
            <?php endif; ?>
        </select>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= !empty($old['active']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="active">Account is active</label>
    </div>
    <button class="orbit-btn"><i class="bi bi-person-plus"></i> Create user</button>
    <a class="orbit-btn ghost" href="<?= e(Url::to('/users')) ?>">Cancel</a>
</form>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
