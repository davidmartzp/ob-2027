<?php

declare(strict_types=1);

use App\Compartido\BaseDatos\Migracion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/*
 * Tablas del congreso con la misma forma que en producción (origamib_online, 2026-10-23),
 * para que la base heredada y una base nueva sean intercambiables. Si la tabla ya existe
 * (viene de la transición), no se toca.
 */
return new class implements Migracion {
    public function subir(Builder $esquema): void
    {
        $this->crear($esquema, 'users', static function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->string('remember_token', 100)->nullable();
            $t->timestamps();
        });

        $this->crear($esquema, 'attendees', static function (Blueprint $t): void {
            $t->id();
            $t->integer('code')->nullable()->unique();
            $t->string('name');
            $t->string('lastname');
            $t->integer('age');
            $t->string('country')->nullable();
            $t->string('city')->nullable();
            $t->string('email')->index();
            $t->string('phone');
            $t->string('companion')->nullable();
            $t->string('idname')->nullable();
            $t->dateTime('paydate');
            $t->string('paymethod', 20)->nullable();
            $t->string('receipt')->nullable();
            $t->string('origamigroup', 200)->nullable();
            $t->string('info')->nullable();
            $t->integer('cardsgroup')->nullable();
            $t->timestamps();
            $t->boolean('status')->default(0);
            $t->integer('cards')->default(0);
            $t->string('days', 1)->nullable();
            $t->boolean('staff')->default(0);
            $t->string('special_code', 3)->nullable();
            $t->boolean('go_to_chiva')->default(0);
            $t->boolean('printCertificate')->default(0);
            $t->integer('chiva_companions')->default(0);
            $t->string('pay_amount', 25)->default('0');
        });

        $this->crear($esquema, 'workshops', static function (Blueprint $t): void {
            $t->integer('id', true);
            $t->string('name', 200);
            $t->string('public', 45)->nullable();
            $t->string('level', 45)->nullable();
            $t->string('observations', 500)->nullable();
            $t->integer('status')->default(1);
            $t->dateTime('created_at')->nullable()->useCurrent();
            $t->dateTime('updated_at')->nullable()->useCurrent();
            $t->integer('id_attendee')->nullable()->index();
            $t->string('image', 45)->nullable();
            $t->integer('tickets')->default(20);
            $t->integer('duration')->nullable();
            $t->integer('part')->default(1);
            $t->integer('relation')->nullable();
        });

        $this->crear($esquema, 'rooms', static function (Blueprint $t): void {
            $t->integer('id', true);
            $t->string('name', 45)->nullable();
        });

        $this->crear($esquema, 'activities', static function (Blueprint $t): void {
            $t->id();
            $t->string('name', 100);
            $t->text('description')->nullable();
            $t->integer('duration');
        });

        $this->crear($esquema, 'events', static function (Blueprint $t): void {
            $t->id();
            $t->integer('id_room');
            $t->time('start_time');
            $t->time('end_time');
            $t->string('day', 20);
            $t->integer('id_workshop')->nullable()->index();
            $t->integer('id_activity')->nullable();
            $t->timestamps();
            $t->index(['day', 'id_room']);
        });

        $this->crear($esquema, 'events_attendees', static function (Blueprint $t): void {
            $t->integer('id_attendee');
            $t->integer('id_event')->index();
            $t->primary(['id_attendee', 'id_event']);
        });

        $this->crear($esquema, 'partners', static function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('logo')->nullable();
            $t->string('whatsapp');
            $t->text('message');
            $t->integer('status')->default(1);
            $t->timestamps();
        });

        $this->crear($esquema, 'workshop_partner', static function (Blueprint $t): void {
            $t->id();
            $t->integer('id_workshop');
            $t->unsignedBigInteger('id_partner')->index();
            $t->unique(['id_workshop', 'id_partner']);
        });
    }

    public function bajar(Builder $esquema): void
    {
        foreach (['workshop_partner', 'partners', 'events_attendees', 'events', 'activities', 'rooms', 'workshops', 'attendees', 'users'] as $tabla) {
            $esquema->dropIfExists($tabla);
        }
    }

    private function crear(Builder $esquema, string $tabla, Closure $definicion): void
    {
        if (!$esquema->hasTable($tabla)) {
            $esquema->create($tabla, $definicion);
        }
    }
};
