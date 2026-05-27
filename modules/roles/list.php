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
$roles = $db->all('roles', [], 'name asc');

$pageTitle = __('nav.roles');
ob_start();
?>
<h1>Roles &amp; permissions</h1>
<p class="lead-muted">Three tiers ship with OrbitAdmin: Owner, Editor, Viewer. You can edit the permissions matrix for each.</p>

<div class="orbit-card">
    <table class="orbit-table">
        <thead><tr><th>#</th><th>Role</th><th>Description</th><th>Permissions</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($roles as $r): ?>
                <?php
                    $perms = [];
                    $raw = $r['permissions'] ?? '';
                    if (is_string($raw) && $raw !== '') {
                        $decoded = json_decode($raw, true);
                        if (is_array($decoded)) { $perms = $decoded; }
                    } elseif (is_array($raw)) {
                        $perms = $raw;
                    }
                ?>
                <tr>
                    <td><?= (int) ($r['id'] ?? 0) ?></td>
                    <td><strong><?= e((string) ($r['name'] ?? '')) ?></strong></td>
                    <td><?= e((string) ($r['description'] ?? '')) ?></td>
                    <td>
                        <?php foreach (array_slice($perms, 0, 6) as $p): ?>
                            <span class="orbit-badge"><?= e((string) $p) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($perms) > 6): ?>
                            <span class="orbit-badge purple">+<?= count($perms) - 6 ?> more</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right">
                        <a class="orbit-pill" href="<?= e(Url::to('/roles/' . (int) $r['id'] . '/edit')) ?>"><i class="bi bi-pencil"></i> Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
