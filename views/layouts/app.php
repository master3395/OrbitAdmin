<?php
use OrbitAdmin\Core\Demo;
use OrbitAdmin\Core\Flash;

$flashes = Flash::pull();
require __DIR__ . '/../partials/head.php';
?>
<body>
<?= Demo::bannerHtml() ?>
<div class="orbit-shell">
    <?php require __DIR__ . '/../partials/sidebar.php'; ?>
    <div>
        <?php require __DIR__ . '/../partials/topbar.php'; ?>
        <main class="orbit-page" id="orbit-main">
            <?php foreach ($flashes as $f): ?>
                <div class="orbit-alert <?= e($f['type']) ?>" data-orbit-auto-dismiss role="status">
                    <?= e($f['message']) ?>
                </div>
            <?php endforeach; ?>
            <?= $content ?? '' ?>
        </main>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
