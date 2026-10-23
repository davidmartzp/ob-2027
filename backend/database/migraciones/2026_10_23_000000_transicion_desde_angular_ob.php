<?php

declare(strict_types=1);

use App\Compartido\BaseDatos\Migracion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/*
 * Convierte una base de angular-ob (Laravel) al esquema nuevo conservando los datos:
 * assistants → attendees, workshops.id_assistant → id_attendee, se quita workshops.time
 * (vacía en producción) y se borran las tablas que el backend nuevo ya no usa.
 * En una base vacía no hace nada; las migraciones siguientes crean lo que falte.
 * Se ejecuta siempre sobre una copia de la base de producción (ver design.md).
 */
return new class implements Migracion {
    /** Hijas antes que madres: en producción las tablas email_* tienen claves foráneas entre sí. */
    private const array TABLAS_SIN_USO = [
        'email_logs', 'email_recipients', 'email_filters', 'email_triggers', 'email_campaigns',
        'email_templates', 'workshops_menu', 'jobs', 'failed_jobs', 'personal_access_tokens',
        'password_resets', 'migrations',
    ];

    public function subir(Builder $esquema): void
    {
        if (!$esquema->hasTable('assistants')) {
            return;
        }

        $esquema->rename('assistants', 'attendees');
        // El reinicio heredado dejaba code = 0; sin número es NULL, y así cabe el índice único.
        $esquema->getConnection()->table('attendees')->where('code', 0)->update(['code' => null]);
        $esquema->table('attendees', static function (Blueprint $tabla): void {
            $tabla->unique('code');
            $tabla->index('email');
        });

        if ($esquema->hasTable('workshops')) {
            $esquema->table('workshops', static function (Blueprint $tabla): void {
                $tabla->renameColumn('id_assistant', 'id_attendee');
            });
            if ($esquema->hasColumn('workshops', 'time')) {
                $esquema->table('workshops', static fn (Blueprint $tabla) => $tabla->dropColumn('time'));
            }
            $esquema->table('workshops', static fn (Blueprint $tabla) => $tabla->index('id_attendee'));
        }

        $esquema->withoutForeignKeyConstraints(static function () use ($esquema): void {
            foreach (self::TABLAS_SIN_USO as $tabla) {
                $esquema->dropIfExists($tabla);
            }
        });
    }

    /** Solo deshace los renombrados: las tablas borradas no se recuperan (están en el volcado de origen). */
    public function bajar(Builder $esquema): void
    {
        if (!$esquema->hasTable('attendees') || $esquema->hasTable('assistants')) {
            return;
        }
        if ($esquema->hasColumn('workshops', 'id_attendee')) {
            $esquema->table('workshops', static function (Blueprint $tabla): void {
                $tabla->dropIndex(['id_attendee']);
                $tabla->renameColumn('id_attendee', 'id_assistant');
            });
        }
        $esquema->table('attendees', static function (Blueprint $tabla): void {
            $tabla->dropUnique(['code']);
            $tabla->dropIndex(['email']);
        });
        $esquema->rename('attendees', 'assistants');
    }
};
