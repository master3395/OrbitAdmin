<?php
namespace OrbitAdmin\Activity;

use OrbitAdmin\Core\Auth;
use OrbitAdmin\Core\Security;
use OrbitAdmin\Db\Database;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Append-only activity log with hash-chained rows. Every new row stores
 * the SHA-256 of the previous row's hash + the current row content.
 */
final class ActivityLog
{
    public static function record(string $action, ?string $target = null, array $meta = []): int
    {
        $db = Database::instance();
        $last = $db->all('activity', [], 'id desc', 1, 0);
        $prevHash = !empty($last[0]['hash']) ? (string) $last[0]['hash'] : str_repeat('0', 64);

        $user = Auth::user();
        $row = [
            'user_id'   => $user['id'] ?? null,
            'actor'     => $user['username'] ?? 'system',
            'action'    => $action,
            'target'    => $target,
            'ip'        => Security::clientIp(),
            'ua'        => Security::userAgent(),
            'meta'      => json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'prev_hash' => $prevHash,
        ];
        $row['hash'] = hash('sha256', $prevHash . json_encode(array_diff_key($row, ['prev_hash' => true, 'hash' => true]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $db->insert('activity', $row);
    }

    /**
     * Walk every activity row and confirm the hash chain is intact.
     * @return array{ok:bool,broken_at:?int,count:int}
     */
    public static function verify(): array
    {
        $db = Database::instance();
        $rows = $db->all('activity', [], 'id asc', 100000);
        $prev = str_repeat('0', 64);
        foreach ($rows as $row) {
            $body = $row;
            $stored = (string) ($body['hash'] ?? '');
            unset($body['hash']);
            $body['prev_hash'] = $prev;
            $expected = hash('sha256', $prev . json_encode(array_diff_key($body, ['prev_hash' => true]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            if (!hash_equals($expected, $stored)) {
                return ['ok' => false, 'broken_at' => (int) ($row['id'] ?? 0), 'count' => count($rows)];
            }
            $prev = $stored;
        }
        return ['ok' => true, 'broken_at' => null, 'count' => count($rows)];
    }
}
