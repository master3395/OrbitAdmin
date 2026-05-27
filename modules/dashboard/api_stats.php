<?php
use OrbitAdmin\Core\Auth;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

Auth::requireLogin();

$db = Database::instance();
$rows = $db->all('activity', [], 'created_at desc', 1000);

$buckets = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime('-' . $i . ' days'));
    $buckets[$day] = 0;
}

foreach ($rows as $row) {
    $ts = strtotime((string) ($row['created_at'] ?? ''));
    if ($ts === false) {
        continue;
    }
    $day = date('Y-m-d', $ts);
    if (isset($buckets[$day])) {
        $buckets[$day]++;
    }
}

$labels = array_map(static fn($d) => date('d/m', strtotime($d)), array_keys($buckets));
$values = array_values($buckets);

json_response([
    'labels' => $labels,
    'values' => $values,
]);
