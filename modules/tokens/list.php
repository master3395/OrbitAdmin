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
$tokens = $db->all('tokens', [], 'id desc', 200);
$me = Auth::user();

$pageTitle = __('nav.tokens');
ob_start();
?>
<div class="d-flex justify-content-between align-items-end" style="margin-bottom:14px">
    <div>
        <h1>API tokens</h1>
        <p class="lead-muted">Tokens are hashed with SHA-256 at rest. The full secret is shown once at creation only.</p>
    </div>
    <a class="orbit-btn" href="<?= e(Url::to('/tokens/create')) ?>"><i class="bi bi-plus-circle"></i> New token</a>
</div>

<div class="orbit-card">
    <table class="orbit-table">
        <thead><tr><th>#</th><th>Name</th><th>Prefix</th><th>Last 4</th><th>Owner</th><th>Created</th><th>Last used</th><th>Status</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($tokens as $t): ?>
                <?php $isMine = (int) ($t['user_id'] ?? 0) === (int) ($me['id'] ?? 0); ?>
                <tr>
                    <td><?= (int) ($t['id'] ?? 0) ?></td>
                    <td><strong><?= e((string) ($t['name'] ?? '')) ?></strong></td>
                    <td><code><?= e((string) ($t['prefix'] ?? '')) ?></code></td>
                    <td><code>****<?= e((string) ($t['last4'] ?? '')) ?></code></td>
                    <td><?= $isMine ? 'You' : ('User #' . (int) ($t['user_id'] ?? 0)) ?></td>
                    <td><?= e(format_datetime((string) ($t['created_at'] ?? ''))) ?></td>
                    <td><?= e(format_datetime((string) ($t['last_used_at'] ?? '')) ?: 'never') ?></td>
                    <td>
                        <?php if (!empty($t['revoked_at'])): ?>
                            <span class="orbit-badge danger">Revoked</span>
                        <?php else: ?>
                            <span class="orbit-badge green">Active</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right">
                        <?php if (empty($t['revoked_at'])): ?>
                            <form method="post" action="<?= e(Url::to('/tokens/' . (int) $t['id'] . '/revoke')) ?>" style="display:inline" data-confirm="Revoke this token?">
                                <?= csrf_field() ?>
                                <button class="orbit-pill" type="submit"><i class="bi bi-x-circle"></i> Revoke</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($tokens)): ?>
                <tr><td colspan="9" style="text-align:center;color:var(--orbit-text-muted);padding:24px">No tokens yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
