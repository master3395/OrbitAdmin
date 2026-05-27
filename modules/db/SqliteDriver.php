<?php
namespace OrbitAdmin\Db;

use OrbitAdmin\Core\Config;
use PDO;
use RuntimeException;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class SqliteDriver implements DriverInterface
{
    private PDO $pdo;
    private string $file;

    public function __construct()
    {
        $this->file = (string) Config::get('SQLITE_PATH', Config::get('DATA_PATH') . '/orbit.sqlite');
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $this->pdo = new PDO('sqlite:' . $this->file);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->pdo->exec('PRAGMA foreign_keys=ON');
    }

    public function name(): string { return 'sqlite'; }

    public function ensureSchema(): void
    {
        $sql = (string) @file_get_contents((string) Config::get('BASE_PATH') . '/sql/sqlite.sql');
        if ($sql !== '') {
            $this->pdo->exec($sql);
        }
    }

    public function get(string $table, array $where)
    {
        $rows = $this->all($table, $where, null, 1, 0);
        return $rows[0] ?? null;
    }

    public function all(string $table, array $where = [], ?string $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $sql = 'SELECT * FROM ' . $this->ident($table);
        $params = [];
        if ($where) {
            $parts = [];
            foreach ($where as $k => $v) {
                $parts[] = $this->ident($k) . ' = :' . $k;
                $params[$k] = $v;
            }
            $sql .= ' WHERE ' . implode(' AND ', $parts);
        }
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $this->sanitizeOrder($orderBy);
        }
        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
            if ($offset) {
                $sql .= ' OFFSET ' . (int) $offset;
            }
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function insert(string $table, array $data): int
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('c');
        }
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . $this->ident($table) . ' (' . implode(',', array_map([$this, 'ident'], $cols))
             . ') VALUES (' . implode(',', array_map(static fn($c) => ':' . $c, $cols)) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, int $id, array $data): bool
    {
        $data['updated_at'] = date('c');
        $sets = [];
        foreach ($data as $k => $_) {
            $sets[] = $this->ident($k) . ' = :' . $k;
        }
        $sql = 'UPDATE ' . $this->ident($table) . ' SET ' . implode(',', $sets) . ' WHERE id = :id';
        $data['id'] = $id;
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete(string $table, int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->ident($table) . ' WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function count(string $table, array $where = []): int
    {
        $sql = 'SELECT COUNT(*) AS c FROM ' . $this->ident($table);
        $params = [];
        if ($where) {
            $parts = [];
            foreach ($where as $k => $v) {
                $parts[] = $this->ident($k) . ' = :' . $k;
                $params[$k] = $v;
            }
            $sql .= ' WHERE ' . implode(' AND ', $parts);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int) ($row['c'] ?? 0);
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if (stripos(ltrim($sql), 'SELECT') === 0) {
            return $stmt->fetchAll();
        }
        return ['affected' => $stmt->rowCount()];
    }

    public function transaction(callable $fn)
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function migrate(string $direction = 'up'): array
    {
        $this->ensureSchema();
        $exists = $this->pdo->query("SELECT COUNT(*) FROM _migrations WHERE version='0.1.0'")->fetchColumn();
        if (!$exists) {
            $this->insert('_migrations', ['version' => '0.1.0', 'applied_at' => date('c')]);
            return ['applied' => ['0.1.0']];
        }
        return ['applied' => []];
    }

    private function ident(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        if (!$clean) {
            throw new RuntimeException('Invalid identifier');
        }
        return '"' . $clean . '"';
    }

    private function sanitizeOrder(string $orderBy): string
    {
        $parts = preg_split('/\s+/', trim($orderBy));
        $col = preg_replace('/[^a-zA-Z0-9_]/', '', $parts[0] ?? 'id') ?: 'id';
        $dir = isset($parts[1]) && strtolower($parts[1]) === 'desc' ? 'DESC' : 'ASC';
        return $this->ident($col) . ' ' . $dir;
    }
}
