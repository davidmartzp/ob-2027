<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/*
 * Converts an angular-ob (Laravel) database to the new schema, keeping the data:
 * assistants → attendees, workshops.id_assistant → id_attendee, drops workshops.time
 * (empty in production) and the tables the new backend no longer uses.
 * On an empty database it does nothing; the next migrations create what is missing.
 * Always run on a copy of the production database first (see design.md).
 */
return new class implements Migration {
    /** Children before parents: in production the email_* tables have foreign keys between them. */
    private const array UNUSED_TABLES = [
        'email_logs', 'email_recipients', 'email_filters', 'email_triggers', 'email_campaigns',
        'email_templates', 'workshops_menu', 'jobs', 'failed_jobs', 'personal_access_tokens',
        'password_resets', 'migrations',
    ];

    public function up(Builder $schema): void
    {
        if (!$schema->hasTable('assistants')) {
            return;
        }

        $schema->rename('assistants', 'attendees');
        // The legacy reset left code = 0; "no number" is NULL, so the unique index fits.
        $schema->getConnection()->table('attendees')->where('code', 0)->update(['code' => null]);
        $schema->table('attendees', static function (Blueprint $table): void {
            $table->unique('code');
            $table->index('email');
        });

        if ($schema->hasTable('workshops')) {
            $schema->table('workshops', static function (Blueprint $table): void {
                $table->renameColumn('id_assistant', 'id_attendee');
            });
            if ($schema->hasColumn('workshops', 'time')) {
                $schema->table('workshops', static fn (Blueprint $table) => $table->dropColumn('time'));
            }
            $schema->table('workshops', static fn (Blueprint $table) => $table->index('id_attendee'));
        }

        $schema->withoutForeignKeyConstraints(static function () use ($schema): void {
            foreach (self::UNUSED_TABLES as $table) {
                $schema->dropIfExists($table);
            }
        });
    }

    /** Only undoes the renames: dropped tables are not restored (they are in the source dump). */
    public function down(Builder $schema): void
    {
        if (!$schema->hasTable('attendees') || $schema->hasTable('assistants')) {
            return;
        }
        if ($schema->hasColumn('workshops', 'id_attendee')) {
            $schema->table('workshops', static function (Blueprint $table): void {
                $table->dropIndex(['id_attendee']);
                $table->renameColumn('id_attendee', 'id_assistant');
            });
        }
        $schema->table('attendees', static function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropIndex(['email']);
        });
        $schema->rename('attendees', 'assistants');
    }
};
