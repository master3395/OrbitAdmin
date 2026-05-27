<?php
use OrbitAdmin\Core\Url;
?>
<header class="orbit-topbar">
    <button class="orbit-iconbtn toggle-sidebar" type="button" data-orbit-toggle-sidebar aria-label="Toggle navigation">
        <i class="bi bi-list"></i>
    </button>

    <button type="button" class="orbit-search" data-orbit-palette-open aria-label="Open command palette">
        <i class="bi bi-search"></i>
        <span>Quick jump</span>
        <span class="ms-auto" style="margin-left:auto"><kbd>Ctrl</kbd> + <kbd>K</kbd></span>
    </button>

    <div class="spacer"></div>

    <a class="orbit-iconbtn" href="<?= e(Url::to('/system')) ?>" title="System info"><i class="bi bi-cpu"></i></a>
    <button class="orbit-iconbtn" type="button" data-orbit-theme-toggle title="Toggle theme">
        <i class="bi bi-circle-half"></i>
    </button>
    <a class="orbit-pill" href="<?= e(Url::to('/profile')) ?>">
        <i class="bi bi-person-circle"></i>
        <span><?= e($_SESSION['user_name'] ?? 'guest') ?></span>
    </a>
    <a class="orbit-iconbtn" href="<?= e(Url::to('/logout')) ?>" title="Sign out"><i class="bi bi-box-arrow-right"></i></a>
</header>
