<?php
use OrbitAdmin\Core\Config;
use OrbitAdmin\Core\Url;

$nonce = $cspNonce ?? '';
$paletteLinks = [
    ['/', 'Dashboard'],
    ['/users', 'Users'],
    ['/roles', 'Roles & permissions'],
    ['/activity', 'Activity log'],
    ['/tokens', 'API tokens'],
    ['/emails', 'Email templates'],
    ['/files', 'File manager'],
    ['/system', 'System info'],
    ['/settings', 'Settings'],
    ['/profile', 'Profile'],
    ['/logout', 'Sign out'],
];
?>
<div class="orbit-palette" aria-hidden="true">
    <div class="orbit-palette-box">
        <input type="text" class="orbit-palette-input" placeholder="Jump to..." aria-label="Quick jump">
        <div class="orbit-palette-results">
            <?php foreach ($paletteLinks as [$path, $label]): ?>
                <a href="<?= e(Url::to($path)) ?>"><i class="bi bi-arrow-return-right"></i> <?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="<?= e(Url::asset('vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>" nonce="<?= e($nonce) ?>"></script>
<script src="<?= e(Url::asset('js/orbit.js')) ?>" nonce="<?= e($nonce) ?>"></script>
<?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $src): ?>
        <script src="<?= e($src) ?>" nonce="<?= e($nonce) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<?php if (!empty($inlineScript)): ?>
    <script nonce="<?= e($nonce) ?>"><?= $inlineScript /* trusted, set by controller */ ?></script>
<?php endif; ?>
