<?php
use OrbitAdmin\Core\Url;
use OrbitAdmin\Core\View;
$body = ob_get_level() ? null : null;
ob_start();
?>
<div class="orbit-card" style="max-width:520px;margin:60px auto;text-align:center">
    <div style="font-size:3.2rem;font-weight:800;letter-spacing:.5px">404</div>
    <p class="lead-muted">We could not find that page.</p>
    <a class="orbit-btn" href="<?= e(Url::to('/')) ?>"><i class="bi bi-arrow-left"></i> Back to dashboard</a>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/app.php';
