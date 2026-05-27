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
$templates = $db->all('email_templates', [], 'slug asc', 200);

$pageTitle = __('nav.emails');
ob_start();
?>
<h1>Email templates</h1>
<p class="lead-muted">Reusable templates with <code>{{variable}}</code> placeholders.</p>

<div class="orbit-card">
    <table class="orbit-table">
        <thead><tr><th>#</th><th>Slug</th><th>Subject</th><th>Variables</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($templates as $t): ?>
                <?php
                    $vars = [];
                    $rawVars = $t['variables'] ?? '';
                    if (is_string($rawVars) && $rawVars !== '') {
                        $decoded = json_decode($rawVars, true);
                        if (is_array($decoded)) { $vars = $decoded; }
                    } elseif (is_array($rawVars)) {
                        $vars = $rawVars;
                    }
                ?>
                <tr>
                    <td><?= (int) ($t['id'] ?? 0) ?></td>
                    <td><code><?= e((string) ($t['slug'] ?? '')) ?></code></td>
                    <td><?= e((string) ($t['subject'] ?? '')) ?></td>
                    <td>
                        <?php foreach (array_slice($vars, 0, 4) as $v): ?>
                            <span class="orbit-badge">{{<?= e((string) $v) ?>}}</span>
                        <?php endforeach; ?>
                        <?php if (count($vars) > 4): ?>
                            <span class="orbit-badge purple">+<?= count($vars) - 4 ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right">
                        <a class="orbit-pill" href="<?= e(Url::to('/emails/' . (int) $t['id'] . '/edit')) ?>"><i class="bi bi-pencil"></i> Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($templates)): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--orbit-text-muted);padding:24px">No templates yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
