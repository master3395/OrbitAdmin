<?php
use OrbitAdmin\Activity\ActivityLog;
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Core\Mailer;
use OrbitAdmin\Core\Url;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireLogin();

$params = $params ?? ($GLOBALS['orbit_route_params'] ?? []);
$id = (int) ($params['id'] ?? 0);

$db = Database::instance();
$tpl = $db->get('email_templates', ['id' => $id]);
if (!$tpl) {
    Flash::error('Template not found.');
    redirect('/emails');
}

$preview = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'save') {
        $vars = array_filter(array_map('trim', explode(',', (string) ($_POST['variables'] ?? ''))));
        $db->update('email_templates', $id, [
            'subject'   => (string) ($_POST['subject'] ?? ''),
            'body'      => (string) ($_POST['body'] ?? ''),
            'variables' => json_encode(array_values($vars), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        ActivityLog::record('email.update', 'email#' . $id);
        Flash::success('Template saved.');
        redirect('/emails/' . $id . '/edit');
    } elseif ($action === 'preview') {
        $sample = [
            'name' => 'Astra Nova',
            'app' => 'OrbitAdmin',
            'date' => date('d/m/Y'),
        ];
        $preview = [
            'subject' => Mailer::render((string) ($_POST['subject'] ?? ''), $sample),
            'body'    => Mailer::render((string) ($_POST['body'] ?? ''), $sample),
        ];
    } elseif ($action === 'send_test') {
        $to = (string) ($_POST['test_to'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Flash::error('Provide a valid test recipient.');
        } else {
            $vars = ['name' => 'Astra Nova', 'app' => 'OrbitAdmin', 'date' => date('d/m/Y')];
            $ok = Mailer::send($to, Mailer::render((string) ($_POST['subject'] ?? ''), $vars), Mailer::render((string) ($_POST['body'] ?? ''), $vars));
            ActivityLog::record('email.test', 'email#' . $id, ['to' => $to, 'ok' => $ok]);
            Flash::success($ok ? ('Test email queued for ' . $to) : 'Mailer failed; check logs.');
        }
        redirect('/emails/' . $id . '/edit');
    }
}

$varsList = [];
$rawVars = $tpl['variables'] ?? '';
if (is_string($rawVars) && $rawVars !== '') {
    $decoded = json_decode($rawVars, true);
    if (is_array($decoded)) { $varsList = $decoded; }
} elseif (is_array($rawVars)) {
    $varsList = $rawVars;
}

$pageTitle = 'Edit email template';
ob_start();
?>
<h1>Edit email template</h1>
<p class="lead-muted"><code><?= e((string) ($tpl['slug'] ?? '')) ?></code></p>

<form method="post" class="orbit-card orbit-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <div class="mb-3">
        <label class="form-label" for="subject">Subject</label>
        <input id="subject" name="subject" class="form-control" value="<?= e((string) ($tpl['subject'] ?? '')) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="body">Body (HTML)</label>
        <textarea id="body" name="body" rows="10" class="form-control" style="font-family:'JetBrains Mono', monospace"><?= e((string) ($tpl['body'] ?? '')) ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label" for="variables">Variables (comma-separated)</label>
        <input id="variables" name="variables" class="form-control" value="<?= e(implode(',', $varsList)) ?>">
        <small class="text-muted">Used by the preview and as documentation for integrators.</small>
    </div>
    <button class="orbit-btn"><i class="bi bi-save"></i> Save</button>
    <button class="orbit-btn ghost" name="action" value="preview"><i class="bi bi-eye"></i> Preview</button>
    <a class="orbit-btn ghost" href="<?= e(Url::to('/emails')) ?>">Back</a>
</form>

<?php if ($preview): ?>
    <div class="orbit-card" style="margin-top:18px">
        <h3 style="margin-top:0">Preview</h3>
        <div><strong>Subject:</strong> <?= e($preview['subject']) ?></div>
        <hr style="border-color:var(--orbit-border)">
        <div style="background:rgba(11,16,32,0.5);padding:16px;border-radius:10px"><?= $preview['body'] ?></div>
    </div>
<?php endif; ?>

<form method="post" class="orbit-card orbit-form" style="margin-top:18px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="send_test">
    <input type="hidden" name="subject" value="<?= e((string) ($tpl['subject'] ?? '')) ?>">
    <input type="hidden" name="body" value="<?= e((string) ($tpl['body'] ?? '')) ?>">
    <label class="form-label" for="test_to">Send a test</label>
    <div class="d-flex" style="gap:8px">
        <input id="test_to" name="test_to" type="email" class="form-control orbit-input" placeholder="you@example.com" style="max-width:320px">
        <button class="orbit-btn"><i class="bi bi-send"></i> Send test</button>
    </div>
    <small class="text-muted">In demo mode, test mails are logged to <code>logs/mail.log</code> rather than sent.</small>
</form>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
