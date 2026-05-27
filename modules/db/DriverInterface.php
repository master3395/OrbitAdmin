<?php
namespace OrbitAdmin\Db;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

interface DriverInterface
{
    public function get(string $table, array $where);
    public function all(string $table, array $where = [], ?string $orderBy = null, ?int $limit = null, ?int $offset = null): array;
    public function insert(string $table, array $data): int;
    public function update(string $table, int $id, array $data): bool;
    public function delete(string $table, int $id): bool;
    public function count(string $table, array $where = []): int;
    public function query(string $sql, array $params = []): array;
    public function transaction(callable $fn);
    public function name(): string;
    public function migrate(string $direction = 'up'): array;
    public function ensureSchema(): void;
}
