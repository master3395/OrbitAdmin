<?php
use OrbitAdmin\Activity\ActivityLog;
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Config;
use OrbitAdmin\Core\Demo;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireLogin();

$db = Database::instance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Config::isDemo()) {
        Flash::warn('Demo mode: settings are read-only.');
        redirect('/settings');
    }
    foreach (['site_name', 'support_email', 'theme', 'maintenance_message'] as $k) {
        if (isset($_POST[$k])) {
            $row = $db->get('settings', ['key' => $k]);
            if ($row) {
                $db->update('settings', (int) $row['id'], ['value' => (string) $_POST[$k]]);
            } else {
                $db->insert('settings', ['key' => $k, 'value' => (string) $_POST[$k]]);
            }
        }
    }
    ActivityLog::record('settings.update');
    Flash::success('Settings saved.');
    redirect('/settings');
}

$current = [];
foreach ($db->all('settings') as $row) {
    $current[$row['key']] = $row['value'] ?? '';
}

$pageTitle = __('nav.settings');
ob_start();
?>
<h1>Settings</h1>
<p class="lead-muted">General configuration. Secrets always live in <code>config.php</code>, never here.</p>

<form method="post" class="orbit-card orbit-form">
    <?= csrf_field() ?>
    <div class="orbit-grid">
        <div>
            <label class="form-label" for="site_name">Site name</label>
            <input id="site_name" name="site_name" class="form-control" value="<?= e((string) ($current['site_name'] ?? Config::get('APP_NAME'))) ?>">
        </div>
        <div>
            <label class="form-label" for="support_email">Support email</label>
            <input id="support_email" name="support_email" type="email" class="form-control" value="<?= e((string) ($current['support_email'] ?? '')) ?>">
        </div>
        <div>
            <label class="form-label" for="theme">Default theme</label>
            <select id="theme" name="theme" class="form-select">
                <?php $t = $current['theme'] ?? 'dark'; ?>
                <option value="dark"  <?= $t === 'dark'  ? 'selected' : '' ?>>Dark</option>
                <option value="light" <?= $t === 'light' ? 'selected' : '' ?>>Light</option>
            </select>
        </div>
        <div>
            <label class="form-label" for="maintenance_message">Maintenance message</label>
            <input id="maintenance_message" name="maintenance_message" class="form-control" placeholder="Visible to operators in the sidebar footer" value="<?= e((string) ($current['maintenance_message'] ?? '')) ?>">
        </div>
    </div>
    <div style="margin-top:18px">
        <button class="orbit-btn"><i class="bi bi-save"></i> Save settings</button>
    </div>
</form>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
