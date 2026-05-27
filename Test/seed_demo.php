<?php
/**
 * Generates the demo JSON seed at data/json.example/.
 * Invoked by `php bin/orbit demo:seed` (we run it directly for the live demo).
 */

if (!defined('ORBIT_INIT')) {
    require_once __DIR__ . '/../bootstrap.php';
}

use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Config;

$dir = Config::get('BASE_PATH') . '/data/json.example';
if (!is_dir($dir)) {
    @mkdir($dir, 0700, true);
}

function orbit_seed_write(string $dir, string $table, array $rows): void {
    $maxId = 0;
    foreach ($rows as $r) {
        if (!empty($r['id'])) {
            $maxId = max($maxId, (int) $r['id']);
        }
    }
    $payload = ['next_id' => $maxId + 1, 'rows' => $rows];
    $file = $dir . '/' . $table . '.json';
    file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

$now = time();

$users = [
    ['id'=>1,'username'=>'admin','email'=>'admin@example.com','name'=>'Astra Nova','password_hash'=>Auth::hash('OrbitDemo!2026'),'role'=>'Owner','active'=>1,'last_login_at'=>date('c', $now - 600),'last_login_ip'=>'10.0.0.10','created_at'=>date('c', $now - 86400 * 30)],
    ['id'=>2,'username'=>'editor','email'=>'editor@example.com','name'=>'Mira Pulsar','password_hash'=>Auth::hash('OrbitDemo!2026'),'role'=>'Editor','active'=>1,'last_login_at'=>date('c', $now - 7200),'last_login_ip'=>'10.0.0.11','created_at'=>date('c', $now - 86400 * 25)],
    ['id'=>3,'username'=>'viewer','email'=>'viewer@example.com','name'=>'Kai Quasar','password_hash'=>Auth::hash('OrbitDemo!2026'),'role'=>'Viewer','active'=>1,'last_login_at'=>date('c', $now - 86400),'last_login_ip'=>'10.0.0.12','created_at'=>date('c', $now - 86400 * 20)],
    ['id'=>4,'username'=>'sigma','email'=>'sigma@example.com','name'=>'Sigma Helix','password_hash'=>Auth::hash('OrbitDemo!2026'),'role'=>'Editor','active'=>1,'last_login_at'=>null,'last_login_ip'=>null,'created_at'=>date('c', $now - 86400 * 14)],
    ['id'=>5,'username'=>'nova','email'=>'nova@example.com','name'=>'Nova Drift','password_hash'=>Auth::hash('OrbitDemo!2026'),'role'=>'Viewer','active'=>0,'last_login_at'=>null,'last_login_ip'=>null,'created_at'=>date('c', $now - 86400 * 7)],
];
orbit_seed_write($dir, 'users', $users);

$roles = [
    ['id'=>1,'name'=>'Owner','description'=>'Full access to every module.','permissions'=>json_encode(['*']),'created_at'=>date('c', $now - 86400 * 30)],
    ['id'=>2,'name'=>'Editor','description'=>'Can create and modify content.','permissions'=>json_encode(['users.view','users.edit','roles.view','activity.view','tokens.view','tokens.create','emails.view','emails.edit','files.view','files.upload','system.view','settings.view']),'created_at'=>date('c', $now - 86400 * 30)],
    ['id'=>3,'name'=>'Viewer','description'=>'Read-only access to non-sensitive pages.','permissions'=>json_encode(['users.view','roles.view','activity.view','tokens.view','emails.view','files.view','system.view','settings.view']),'created_at'=>date('c', $now - 86400 * 30)],
];
orbit_seed_write($dir, 'roles', $roles);

$permissions = [];
foreach (['users.view','users.create','users.edit','users.delete','roles.view','roles.edit','activity.view','tokens.view','tokens.create','tokens.revoke','emails.view','emails.edit','files.view','files.upload','files.delete','system.view','settings.view','settings.edit'] as $i => $key) {
    $permissions[] = ['id'=>$i+1,'key'=>$key,'label'=>ucfirst(str_replace(['.','_'], [': ',' '], $key)),'created_at'=>date('c', $now - 86400 * 30)];
}
orbit_seed_write($dir, 'permissions', $permissions);

// Activity log: build a hash-chained set
$actions = [
    ['admin','login.success','session#1'],
    ['admin','users.create','user#2'],
    ['admin','users.create','user#3'],
    ['admin','roles.update','role#2'],
    ['editor','login.success','session#2'],
    ['editor','users.edit','user#3'],
    ['editor','emails.update','email#1'],
    ['admin','token.create','token#1'],
    ['admin','settings.update',null],
    ['viewer','login.success','session#3'],
    ['admin','file.upload','demo-logo.png'],
    ['editor','users.create','user#4'],
    ['sigma','login.success','session#4'],
    ['admin','token.revoke','token#1'],
    ['admin','users.edit','user#5'],
    ['editor','emails.test','email#1'],
    ['admin','login.success','session#5'],
    ['admin','file.delete','old.log'],
    ['editor','file.upload','report.pdf'],
    ['admin','users.delete','user#9'],
    ['admin','token.create','token#2'],
    ['admin','settings.update',null],
    ['editor','login.success','session#6'],
    ['viewer','login.success','session#7'],
    ['admin','login.success','session#8'],
];

$activity = [];
$prev = str_repeat('0', 64);
foreach ($actions as $i => [$actor, $action, $target]) {
    $row = [
        'id'        => $i + 1,
        'user_id'   => $actor === 'admin' ? 1 : ($actor === 'editor' ? 2 : ($actor === 'viewer' ? 3 : 4)),
        'actor'     => $actor,
        'action'    => $action,
        'target'    => $target,
        'ip'        => '10.0.0.' . (10 + ($i % 6)),
        'ua'        => 'Mozilla/5.0 OrbitAdminDemo',
        'meta'      => json_encode(['note' => 'seeded demo entry']),
        'prev_hash' => $prev,
        'created_at'=> date('c', $now - (86400 * (14 - intdiv($i, 2)) + ($i * 1800))),
    ];
    $body = $row;
    unset($body['hash']);
    $row['hash'] = hash('sha256', $prev . json_encode(array_diff_key($body, ['prev_hash' => true]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $prev = $row['hash'];
    $activity[] = $row;
}
orbit_seed_write($dir, 'activity', $activity);

$tokens = [
    ['id'=>1,'user_id'=>1,'name'=>'CI pipeline (revoked)','prefix'=>'orb_qaz','last4'=>'r1xz','hash'=>hash('sha256','orb_demo_revoked'),'scopes'=>'read,write','expires_at'=>null,'last_used_at'=>date('c', $now - 86400),'revoked_at'=>date('c', $now - 7200),'created_at'=>date('c', $now - 86400 * 20)],
    ['id'=>2,'user_id'=>1,'name'=>'Mobile app','prefix'=>'orb_mob','last4'=>'a3kc','hash'=>hash('sha256','orb_demo_mobile'),'scopes'=>'read','expires_at'=>null,'last_used_at'=>date('c', $now - 3600),'revoked_at'=>null,'created_at'=>date('c', $now - 86400 * 5)],
];
orbit_seed_write($dir, 'tokens', $tokens);

$emailTemplates = [
    [
        'id'=>1,'slug'=>'welcome','subject'=>'Welcome to {{app}}',
        'body'=>'<p>Hi {{name}},</p><p>Your account at <strong>{{app}}</strong> is ready.</p><p>Signed up on {{date}}.</p><p>OrbitAdmin team</p>',
        'variables'=>json_encode(['name','app','date']),
        'created_at'=>date('c', $now - 86400 * 30),
    ],
    [
        'id'=>2,'slug'=>'password_reset','subject'=>'Reset your {{app}} password',
        'body'=>'<p>Hi {{name}},</p><p>Click the link to reset your password.</p><p>If you did not request this, ignore this message.</p>',
        'variables'=>json_encode(['name','app']),
        'created_at'=>date('c', $now - 86400 * 30),
    ],
];
orbit_seed_write($dir, 'email_templates', $emailTemplates);

$settings = [
    ['id'=>1,'key'=>'site_name','value'=>'OrbitAdmin Demo','created_at'=>date('c', $now - 86400 * 30)],
    ['id'=>2,'key'=>'support_email','value'=>'support@orbitadmin.dev','created_at'=>date('c', $now - 86400 * 30)],
    ['id'=>3,'key'=>'theme','value'=>'dark','created_at'=>date('c', $now - 86400 * 30)],
    ['id'=>4,'key'=>'maintenance_message','value'=>'','created_at'=>date('c', $now - 86400 * 30)],
];
orbit_seed_write($dir, 'settings', $settings);

$migrations = [
    ['id'=>1,'version'=>'0.1.0','applied_at'=>date('c', $now - 86400 * 30)],
];
orbit_seed_write($dir, '_migrations', $migrations);

echo "Seeded " . count(glob($dir . '/*.json') ?: []) . " JSON tables into $dir\n";
