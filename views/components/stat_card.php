<?php
$variant = $variant ?? '';
$icon    = $icon ?? 'bi-graph-up';
$label   = $label ?? '';
$value   = $value ?? '';
$delta   = $delta ?? null;
?>
<div class="orbit-card">
    <div class="orbit-stat <?= e($variant) ?>">
        <div>
            <div class="label"><?= e($label) ?></div>
            <div class="value"><?= e((string) $value) ?></div>
            <?php if ($delta !== null): ?>
                <div class="orbit-badge <?= ($delta >= 0 ? 'green' : 'danger') ?>">
                    <?= $delta >= 0 ? '+' : '' ?><?= e((string) $delta) ?>%
                </div>
            <?php endif; ?>
        </div>
        <div class="icon"><i class="bi <?= e($icon) ?>"></i></div>
    </div>
</div>
