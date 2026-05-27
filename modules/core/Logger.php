<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class Logger
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?: (Config::get('LOG_PATH') . '/orbit.log');
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
    }

    /** @param array<string,mixed> $context */
    public function debug(string $msg, array $context = []): void { $this->write('DEBUG', $msg, $context); }
    /** @param array<string,mixed> $context */
    public function info(string $msg, array $context = []): void  { $this->write('INFO',  $msg, $context); }
    /** @param array<string,mixed> $context */
    public function warn(string $msg, array $context = []): void  { $this->write('WARN',  $msg, $context); }
    /** @param array<string,mixed> $context */
    public function error(string $msg, array $context = []): void { $this->write('ERROR', $msg, $context); }

    /** @param array<string,mixed> $context */
    private function write(string $level, string $msg, array $context): void
    {
        $ts = date('d/m/Y H:i:s');
        $ctx = $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $line = "[$ts] $level: $msg$ctx" . PHP_EOL;
        @file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }

    public function rotate(int $maxBytes = 5_242_880): void
    {
        if (!is_file($this->path) || filesize($this->path) < $maxBytes) {
            return;
        }
        $archive = $this->path . '.' . date('Ymd-His') . '.log';
        @rename($this->path, $archive);
    }
}
