<?php
use OrbitAdmin\Core\Flash;
$flashes = Flash::pull();
require __DIR__ . '/../partials/head.php';
?>
<body>
<div class="orbit-auth">
    <div class="orbit-orb left"></div>
    <div class="orbit-orb right"></div>
    <div class="orbit-auth-card">
        <?php foreach ($flashes as $f): ?>
            <div class="orbit-alert <?= e($f['type']) ?>" data-orbit-auto-dismiss role="status">
                <?= e($f['message']) ?>
            </div>
        <?php endforeach; ?>
        <?= $content ?? '' ?>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
