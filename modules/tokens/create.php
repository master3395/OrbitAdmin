<?php
use OrbitAdmin\Activity\ActivityLog;
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Demo;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Core\Security;
use OrbitAdmin\Core\Url;
use OrbitAdmin\Core\Validator;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireLogin();

$db = Database::instance();
$generated = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Demo::guard('install')) {
        // demo mode: allow tokens but flash a warning
    }
    $v = new Validator($_POST);
    $v->required('name', 'Token name')->maxLen('name', 190);
    if ($v->fails()) {
        $errors = $v->errors;
    } else {
        $secretBody = Security::randomToken(20);
        $secret = 'orb_' . $secretBody;
        $prefix = substr($secret, 0, 7);
        $last4 = substr($secret, -4);
        $hash = hash('sha256', $secret);
        $scopes = (string) ($_POST['scopes'] ?? 'read');
        $id = $db->insert('tokens', [
            'user_id'    => (int) Auth::id(),
            'name'       => trim((string) $_POST['name']),
            'prefix'     => $prefix,
            'last4'      => $last4,
            'hash'       => $hash,
            'scopes'     => $scopes,
            'expires_at' => null,
            'revoked_at' => null,
        ]);
        ActivityLog::record('token.create', 'token#' . $id, ['scopes' => $scopes]);
        $generated = $secret;
        Flash::success('Token created. Copy it now, it will not be shown again.');
    }
}

$pageTitle = 'New API token';
ob_start();
?>
<h1>New API token</h1>
<p class="lead-muted">Tokens are hashed at rest. The full value is displayed once after creation.</p>

<?php if ($generated): ?>
    <div class="orbit-card" style="margin-bottom:14px;border:1px solid var(--orbit-accent)">
        <h3 style="margin-top:0">Your new token</h3>
        <p>Store this secret somewhere safe. OrbitAdmin will not show it again.</p>
        <code style="display:block;padding:12px;background:rgba(11,16,32,0.55);border-radius:10px;word-break:break-all"><?= e($generated) ?></code>
        <div style="margin-top:14px">
            <a class="orbit-btn" href="<?= e(Url::to('/tokens')) ?>"><i class="bi bi-arrow-left"></i> Back to tokens</a>
        </div>
    </div>
<?php else: ?>
    <form method="post" class="orbit-card orbit-form" style="max-width:600px">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="name">Token name</label>
            <input id="name" name="name" class="form-control" required maxlength="190" placeholder="CI pipeline, mobile app, etc.">
            <?php if (isset($errors['name'])): ?><div class="text-danger small mt-1"><?= e($errors['name']) ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="scopes">Scopes</label>
            <select id="scopes" name="scopes" class="form-select">
                <option value="read">read</option>
                <option value="read,write">read,write</option>
                <option value="read,write,admin">read,write,admin</option>
            </select>
        </div>
        <button class="orbit-btn"><i class="bi bi-key"></i> Generate token</button>
        <a class="orbit-btn ghost" href="<?= e(Url::to('/tokens')) ?>">Cancel</a>
    </form>
<?php endif; ?>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
