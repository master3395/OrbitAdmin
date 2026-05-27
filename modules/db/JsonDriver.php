<?php
namespace OrbitAdmin\Db;

use OrbitAdmin\Core\Config;
use RuntimeException;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * JSON flat-file driver. One file per table at data/json/<table>.json.
 * Atomic writes via temp file + rename. Suitable for small deployments.
 */
final class JsonDriver implements DriverInterface
{
    private string $dir;

    public function __construct()
    {
        $this->dir = (string) Config::get('DATA_PATH') . '/json';
    }

    public function name(): string { return 'json'; }

    public function ensureSchema(): void
    {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0700, true);
        }
        $example = (string) Config::get('BASE_PATH') . '/data/json.example';
        if (is_dir($example)) {
            foreach (glob($example . '/*.json') ?: [] as $src) {
                $dst = $this->dir . '/' . basename($src);
                if (!is_file($dst)) {
                    @copy($src, $dst);
                }
            }
        }
        foreach (['users', 'roles', 'permissions', 'activity', 'tokens', 'email_templates', 'settings', '_migrations'] as $t) {
            $f = $this->dir . '/' . $t . '.json';
            if (!is_file($f)) {
                $this->writeTable($t, ['next_id' => 1, 'rows' => []]);
            }
        }
    }

    public function get(string $table, array $where)
    {
        $rows = $this->all($table, $where, null, 1, 0);
        return $rows[0] ?? null;
    }

    public function all(string $table, array $where = [], ?string $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $data = $this->readTable($table);
        $rows = $data['rows'] ?? [];
        if ($where) {
            $rows = array_values(array_filter($rows, function ($row) use ($where) {
                foreach ($where as $k => $v) {
                    if (!array_key_exists($k, $row) || $row[$k] != $v) {
                        return false;
                    }
                }
                return true;
            }));
        }
        if ($orderBy) {
            [$col, $dir] = $this->parseOrder($orderBy);
            usort($rows, function ($a, $b) use ($col, $dir) {
                $av = $a[$col] ?? null; $bv = $b[$col] ?? null;
                if ($av == $bv) return 0;
                return ($av < $bv ? -1 : 1) * ($dir === 'desc' ? -1 : 1);
            });
        }
        if ($offset) {
            $rows = array_slice($rows, $offset);
        }
        if ($limit) {
            $rows = array_slice($rows, 0, $limit);
        }
        return $rows;
    }

    public function insert(string $table, array $data): int
    {
        $store = $this->readTable($table);
        $id = (int) ($store['next_id'] ?? 1);
        $data['id'] = $id;
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('c');
        }
        $store['rows'][] = $data;
        $store['next_id'] = $id + 1;
        $this->writeTable($table, $store);
        return $id;
    }

    public function update(string $table, int $id, array $data): bool
    {
        $store = $this->readTable($table);
        $updated = false;
        foreach ($store['rows'] as &$row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                $row = array_merge($row, $data, ['id' => $id, 'updated_at' => date('c')]);
                $updated = true;
                break;
            }
        }
        unset($row);
        if ($updated) {
            $this->writeTable($table, $store);
        }
        return $updated;
    }

    public function delete(string $table, int $id): bool
    {
        $store = $this->readTable($table);
        $before = count($store['rows']);
        $store['rows'] = array_values(array_filter($store['rows'], static fn($r) => (int) ($r['id'] ?? 0) !== $id));
        $changed = count($store['rows']) !== $before;
        if ($changed) {
            $this->writeTable($table, $store);
        }
        return $changed;
    }

    public function count(string $table, array $where = []): int
    {
        return count($this->all($table, $where));
    }

    public function query(string $sql, array $params = []): array
    {
        // JSON driver does not support arbitrary SQL; expose a minimal helper for tests.
        return [];
    }

    public function transaction(callable $fn)
    {
        // No real transactions in JSON; best-effort.
        return $fn($this);
    }

    public function migrate(string $direction = 'up'): array
    {
        $this->ensureSchema();
        $store = $this->readTable('_migrations');
        if (!array_filter($store['rows'], static fn($r) => ($r['version'] ?? '') === '0.1.0')) {
            $this->insert('_migrations', ['version' => '0.1.0', 'applied_at' => date('c')]);
            return ['applied' => ['0.1.0']];
        }
        return ['applied' => []];
    }

    /** @return array{0:string,1:string} */
    private function parseOrder(string $orderBy): array
    {
        $parts = preg_split('/\s+/', trim($orderBy)) ?: [$orderBy];
        $col = preg_replace('/[^a-zA-Z0-9_]/', '', $parts[0]) ?: 'id';
        $dir = isset($parts[1]) && strtolower($parts[1]) === 'desc' ? 'desc' : 'asc';
        return [$col, $dir];
    }

    private function readTable(string $table): array
    {
        $file = $this->path($table);
        if (!is_file($file)) {
            return ['next_id' => 1, 'rows' => []];
        }
        $raw = @file_get_contents($file);
        if (!is_string($raw) || $raw === '') {
            return ['next_id' => 1, 'rows' => []];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['next_id' => 1, 'rows' => []];
        }
        if (!isset($data['rows']) || !is_array($data['rows'])) {
            $data['rows'] = [];
        }
        if (!isset($data['next_id'])) {
            $maxId = 0;
            foreach ($data['rows'] as $r) {
                $maxId = max($maxId, (int) ($r['id'] ?? 0));
            }
            $data['next_id'] = $maxId + 1;
        }
        return $data;
    }

    private function writeTable(string $table, array $data): void
    {
        $file = $this->path($table);
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0700, true);
        }
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $written = @file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
        if ($written === false) {
            throw new RuntimeException('JSON write failed for ' . $table);
        }
        @chmod($tmp, 0600);
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            throw new RuntimeException('JSON rename failed for ' . $table);
        }
    }

    private function path(string $table): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if (!$clean) {
            throw new RuntimeException('Invalid table name');
        }
        return $this->dir . '/' . $clean . '.json';
    }
}
