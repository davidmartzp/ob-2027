<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migration;

use Illuminate\Database\Connection;
use RuntimeException;

/**
 * Runs `database/seeds/NN_name.php` in order. Each file returns an idempotent
 * `function (Connection $db): void` (look up by natural key, then create or update).
 */
final readonly class Seeder
{
    public function __construct(private Connection $db, private string $directory)
    {
    }

    /** @return list<string> Seeds that ran. */
    public function seed(): array
    {
        $files = glob($this->directory . '/*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $seed = require $file;
            if (!is_callable($seed)) {
                throw new RuntimeException('Seed ' . basename($file) . ' must return a function.');
            }
            $seed($this->db);
        }

        return array_map(static fn (string $path): string => basename($path, '.php'), $files);
    }
}
