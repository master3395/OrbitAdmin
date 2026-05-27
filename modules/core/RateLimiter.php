<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Sliding-window rate limiter backed by a JSON file (cheap, works with any driver).
 * Designed for login, install, and API endpoints. Not for high-RPS scenarios.
 */
final class RateLimiter
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?: (Config::get('DATA_PATH') . '/ratelimits.json');
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
    }

    /**
     * @return array{allowed:bool,remaining:int,retry_after:int}
     */
    public function hit(string $key, int $maxAttempts, int $windowSeconds): array
    {
        $now = time();
        $state = $this->load();
        $bucket = $state[$key] ?? [];
        $bucket = array_values(array_filter($bucket, static fn($t) => is_int($t) && ($now - $t) < $windowSeconds));
        $count = count($bucket);
        if ($count >= $maxAttempts) {
            $oldest = min($bucket);
            $retry = max(1, $windowSeconds - ($now - $oldest));
            $state[$key] = $bucket;
            $this->save($state);
            return ['allowed' => false, 'remaining' => 0, 'retry_after' => $retry];
        }
        $bucket[] = $now;
        $state[$key] = $bucket;
        $this->save($state);
        return ['allowed' => true, 'remaining' => $maxAttempts - count($bucket), 'retry_after' => 0];
    }

    public function reset(string $key): void
    {
        $state = $this->load();
        unset($state[$key]);
        $this->save($state);
    }

    public function clear(): void
    {
        if (is_file($this->file)) {
            @unlink($this->file);
        }
    }

    /** @return array<string,array<int,int>> */
    private function load(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $raw = @file_get_contents($this->file);
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string,array<int,int>> $state */
    private function save(array $state): void
    {
        $tmp = $this->file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        @file_put_contents($tmp, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
        @rename($tmp, $this->file);
    }
}
