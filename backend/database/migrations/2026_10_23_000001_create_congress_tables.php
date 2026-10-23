<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/*
 * Congress tables with the same shape as in production (origamib_online, 2026-10-23), so a
 * legacy database and a new one are interchangeable. Existing tables (from the legacy
 * migration) are left untouched.
 */
return new class implements Migration {
    public function up(Builder $schema): void
    {
        $this->createIfMissing($schema, 'users', static function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->string('remember_token', 100)->nullable();
            $t->timestamps();
        });

        $this->createIfMissing($schema, 'attendees', static function (Blueprint $t): void {
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

        $this->createIfMissing($schema, 'workshops', static function (Blueprint $t): void {
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

        $this->createIfMissing($schema, 'rooms', static function (Blueprint $t): void {
            $t->integer('id', true);
            $t->string('name', 45)->nullable();
        });

        $this->createIfMissing($schema, 'activities', static function (Blueprint $t): void {
            $t->id();
            $t->string('name', 100);
            $t->text('description')->nullable();
            $t->integer('duration');
        });

        $this->createIfMissing($schema, 'events', static function (Blueprint $t): void {
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

        $this->createIfMissing($schema, 'events_attendees', static function (Blueprint $t): void {
            $t->integer('id_attendee');
            $t->integer('id_event')->index();
            $t->primary(['id_attendee', 'id_event']);
        });

        $this->createIfMissing($schema, 'partners', static function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('logo')->nullable();
            $t->string('whatsapp');
            $t->text('message');
            $t->integer('status')->default(1);
            $t->timestamps();
        });

        $this->createIfMissing($schema, 'workshop_partner', static function (Blueprint $t): void {
            $t->id();
            $t->integer('id_workshop');
            $t->unsignedBigInteger('id_partner')->index();
            $t->unique(['id_workshop', 'id_partner']);
        });
    }

    public function down(Builder $schema): void
    {
        foreach (['workshop_partner', 'partners', 'events_attendees', 'events', 'activities', 'rooms', 'workshops', 'attendees', 'users'] as $table) {
            $schema->dropIfExists($table);
        }
    }

    private function createIfMissing(Builder $schema, string $table, Closure $definition): void
    {
        if (!$schema->hasTable($table)) {
            $schema->create($table, $definition);
        }
    }
};
