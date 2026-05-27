<?php
use OrbitAdmin\Activity\ActivityLog;
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Config;
use OrbitAdmin\Core\Flash;
use OrbitAdmin\Core\Url;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireLogin();

$uploadsDir = Config::get('DATA_PATH') . '/uploads';
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0700, true);
}

$allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'pdf', 'txt', 'csv', 'json', 'log', 'zip'];
$allowedMime = [
    'image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp',
    'application/pdf', 'text/plain', 'text/csv', 'application/json',
    'application/zip', 'application/x-zip-compressed',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'upload');
    if ($action === 'upload') {
        if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $name = basename((string) $_FILES['file']['name']);
            $size = (int) ($_FILES['file']['size'] ?? 0);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mime = (string) ($_FILES['file']['type'] ?? '');
            if ($size > 8 * 1024 * 1024) {
                Flash::error('File too large (8 MB limit in demo).');
            } elseif (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
                Flash::error('Unsupported file type.');
            } else {
                $safe = bin2hex(random_bytes(8)) . '.' . $ext;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadsDir . '/' . $safe)) {
                    @chmod($uploadsDir . '/' . $safe, 0600);
                    ActivityLog::record('file.upload', $safe, ['original' => $name, 'size' => $size]);
                    Flash::success('Uploaded ' . $name . ' as ' . $safe);
                } else {
                    Flash::error('Upload failed.');
                }
            }
        } else {
            Flash::error('No file received.');
        }
    } elseif ($action === 'delete') {
        $target = basename((string) ($_POST['name'] ?? ''));
        if ($target && is_file($uploadsDir . '/' . $target)) {
            @unlink($uploadsDir . '/' . $target);
            ActivityLog::record('file.delete', $target);
            Flash::success('Deleted ' . $target);
        }
    }
    redirect('/files');
}

$files = [];
foreach (glob($uploadsDir . '/*') ?: [] as $f) {
    if (is_file($f)) {
        $files[] = [
            'name'  => basename($f),
            'size'  => filesize($f) ?: 0,
            'mtime' => filemtime($f) ?: 0,
        ];
    }
}
usort($files, static fn($a, $b) => ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0));

$pageTitle = __('nav.files');
ob_start();
?>
<div class="d-flex justify-content-between align-items-end" style="margin-bottom:14px">
    <div>
        <h1>File manager</h1>
        <p class="lead-muted">Uploads stay in <code>data/uploads/</code> (outside the web root).</p>
    </div>
</div>

<form method="post" action="<?= e(Url::to('/files/upload')) ?>" enctype="multipart/form-data" class="orbit-card orbit-form" style="margin-bottom:14px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload">
    <div class="d-flex" style="gap:8px;align-items:center;flex-wrap:wrap">
        <input type="file" name="file" class="form-control orbit-input" required>
        <button class="orbit-btn"><i class="bi bi-cloud-upload"></i> Upload</button>
        <small class="text-muted">Allowed: <?= e(implode(', ', $allowedExt)) ?></small>
    </div>
</form>

<div class="orbit-card">
    <table class="orbit-table">
        <thead><tr><th>Name</th><th>Size</th><th>Modified</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($files as $f): ?>
                <tr>
                    <td><code><?= e($f['name']) ?></code></td>
                    <td><?= e(\OrbitAdmin\Core\Helpers::shortBytes((int) $f['size'])) ?></td>
                    <td><?= e(format_datetime((int) $f['mtime'])) ?></td>
                    <td style="text-align:right">
                        <form method="post" action="<?= e(Url::to('/files/delete')) ?>" style="display:inline" data-confirm="Delete <?= e($f['name']) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="name" value="<?= e($f['name']) ?>">
                            <button class="orbit-pill" type="submit"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($files)): ?>
                <tr><td colspan="4" style="text-align:center;color:var(--orbit-text-muted);padding:24px">No files uploaded yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
require ORBIT_ROOT . '/views/layouts/app.php';
