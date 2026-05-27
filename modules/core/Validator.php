<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class Validator
{
    /** @var array<string,string> */
    public array $errors = [];

    /** @var array<string,mixed> */
    public array $data;

    /** @param array<string,mixed> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, ?string $label = null): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->errors[$field] = ($label ?: $field) . ' is required.';
        }
        return $this;
    }

    public function email(string $field, ?string $label = null): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = ($label ?: $field) . ' must be a valid email.';
        }
        return $this;
    }

    public function minLen(string $field, int $min, ?string $label = null): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field] = ($label ?: $field) . " must be at least $min characters.";
        }
        return $this;
    }

    public function maxLen(string $field, int $max, ?string $label = null): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = ($label ?: $field) . " must be at most $max characters.";
        }
        return $this;
    }

    /** @param array<int,string> $allowed */
    public function in(string $field, array $allowed, ?string $label = null): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->errors[$field] = ($label ?: $field) . ' has an invalid value.';
        }
        return $this;
    }

    public function regex(string $field, string $pattern, ?string $label = null): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && !preg_match($pattern, $value)) {
            $this->errors[$field] = ($label ?: $field) . ' has an invalid format.';
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function value(string $field, ?string $default = null): ?string
    {
        $v = $this->data[$field] ?? $default;
        return $v === null ? null : (string) $v;
    }
}
