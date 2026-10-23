<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Infrastructure\Persistence\Legacy\LegacyImport;
use InvalidArgumentException;

/**
 * Step after `migrate` on a copy of the production database (design.md, migration plan):
 *   php bin/console import:legacy <legacy images folder>
 * Fails if a column the backend needs is missing; reports broken references; copies images.
 */
final readonly class ImportLegacyCommand implements Command
{
    public function __construct(private LegacyImport $import)
    {
    }

    public function name(): string
    {
        return 'import:legacy';
    }

    public function description(): string
    {
        return 'Check the migrated legacy database and copy its images: import:legacy <images-folder>';
    }

    public function run(array $arguments): int
    {
        $directory = rtrim($arguments[0] ?? '', '/');
        if ($directory === '' || !is_dir($directory)) {
            throw new InvalidArgumentException('Usage: php bin/console import:legacy <folder with taller_*.jpg / partner_*.png>');
        }

        $schema = $this->import->checkSchema();
        foreach ($schema['extra'] as $table => $columns) {
            echo "Note: {$table} has columns the backend does not use: " . implode(', ', $columns) . "\n";
        }
        if ($schema['missing'] !== []) {
            foreach ($schema['missing'] as $table => $columns) {
                fwrite(STDERR, "Missing in {$table}: " . implode(', ', $columns) . "\n");
            }
            fwrite(STDERR, "Run `php bin/console migrate` first, or fix the schema. Nothing was copied.\n");

            return 1;
        }

        echo "Schema OK.\n";
        foreach ($this->import->brokenReferences() as $what => $count) {
            if ($count > 0) {
                echo "Warning: {$count} {$what}.\n";
            }
        }

        $images = $this->import->importImages($directory);
        printf(
            "Images: %d copied, %d already present, %d missing, %d rejected.\n",
            count($images['copied']),
            count($images['existing']),
            count($images['missing']),
            count($images['rejected']),
        );
        foreach ($images['missing'] as $name) {
            echo "  missing: {$name}\n";
        }
        foreach ($images['rejected'] as $name => $reason) {
            echo "  rejected: {$name} — {$reason}\n";
        }

        return 0;
    }
}
