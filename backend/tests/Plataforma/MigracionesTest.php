<?php

declare(strict_types=1);

namespace Pruebas\Plataforma;

use App\Compartido\BaseDatos\Migrador;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MigracionesTest extends TestCase
{
    private Connection $bd;

    protected function setUp(): void
    {
        $app = (require dirname(__DIR__, 2) . '/config/arranque.php')();
        $this->bd = $app->getContainer()->get(Connection::class);
    }

    #[Test]
    public function migrar_y_revertir_deja_el_esquema_como_estaba(): void
    {
        $migrador = new Migrador($this->bd, dirname(__DIR__) . '/Fixtures/migraciones');

        self::assertSame(['2026_01_01_000001_crear_uno', '2026_01_01_000002_crear_dos'], $migrador->migrar());
        self::assertTrue($this->esquema()->hasTable('uno'));
        self::assertSame([], $migrador->migrar(), 'Una segunda pasada no hace nada');

        self::assertSame(['2026_01_01_000002_crear_dos', '2026_01_01_000001_crear_uno'], $migrador->revertir());
        self::assertSame(['migraciones'], $this->tablas());
        self::assertCount(2, $migrador->pendientes());
    }

    #[Test]
    public function el_esquema_real_migra_y_revierte_en_sqlite(): void
    {
        $migrador = new Migrador($this->bd, dirname(__DIR__, 2) . '/database/migraciones');
        $migrador->migrar();

        foreach (['users', 'attendees', 'workshops', 'rooms', 'activities', 'events', 'events_attendees', 'partners', 'workshop_partner', 'tokens_acceso', 'enlaces_acceso', 'intentos', 'email_envios'] as $tabla) {
            self::assertTrue($this->esquema()->hasTable($tabla), "Falta la tabla {$tabla}");
        }

        $migrador->revertir();
        self::assertSame(['migraciones'], $this->tablas());
    }

    #[Test]
    public function transicion_conserva_los_datos_de_una_base_heredada(): void
    {
        $this->crearBaseHeredada();

        (new Migrador($this->bd, dirname(__DIR__, 2) . '/database/migraciones'))->migrar();

        $esquema = $this->esquema();
        self::assertFalse($esquema->hasTable('assistants'));
        self::assertSame([1, 2, 3], $this->bd->table('attendees')->orderBy('id')->pluck('id')->map(intval(...))->all());
        self::assertSame('Ana', $this->bd->table('attendees')->where('id', 1)->value('name'));
        self::assertNull($this->bd->table('attendees')->where('id', 3)->value('code'), 'code 0 heredado pasa a NULL');
        self::assertTrue($esquema->hasColumn('workshops', 'id_attendee'));
        self::assertFalse($esquema->hasColumn('workshops', 'id_assistant'));
        self::assertFalse($esquema->hasColumn('workshops', 'time'));
        self::assertSame(2, (int) $this->bd->table('workshops')->where('id', 10)->value('id_attendee'));
        self::assertSame(1, $this->bd->table('events_attendees')->count());
        foreach (['email_triggers', 'email_campaigns', 'personal_access_tokens', 'jobs', 'migrations'] as $tabla) {
            self::assertFalse($esquema->hasTable($tabla), "{$tabla} debería haberse borrado");
        }
        self::assertTrue($esquema->hasTable('obusers'), 'Las tablas ajenas al congreso no se tocan');
        self::assertTrue($esquema->hasTable('email_envios'));
    }

    /** Subconjunto de origamib_online (2026-10-23) con datos inventados. */
    private function crearBaseHeredada(): void
    {
        $esquema = $this->esquema();
        $esquema->create('assistants', static function (Blueprint $t): void {
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
        $esquema->create('workshops', static function (Blueprint $t): void {
            $t->integer('id', true);
            $t->string('name', 200);
            $t->string('time', 45)->nullable();
            $t->integer('status')->default(1);
            $t->integer('id_assistant')->nullable();
            $t->integer('tickets')->default(20);
        });
        $esquema->create('events_attendees', static function (Blueprint $t): void {
            $t->integer('id_attendee');
            $t->integer('id_event');
            $t->primary(['id_attendee', 'id_event']);
        });
        foreach (['email_campaigns', 'personal_access_tokens', 'jobs', 'migrations', 'obusers'] as $tabla) {
            $esquema->create($tabla, static fn (Blueprint $t) => $t->id());
        }
        // Como en producción: tablas email_* con claves foráneas entre sí (el orden de borrado importa).
        $esquema->create('email_triggers', static function (Blueprint $t): void {
            $t->id();
            $t->foreignId('campaign_id')->nullable()->constrained('email_campaigns');
        });

        $base = ['lastname' => 'Pérez', 'age' => 30, 'email' => 'familia@ejemplo.com', 'phone' => '300', 'paydate' => '2026-03-01 10:00:00'];
        $this->bd->table('assistants')->insert([
            ['id' => 1, 'code' => 1, 'name' => 'Ana'] + $base,
            ['id' => 2, 'code' => 2, 'name' => 'Luis'] + $base,
            ['id' => 3, 'code' => 0, 'name' => 'Sofía'] + $base,
        ]);
        $this->bd->table('workshops')->insert(['id' => 10, 'name' => 'Grulla', 'id_assistant' => 2]);
        $this->bd->table('events_attendees')->insert(['id_attendee' => 1, 'id_event' => 5]);
    }

    private function esquema(): \Illuminate\Database\Schema\Builder
    {
        return $this->bd->getSchemaBuilder();
    }

    /** @return list<string> */
    private function tablas(): array
    {
        $nombres = array_column($this->esquema()->getTables(), 'name');
        sort($nombres);

        return array_values(array_filter($nombres, static fn (string $n): bool => !str_starts_with($n, 'sqlite_')));
    }
}
