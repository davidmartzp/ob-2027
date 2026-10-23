<?php

declare(strict_types=1);

namespace Tests\Integration\Persistence;

use App\Infrastructure\Persistence\Migration\Migrator;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MigrationsTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        $app = (require dirname(__DIR__, 3) . '/config/bootstrap.php')();
        $this->db = $app->getContainer()->get(Connection::class);
    }

    #[Test]
    public function migrate_and_rollback_leave_the_schema_as_it_was(): void
    {
        $migrator = new Migrator($this->db, dirname(__DIR__, 2) . '/Fixtures/migrations');

        self::assertSame(['2026_01_01_000001_create_one', '2026_01_01_000002_create_two'], $migrator->migrate());
        self::assertTrue($this->schema()->hasTable('one'));
        self::assertSame([], $migrator->migrate(), 'A second run does nothing');

        self::assertSame(['2026_01_01_000002_create_two', '2026_01_01_000001_create_one'], $migrator->rollback());
        self::assertSame(['schema_migrations'], $this->tables());
        self::assertCount(2, $migrator->pending());
    }

    #[Test]
    public function the_real_schema_migrates_and_rolls_back_on_sqlite(): void
    {
        $migrator = new Migrator($this->db, dirname(__DIR__, 3) . '/database/migrations');
        $migrator->migrate();

        foreach (['users', 'attendees', 'workshops', 'rooms', 'activities', 'events', 'events_attendees', 'partners', 'workshop_partner', 'access_tokens', 'access_links', 'rate_limit_hits', 'email_outbox'] as $table) {
            self::assertTrue($this->schema()->hasTable($table), "Missing table {$table}");
        }

        $migrator->rollback();
        self::assertSame(['schema_migrations'], $this->tables());
    }

    #[Test]
    public function legacy_migration_keeps_the_data_of_a_legacy_database(): void
    {
        $this->createLegacyDatabase();

        (new Migrator($this->db, dirname(__DIR__, 3) . '/database/migrations'))->migrate();

        $schema = $this->schema();
        self::assertFalse($schema->hasTable('assistants'));
        self::assertSame([1, 2, 3], $this->db->table('attendees')->orderBy('id')->pluck('id')->map(intval(...))->all());
        self::assertSame('Ana', $this->db->table('attendees')->where('id', 1)->value('name'));
        self::assertNull($this->db->table('attendees')->where('id', 3)->value('code'), 'legacy code 0 becomes NULL');
        self::assertTrue($schema->hasColumn('workshops', 'id_attendee'));
        self::assertFalse($schema->hasColumn('workshops', 'id_assistant'));
        self::assertFalse($schema->hasColumn('workshops', 'time'));
        self::assertSame(2, (int) $this->db->table('workshops')->where('id', 10)->value('id_attendee'));
        self::assertSame(1, $this->db->table('events_attendees')->count());
        foreach (['email_triggers', 'email_campaigns', 'personal_access_tokens', 'jobs', 'migrations'] as $table) {
            self::assertFalse($schema->hasTable($table), "{$table} should have been dropped");
        }
        self::assertTrue($schema->hasTable('obusers'), 'Tables unrelated to the congress are left alone');
        self::assertTrue($schema->hasTable('email_outbox'));
    }

    /** Subset of origamib_online (2026-10-23) with made-up data. */
    private function createLegacyDatabase(): void
    {
        $schema = $this->schema();
        $schema->create('assistants', static function (Blueprint $t): void {
            $t->id();
            $t->integer('code')->nullable();
            $t->string('name');
            $t->string('lastname');
            $t->integer('age');
            $t->string('email');
            $t->string('phone');
            $t->dateTime('paydate');
            $t->timestamps();
            $t->boolean('status')->default(0);
            $t->string('days', 1)->nullable();
            $t->boolean('staff')->default(0);
            $t->string('special_code', 3)->nullable();
            $t->string('pay_amount', 25)->default('0');
        });
        $schema->create('workshops', static function (Blueprint $t): void {
            $t->integer('id', true);
            $t->string('name', 200);
            $t->string('time', 45)->nullable();
            $t->integer('status')->default(1);
            $t->integer('id_assistant')->nullable();
            $t->integer('tickets')->default(20);
        });
        $schema->create('events_attendees', static function (Blueprint $t): void {
            $t->integer('id_attendee');
            $t->integer('id_event');
            $t->primary(['id_attendee', 'id_event']);
        });
        foreach (['email_campaigns', 'personal_access_tokens', 'jobs', 'migrations', 'obusers'] as $table) {
            $schema->create($table, static fn (Blueprint $t) => $t->id());
        }
        // As in production: email_* tables with foreign keys between them (drop order matters).
        $schema->create('email_triggers', static function (Blueprint $t): void {
            $t->id();
            $t->foreignId('campaign_id')->nullable()->constrained('email_campaigns');
        });

        $base = ['lastname' => 'Pérez', 'age' => 30, 'email' => 'familia@ejemplo.com', 'phone' => '300', 'paydate' => '2026-03-01 10:00:00'];
        $this->db->table('assistants')->insert([
            ['id' => 1, 'code' => 1, 'name' => 'Ana'] + $base,
            ['id' => 2, 'code' => 2, 'name' => 'Luis'] + $base,
            ['id' => 3, 'code' => 0, 'name' => 'Sofía'] + $base,
        ]);
        $this->db->table('workshops')->insert(['id' => 10, 'name' => 'Grulla', 'id_assistant' => 2]);
        $this->db->table('events_attendees')->insert(['id_attendee' => 1, 'id_event' => 5]);
    }

    private function schema(): \Illuminate\Database\Schema\Builder
    {
        return $this->db->getSchemaBuilder();
    }

    /** @return list<string> */
    private function tables(): array
    {
        $names = array_column($this->schema()->getTables(), 'name');
        sort($names);

        return array_values(array_filter($names, static fn (string $name): bool => !str_starts_with($name, 'sqlite_')));
    }
}
