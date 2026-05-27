<?php
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Config;
use OrbitAdmin\Core\Csrf;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Core\Logger;
use OrbitAdmin\Core\RateLimiter;
use OrbitAdmin\Core\Security;
use OrbitAdmin\Core\Url;
use OrbitAdmin\Core\Validator;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

if (Auth::check()) {
    redirect('/');
}

$nextInput = isset($_POST['next']) ? (string) $_POST['next'] : (isset($_GET['next']) ? (string) $_GET['next'] : '/');
$next = Url::normalizeInternalPath($nextInput);
$errors = [];
$old = ['username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $limiter = new RateLimiter();
    $key = 'login:' . Security::clientIp();
    $hit = $limiter->hit($key, (int) Config::get('RATE_LOGIN_MAX', 8), (int) Config::get('RATE_LOGIN_WIN', 300));
    if (!$hit['allowed']) {
        Flash::error('Too many attempts. Try again in ' . $hit['retry_after'] . ' seconds.');
    } else {
        $v = new Validator($_POST);
        $v->required('username', 'Username')->required('password', 'Password');
        if ($v->fails()) {
            $errors = $v->errors;
        } else {
            if (Auth::attempt((string) $_POST['username'], (string) $_POST['password'])) {
                (new Logger())->info('login.success', [
                    'user' => $_POST['username'],
                    'ip' => Security::clientIp(),
                ]);
                $limiter->reset($key);
                Flash::success(__('auth.welcome'));
                $target = preg_match('#^/[A-Za-z0-9_\-/]*$#', $next) ? $next : '/';
                redirect($target);
            }
            (new Logger())->warn('login.failed', [
                'user' => $_POST['username'],
                'ip' => Security::clientIp(),
            ]);
            Flash::error(__('auth.failed'));
        }
    }
    $old['username'] = (string) ($_POST['username'] ?? '');
}

$pageTitle = __('auth.signin');
ob_start();
?>
<h1><?= e(__('app.name')) ?></h1>
<p class="muted"><?= e(__('app.tagline')) ?></p>

<form method="post" class="orbit-form" autocomplete="on" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">

    <div class="mb-3">
        <label class="form-label" for="username"><?= e(__('auth.username')) ?></label>
        <input id="username" name="username" type="text" class="form-control" required
               value="<?= e($old['username']) ?>" autocomplete="username" autofocus>
        <?php if (isset($errors['username'])): ?><div class="text-danger small mt-1"><?= e($errors['username']) ?></div><?php endif; ?>
    </div>

    <div class="mb-3">
        <label class="form-label" for="password"><?= e(__('auth.password')) ?></label>
        <input id="password" name="password" type="password" class="form-control" required autocomplete="current-password">
        <?php if (isset($errors['password'])): ?><div class="text-danger small mt-1"><?= e($errors['password']) ?></div><?php endif; ?>
    </div>

    <button type="submit" class="orbit-btn w-100"><i class="bi bi-shield-lock"></i> <?= e(__('auth.signin')) ?></button>
</form>

<?php if (Config::isDemo()): ?>
    <div class="orbit-alert info" style="margin-top:18px;font-size:0.85rem">
        <div><strong>Demo credentials</strong></div>
        <div>admin / OrbitDemo!2026</div>
        <div>editor / OrbitDemo!2026</div>
        <div>viewer / OrbitDemo!2026</div>
    </div>
<?php endif; ?>

<div style="margin-top:18px;font-size:0.8rem;color:var(--orbit-text-muted);text-align:center">
    OrbitAdmin v<?= e((string) Config::get('APP_VERSION')) ?> &middot;
    <a href="https://github.com/master3395/OrbitAdmin" target="_blank" rel="noopener">GitHub</a>
</div>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/auth.php';
