<?php
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Url;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireLogin();
$db = Database::instance();

$q = trim((string) ($_GET['q'] ?? ''));
$users = $db->all('users', [], 'id asc', 200);
if ($q !== '') {
    $users = array_values(array_filter($users, static function ($u) use ($q) {
        $needle = strtolower($q);
        foreach (['username', 'email', 'name', 'role'] as $col) {
            if (isset($u[$col]) && strpos(strtolower((string) $u[$col]), $needle) !== false) {
                return true;
            }
        }
        return false;
    }));
}

$pageTitle = __('nav.users');
ob_start();
?>
<div class="d-flex justify-content-between align-items-center" style="margin-bottom:14px">
    <div>
        <h1>Users</h1>
        <p class="lead-muted">Manage humans and service accounts.</p>
    </div>
    <a class="orbit-btn" href="<?= e(Url::to('/users/create')) ?>"><i class="bi bi-person-plus"></i> New user</a>
</div>

<div class="orbit-card">
    <form method="get" class="d-flex" style="gap:8px;margin-bottom:14px">
        <input type="text" name="q" class="form-control orbit-input" placeholder="Search by name, username, email, role" value="<?= e($q) ?>">
        <button class="orbit-btn ghost"><i class="bi bi-search"></i></button>
    </form>

    <table class="orbit-table">
        <thead>
            <tr><th>#</th><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int) ($u['id'] ?? 0) ?></td>
                    <td>
                        <strong><?= e((string) ($u['name'] ?? $u['username'] ?? '')) ?></strong><br>
                        <small style="color:var(--orbit-text-muted)"><?= e((string) ($u['username'] ?? '')) ?></small>
                    </td>
                    <td><?= e((string) ($u['email'] ?? '')) ?></td>
                    <td><span class="orbit-badge cyan"><?= e((string) ($u['role'] ?? 'Viewer')) ?></span></td>
                    <td><?= !empty($u['active']) ? '<span class="orbit-badge green">Active</span>' : '<span class="orbit-badge danger">Disabled</span>' ?></td>
                    <td><?= e(format_datetime((string) ($u['last_login_at'] ?? ''))) ?></td>
                    <td style="text-align:right">
                        <a class="orbit-pill" href="<?= e(Url::to('/users/' . (int) $u['id'] . '/edit')) ?>"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= e(Url::to('/users/' . (int) $u['id'] . '/delete')) ?>" style="display:inline" data-confirm="Delete user <?= e((string) ($u['username'] ?? '')) ?>?">
                            <?= csrf_field() ?>
                            <button class="orbit-pill" type="submit" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--orbit-text-muted);padding:24px">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
