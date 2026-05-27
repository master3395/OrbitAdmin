<?php
use OrbitAdmin\Activity\ActivityLog;
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Config;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireLogin();

$verify = ActivityLog::verify();
$db = Database::instance();
$diskTotal = @disk_total_space(Config::get('DATA_PATH')) ?: 0;
$diskFree  = @disk_free_space(Config::get('DATA_PATH'))  ?: 0;
$diskUsed  = $diskTotal - $diskFree;
$diskPct   = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

$pageTitle = __('nav.system');
ob_start();
?>
<h1>System info</h1>
<p class="lead-muted">Snapshot of the OrbitAdmin runtime and host.</p>

<div class="orbit-grid">
    <div class="orbit-card">
        <h3 style="margin-top:0">Application</h3>
        <table class="orbit-table">
            <tr><th>App</th><td><?= e((string) Config::get('APP_NAME')) ?> v<?= e((string) Config::get('APP_VERSION')) ?></td></tr>
            <tr><th>Mode</th><td><?= Config::isDemo() ? '<span class="orbit-badge purple">Demo</span>' : '<span class="orbit-badge cyan">Live</span>' ?></td></tr>
            <tr><th>Locale</th><td><?= e((string) Config::get('APP_LOCALE')) ?></td></tr>
            <tr><th>Base URL</th><td><code><?= e((string) Config::get('BASE_URL')) ?></code></td></tr>
            <tr><th>Driver</th><td><?= e(strtoupper($db->name())) ?></td></tr>
            <tr><th>Activity chain</th><td><?= $verify['ok'] ? '<span class="orbit-badge green">Intact</span>' : '<span class="orbit-badge danger">Broken</span>' ?> (<?= (int) $verify['count'] ?> events)</td></tr>
        </table>
    </div>

    <div class="orbit-card">
        <h3 style="margin-top:0">Server</h3>
        <table class="orbit-table">
            <tr><th>PHP</th><td><?= e(PHP_VERSION) ?></td></tr>
            <tr><th>SAPI</th><td><?= e(PHP_SAPI) ?></td></tr>
            <tr><th>Software</th><td><?= e((string) ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown')) ?></td></tr>
            <tr><th>OS</th><td><?= e(PHP_OS) ?></td></tr>
            <tr><th>Timezone</th><td><?= e(date_default_timezone_get()) ?></td></tr>
            <tr><th>Memory limit</th><td><?= e((string) ini_get('memory_limit')) ?></td></tr>
            <tr><th>Max upload</th><td><?= e((string) ini_get('upload_max_filesize')) ?></td></tr>
            <tr><th>Extensions</th><td>pdo: <?= extension_loaded('pdo') ? 'yes' : 'no' ?> &middot; pdo_sqlite: <?= extension_loaded('pdo_sqlite') ? 'yes' : 'no' ?> &middot; pdo_mysql: <?= extension_loaded('pdo_mysql') ? 'yes' : 'no' ?></td></tr>
        </table>
    </div>

    <div class="orbit-card">
        <h3 style="margin-top:0">Disk</h3>
        <p>Data partition usage</p>
        <div style="position:relative;height:10px;border-radius:999px;background:rgba(154,195,255,0.12);overflow:hidden">
            <div style="position:absolute;left:0;top:0;bottom:0;width:<?= e((string) min(100, max(0, $diskPct))) ?>%;background:linear-gradient(90deg,var(--orbit-accent),var(--orbit-accent-2))"></div>
        </div>
        <table class="orbit-table" style="margin-top:8px">
            <tr><th>Used</th><td><?= e(\OrbitAdmin\Core\Helpers::shortBytes((int) $diskUsed)) ?> (<?= e((string) $diskPct) ?>%)</td></tr>
            <tr><th>Free</th><td><?= e(\OrbitAdmin\Core\Helpers::shortBytes((int) $diskFree)) ?></td></tr>
            <tr><th>Total</th><td><?= e(\OrbitAdmin\Core\Helpers::shortBytes((int) $diskTotal)) ?></td></tr>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
