<?php
use OrbitAdmin\Core\Config;
use OrbitAdmin\Core\Url;
?>
<!doctype html>
<html lang="<?= e((string) Config::get('APP_LOCALE', 'en')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1020">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title><?= e($pageTitle ?? __('app.name')) ?> &middot; <?= e((string) Config::get('APP_NAME')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(Url::asset('img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(Url::asset('vendor/bootstrap/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(Url::asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(Url::asset('css/orbit.css')) ?>">
</head>
