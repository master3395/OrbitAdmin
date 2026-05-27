<?php
use OrbitAdmin\Core\Url;
ob_start();
?>
<div class="orbit-card" style="max-width:560px;margin:60px auto;text-align:center">
    <div style="font-size:3.2rem;font-weight:800;letter-spacing:.5px">500</div>
    <p class="lead-muted"><?= e($errorMessage ?? 'Something went wrong.') ?></p>
    <a class="orbit-btn" href="<?= e(Url::to('/')) ?>"><i class="bi bi-arrow-left"></i> Back to dashboard</a>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/app.php';
