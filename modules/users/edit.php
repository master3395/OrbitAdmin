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

$params = $params ?? ($GLOBALS['orbit_route_params'] ?? []);
$id = (int) ($params['id'] ?? 0);

$db = Database::instance();
$user = $db->get('users', ['id' => $id]);
if (!$user) {
    Flash::error('User not found.');
    redirect('/users');
}

$errors = [];
$roles = $db->all('roles', [], 'name asc');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Demo::guard('edit', ['user_id' => $id])) {
        redirect('/users');
    }
    $v = new Validator($_POST);
    $v->required('email', 'Email')->email('email');
    $v->required('role', 'Role');
    if ($v->passes()) {
        $data = [
            'email'  => strtolower((string) $_POST['email']),
            'name'   => trim((string) ($_POST['name'] ?? '')) ?: ($user['username'] ?? ''),
            'role'   => (string) $_POST['role'],
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
        if (!empty($_POST['password'])) {
            if (strlen((string) $_POST['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters.';
            } else {
                $data['password_hash'] = Auth::hash((string) $_POST['password']);
            }
        }
        if (empty($errors)) {
            $db->update('users', $id, $data);
            (new Logger())->info('users.update', ['id' => $id]);
            Flash::success('User updated.');
            redirect('/users');
        }
    } else {
        $errors = $v->errors;
    }
    $user = array_merge($user, $_POST);
}

$pageTitle = 'Edit user';
ob_start();
?>
<h1>Edit user</h1>
<p class="lead-muted"><?= e((string) ($user['username'] ?? '')) ?> &middot; created <?= e(format_datetime((string) ($user['created_at'] ?? ''))) ?></p>

<form method="post" class="orbit-card orbit-form" style="max-width:680px">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Username</label>
        <input class="form-control" value="<?= e((string) ($user['username'] ?? '')) ?>" disabled>
    </div>
    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input id="email" name="email" type="email" class="form-control" value="<?= e((string) ($user['email'] ?? '')) ?>" required>
        <?php if (isset($errors['email'])): ?><div class="text-danger small mt-1"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="name">Display name</label>
        <input id="name" name="name" class="form-control" value="<?= e((string) ($user['name'] ?? '')) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">New password (leave blank to keep)</label>
        <input id="password" name="password" type="password" class="form-control">
        <?php if (isset($errors['password'])): ?><div class="text-danger small mt-1"><?= e($errors['password']) ?></div><?php endif; ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="role">Role</label>
        <select id="role" name="role" class="form-select">
            <?php foreach ($roles as $r): ?>
                <option value="<?= e((string) $r['name']) ?>" <?= ($user['role'] ?? '') === $r['name'] ? 'selected' : '' ?>><?= e((string) $r['name']) ?></option>
            <?php endforeach; ?>
            <?php if (empty($roles)): ?>
                <?php foreach (['Owner','Editor','Viewer'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($user['role'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= !empty($user['active']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="active">Account is active</label>
    </div>
    <button class="orbit-btn"><i class="bi bi-save"></i> Save</button>
    <a class="orbit-btn ghost" href="<?= e(Url::to('/users')) ?>">Cancel</a>
</form>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
