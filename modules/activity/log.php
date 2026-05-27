<?php
use OrbitAdmin\Activity\ActivityLog;
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireLogin();
$db = Database::instance();

$rows = $db->all('activity', [], 'id desc', 200);
$verify = ActivityLog::verify();

$pageTitle = __('nav.activity');
ob_start();
?>
<div class="d-flex justify-content-between align-items-end" style="margin-bottom:14px">
    <div>
        <h1>Activity log</h1>
        <p class="lead-muted">Hash-chained timeline of who did what and when.</p>
    </div>
    <span class="orbit-badge <?= $verify['ok'] ? 'green' : 'danger' ?>">
        <i class="bi <?= $verify['ok'] ? 'bi-shield-check' : 'bi-shield-exclamation' ?>"></i>
        Chain <?= $verify['ok'] ? 'intact' : 'broken at #' . (int) $verify['broken_at'] ?>
        &middot; <?= (int) $verify['count'] ?> events
    </span>
</div>

<div class="orbit-card" style="padding:0">
    <table class="orbit-table">
        <thead>
            <tr><th>#</th><th>When</th><th>Actor</th><th>Action</th><th>Target</th><th>IP</th><th>Hash</th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int) ($r['id'] ?? 0) ?></td>
                    <td><?= e(format_datetime((string) ($r['created_at'] ?? ''))) ?></td>
                    <td><?= e((string) ($r['actor'] ?? '')) ?></td>
                    <td><span class="orbit-badge cyan"><?= e((string) ($r['action'] ?? '')) ?></span></td>
                    <td><?= e((string) ($r['target'] ?? '')) ?></td>
                    <td><code><?= e((string) ($r['ip'] ?? '')) ?></code></td>
                    <td><code title="<?= e((string) ($r['hash'] ?? '')) ?>"><?= e(substr((string) ($r['hash'] ?? ''), 0, 10)) ?>...</code></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--orbit-text-muted);padding:24px">No events recorded yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
