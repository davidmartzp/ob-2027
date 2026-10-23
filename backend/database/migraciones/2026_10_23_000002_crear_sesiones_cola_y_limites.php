<?php

declare(strict_types=1);

use App\Compartido\BaseDatos\Migracion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/* Tablas nuevas del backend: tokens de acceso, enlaces de acceso, límites de intentos y cola de correo. */
return new class implements Migracion {
    public function subir(Builder $esquema): void
    {
        $esquema->create('tokens_acceso', static function (Blueprint $t): void {
            $t->id();
            $t->string('tipo', 12);               // cms | asistente
            $t->unsignedBigInteger('id_sujeto');  // users.id o attendees.id
            $t->char('hash', 64)->unique();       // SHA-256 del token; el token en claro nunca se guarda
            $t->dateTime('caduca_en');
            $t->dateTime('revocado_en')->nullable();
            $t->dateTime('ultimo_uso_en')->nullable();
            $t->dateTime('creado_en');
            $t->index(['tipo', 'id_sujeto']);
        });

        $esquema->create('enlaces_acceso', static function (Blueprint $t): void {
            $t->id();
            $t->char('hash', 64)->unique();
            $t->unsignedBigInteger('id_attendee')->index();
            $t->dateTime('caduca_en');
            $t->dateTime('usado_en')->nullable();
            $t->dateTime('creado_en');
        });

        $esquema->create('intentos', static function (Blueprint $t): void {
            $t->id();
            $t->string('clave', 191);             // p. ej. login:ip:1.2.3.4
            $t->dateTime('ocurrido_en');
            $t->index(['clave', 'ocurrido_en']);
        });

        $esquema->create('email_envios', static function (Blueprint $t): void {
            $t->id();
            $t->string('tipo', 40);
            $t->unsignedBigInteger('id_attendee')->nullable()->index();
            $t->string('email');
            $t->string('subject');
            $t->longText('body');
            $t->longText('texto')->nullable();
            $t->string('estado', 10)->default('pending'); // pending | sent | failed
            $t->unsignedTinyInteger('intentos')->default(0);
            $t->dateTime('disponible_en');
            $t->dateTime('enviado_en')->nullable();
            $t->text('error')->nullable();
            $t->string('clave_unica', 100)->nullable()->unique(); // tipo+asistente; NULL en reenvíos
            $t->timestamps();
            $t->index(['estado', 'disponible_en']);
        });
    }

    public function bajar(Builder $esquema): void
    {
        foreach (['email_envios', 'intentos', 'enlaces_acceso', 'tokens_acceso'] as $tabla) {
            $esquema->dropIfExists($tabla);
        }
    }
};
