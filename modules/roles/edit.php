<?php
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Demo;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Core\Logger;
use OrbitAdmin\Core\Url;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireRole('Owner');

$params = $params ?? ($GLOBALS['orbit_route_params'] ?? []);
$id = (int) ($params['id'] ?? 0);

$db = Database::instance();
$role = $db->get('roles', ['id' => $id]);
if (!$role) {
    Flash::error('Role not found.');
    redirect('/roles');
}

$allPermissions = [
    'users.view', 'users.create', 'users.edit', 'users.delete',
    'roles.view', 'roles.edit',
    'activity.view',
    'tokens.view', 'tokens.create', 'tokens.revoke',
    'emails.view', 'emails.edit',
    'files.view', 'files.upload', 'files.delete',
    'system.view',
    'settings.view', 'settings.edit',
];

$current = [];
$raw = $role['permissions'] ?? '';
if (is_string($raw) && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) { $current = $decoded; }
} elseif (is_array($raw)) {
    $current = $raw;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Demo::guard('edit', ['role_id' => $id])) {
        redirect('/roles');
    }
    $selected = array_values(array_intersect($allPermissions, (array) ($_POST['perm'] ?? [])));
    $db->update('roles', $id, [
        'description' => trim((string) ($_POST['description'] ?? '')),
        'permissions' => json_encode($selected, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);
    (new Logger())->info('roles.update', ['id' => $id, 'permissions' => $selected]);
    Flash::success('Role updated.');
    redirect('/roles');
}

$pageTitle = 'Edit role: ' . (string) ($role['name'] ?? '');
ob_start();
?>
<h1>Edit role</h1>
<p class="lead-muted"><strong><?= e((string) ($role['name'] ?? '')) ?></strong></p>

<form method="post" class="orbit-card orbit-form">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="description">Description</label>
        <input id="description" name="description" class="form-control" value="<?= e((string) ($role['description'] ?? '')) ?>">
    </div>

    <h3 style="margin-top:14px">Permissions</h3>
    <div class="orbit-grid">
        <?php foreach ($allPermissions as $perm): ?>
            <label class="form-check" style="background:rgba(11,16,32,0.4);padding:10px 12px;border-radius:10px;border:1px solid var(--orbit-border)">
                <input class="form-check-input" type="checkbox" name="perm[]" value="<?= e($perm) ?>" <?= in_array($perm, $current, true) ? 'checked' : '' ?>>
                <span class="form-check-label" style="margin-left:6px"><?= e($perm) ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <div style="margin-top:18px">
        <button class="orbit-btn"><i class="bi bi-save"></i> Save</button>
        <a class="orbit-btn ghost" href="<?= e(Url::to('/roles')) ?>">Cancel</a>
    </div>
</form>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
