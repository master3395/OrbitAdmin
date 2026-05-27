<?php
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Url;
use OrbitAdmin\Core\View;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireLogin();

$db = Database::instance();

$counts = [
    'users'    => $db->count('users'),
    'roles'    => $db->count('roles'),
    'activity' => $db->count('activity'),
    'tokens'   => $db->count('tokens'),
];

$recent = $db->all('activity', [], 'created_at desc', 8);

$pageTitle = __('nav.dashboard');
ob_start();
?>
<div class="d-flex justify-content-between align-items-end" style="margin-bottom:14px">
    <div>
        <h1>Mission overview</h1>
        <p class="lead-muted">Real-time pulse of your OrbitAdmin instance.</p>
    </div>
    <div class="d-none d-md-flex" style="gap:8px">
        <a class="orbit-pill" href="<?= e(Url::to('/users/create')) ?>"><i class="bi bi-person-plus"></i> New user</a>
        <a class="orbit-pill" href="<?= e(Url::to('/tokens/create')) ?>"><i class="bi bi-key"></i> New token</a>
    </div>
</div>

<div class="orbit-grid">
    <?= View::component('stat_card', ['label' => 'Users',    'value' => $counts['users'],    'icon' => 'bi-people',     'variant' => '']) ?>
    <?= View::component('stat_card', ['label' => 'Roles',    'value' => $counts['roles'],    'icon' => 'bi-shield-lock', 'variant' => 'purple']) ?>
    <?= View::component('stat_card', ['label' => 'Events',   'value' => $counts['activity'], 'icon' => 'bi-activity',   'variant' => 'green']) ?>
    <?= View::component('stat_card', ['label' => 'API tokens','value' => $counts['tokens'],  'icon' => 'bi-key',        'variant' => 'amber']) ?>
</div>

<div class="orbit-grid-2" style="margin-top:18px">
    <div class="orbit-card">
        <div class="d-flex justify-content-between align-items-center" style="margin-bottom:8px">
            <h3 style="margin:0">Activity (last 14 days)</h3>
            <span class="orbit-badge cyan"><i class="bi bi-graph-up"></i> live</span>
        </div>
        <canvas id="orbitActivityChart" height="120" aria-label="Activity chart"></canvas>
    </div>

    <div class="orbit-card">
        <h3 style="margin-top:0">Recent activity</h3>
        <?php if (!$recent): ?>
            <p class="lead-muted">No activity yet.</p>
        <?php else: ?>
            <table class="orbit-table">
                <thead><tr><th>Actor</th><th>Action</th><th>When</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td><?= e((string) ($row['actor'] ?? '')) ?></td>
                        <td><span class="orbit-badge"><?= e((string) ($row['action'] ?? '')) ?></span></td>
                        <td><?= e(format_datetime((string) ($row['created_at'] ?? ''))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <a class="orbit-pill" href="<?= e(Url::to('/activity')) ?>" style="margin-top:8px"><i class="bi bi-arrow-right"></i> View all</a>
        <?php endif; ?>
    </div>
</div>

<?php
$extraScripts = [Url::asset('vendor/chartjs/chart.umd.js')];
$inlineScript = "fetch('" . Url::to('/api/dashboard/stats') . "')"
    . ".then(r => r.json()).then(payload => {"
    . " const ctx = document.getElementById('orbitActivityChart');"
    . " if (!ctx || !window.Chart) return;"
    . " const gradient = ctx.getContext('2d').createLinearGradient(0,0,0,200);"
    . " gradient.addColorStop(0, 'rgba(34,211,238,0.55)');"
    . " gradient.addColorStop(1, 'rgba(217,70,239,0)');"
    . " new Chart(ctx, { type: 'line', data: { labels: payload.labels, datasets: [{ label: 'Events', data: payload.values, borderColor: '#22d3ee', backgroundColor: gradient, tension: 0.35, fill: true, pointRadius: 0 }] }, options: { plugins:{legend:{display:false}}, scales:{x:{grid:{color:'rgba(154,195,255,0.08)'},ticks:{color:'#9aa6cf'}},y:{grid:{color:'rgba(154,195,255,0.08)'},ticks:{color:'#9aa6cf'}}} } });"
    . "}).catch(()=>{});";
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
