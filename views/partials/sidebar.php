<?php
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Config;
use OrbitAdmin\Core\Url;

$role = Auth::role();
$navMain = [
    ['/', 'bi-grid-1x2', __('nav.dashboard')],
    ['/users', 'bi-people', __('nav.users')],
    ['/roles', 'bi-shield-lock', __('nav.roles')],
    ['/activity', 'bi-activity', __('nav.activity')],
];
$navOps = [
    ['/tokens', 'bi-key', __('nav.tokens')],
    ['/emails', 'bi-envelope', __('nav.emails')],
    ['/files', 'bi-folder2-open', __('nav.files')],
];
$navSystem = [
    ['/system', 'bi-cpu', __('nav.system')],
    ['/settings', 'bi-gear', __('nav.settings')],
];
?>
<aside class="orbit-sidebar" aria-label="Primary navigation">
    <div class="orbit-brand">
        <div class="orbit-logo" aria-hidden="true"></div>
        <div>
            <div class="orbit-name"><?= e((string) Config::get('APP_NAME')) ?></div>
            <div class="orbit-version">v<?= e((string) Config::get('APP_VERSION')) ?> &middot; <?= e(strtoupper((string) Config::get('DB_DRIVER'))) ?></div>
        </div>
    </div>
    <nav class="orbit-nav">
        <div class="label">Overview</div>
        <?php foreach ($navMain as [$path, $icon, $label]): ?>
            <a href="<?= e(Url::to($path)) ?>" class="<?= Url::isActive($path) ? 'active' : '' ?>">
                <i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>

        <div class="label">Operations</div>
        <?php foreach ($navOps as [$path, $icon, $label]): ?>
            <a href="<?= e(Url::to($path)) ?>" class="<?= Url::isActive($path) ? 'active' : '' ?>">
                <i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>

        <div class="label">System</div>
        <?php foreach ($navSystem as [$path, $icon, $label]): ?>
            <a href="<?= e(Url::to($path)) ?>" class="<?= Url::isActive($path) ? 'active' : '' ?>">
                <i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="orbit-sidebar-footer">
        <div>Signed in as <strong><?= e($_SESSION['user_name'] ?? 'guest') ?></strong></div>
        <div>Role: <span class="orbit-badge cyan"><?= e($role) ?></span></div>
        <?php if (!empty($appDemo)): ?>
            <div style="margin-top:8px;color:#fda4af">Demo mode active</div>
        <?php endif; ?>
    </div>
</aside>
