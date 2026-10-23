<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use RuntimeException;

/**
 * Configuration read from the environment (`.env` or system variables). Immutable.
 * An empty value counts as missing.
 */
final readonly class Settings
{
    /** @param array<string, string> $values */
    public function __construct(private array $values)
    {
    }

    /**
     * Reads the process environment, applies `$overrides` (tests) and checks required variables.
     *
     * @param array<string, string> $overrides
     */
    public static function fromEnvironment(array $overrides = []): self
    {
        $values = [];
        foreach ([getenv(), $_SERVER, $_ENV, $overrides] as $source) {
            foreach ($source as $key => $value) {
                if (is_string($key) && is_scalar($value)) {
                    $values[$key] = (string) $value;
                }
            }
        }

        $settings = new self($values);
        $settings->require(['DB_DATABASE']);
        if ($settings->string('DB_CONNECTION', 'mariadb') !== 'sqlite') {
            $settings->require(['DB_HOST', 'DB_USERNAME']);
        }

        return $settings;
    }

    /** @param list<string> $keys */
    private function require(array $keys): void
    {
        $missing = array_values(array_filter($keys, fn (string $key) => $this->string($key) === ''));
        if ($missing !== []) {
            throw new RuntimeException('Missing configuration in .env: ' . implode(', ', $missing));
        }
    }

    public function string(string $key, string $default = ''): string
    {
        $value = trim($this->values[$key] ?? '');

        return $value === '' ? $default : $value;
    }

    public function int(string $key, int $default): int
    {
        $value = $this->string($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = strtolower($this->string($key));

        return $value === '' ? $default : in_array($value, ['1', 'true', 'on', 'yes'], true);
    }

    /** @return list<string> Comma-separated values, without empties. */
    public function list(string $key): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->string($key))), 'strlen'));
    }

    public function isProduction(): bool
    {
        return $this->string('APP_ENV', 'production') === 'production';
    }

    public function debug(): bool
    {
        return $this->bool('APP_DEBUG');
    }
}
