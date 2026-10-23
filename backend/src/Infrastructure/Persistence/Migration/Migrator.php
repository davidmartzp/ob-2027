<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migration;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use RuntimeException;

/**
 * Applies and rolls back migrations in batches, tracked in `schema_migrations`
 * (distinct from Laravel's `migrations` table in the legacy database).
 */
final readonly class Migrator
{
    private const string TABLE = 'schema_migrations';

    public function __construct(private Connection $db, private string $directory)
    {
    }

    /** @return list<string> Migrations applied in this batch. */
    public function migrate(): array
    {
        $this->ensureTable();
        $pending = $this->pending();
        if ($pending === []) {
            return [];
        }

        $batch = (int) $this->db->table(self::TABLE)->max('batch') + 1;
        foreach ($pending as $name) {
            $this->load($name)->up($this->db->getSchemaBuilder());
            $this->db->table(self::TABLE)->insert(['migration' => $name, 'batch' => $batch, 'applied_at' => date('Y-m-d H:i:s')]);
        }

        return $pending;
    }

    /** @return list<string> Migrations rolled back (the last batch, in reverse order). */
    public function rollback(): array
    {
        $this->ensureTable();
        $batch = $this->db->table(self::TABLE)->max('batch');
        if ($batch === null) {
            return [];
        }

        $names = $this->db->table(self::TABLE)->where('batch', $batch)->orderByDesc('migration')->pluck('migration')->all();
        foreach ($names as $name) {
            $this->load($name)->down($this->db->getSchemaBuilder());
            $this->db->table(self::TABLE)->where('migration', $name)->delete();
        }

        return $names;
    }

    /** @return list<string> */
    public function pending(): array
    {
        $this->ensureTable();
        $applied = $this->db->table(self::TABLE)->pluck('migration')->all();

        return array_values(array_diff($this->files(), $applied));
    }

    private function ensureTable(): void
    {
        $schema = $this->db->getSchemaBuilder();
        if (!$schema->hasTable(self::TABLE)) {
            $schema->create(self::TABLE, static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('migration')->unique();
                $table->unsignedInteger('batch');
                $table->dateTime('applied_at');
            });
        }
    }

    /** @return list<string> File names without extension, sorted. */
    private function files(): array
    {
        $files = array_map(static fn (string $path): string => basename($path, '.php'), glob($this->directory . '/*.php') ?: []);
        sort($files);

        return $files;
    }

    private function load(string $name): Migration
    {
        $migration = require $this->directory . '/' . $name . '.php';
        if (!$migration instanceof Migration) {
            throw new RuntimeException("Migration {$name} must return a Migration object.");
        }

        return $migration;
    }
}
