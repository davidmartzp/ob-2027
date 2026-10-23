<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/* New backend tables: access tokens, access links, rate-limit hits and the email outbox. */
return new class implements Migration {
    public function up(Builder $schema): void
    {
        $schema->create('access_tokens', static function (Blueprint $t): void {
            $t->id();
            $t->string('type', 12);                 // cms | attendee
            $t->unsignedBigInteger('subject_id');   // users.id or attendees.id
            $t->char('hash', 64)->unique();         // SHA-256 of the token; the plain token is never stored
            $t->dateTime('expires_at');
            $t->dateTime('revoked_at')->nullable();
            $t->dateTime('last_used_at')->nullable();
            $t->dateTime('created_at');
            $t->index(['type', 'subject_id']);
        });

        $schema->create('access_links', static function (Blueprint $t): void {
            $t->id();
            $t->char('hash', 64)->unique();
            $t->unsignedBigInteger('id_attendee')->index();
            $t->dateTime('expires_at');
            $t->dateTime('used_at')->nullable();
            $t->dateTime('created_at');
        });

        $schema->create('rate_limit_hits', static function (Blueprint $t): void {
            $t->id();
            $t->string('limit_key', 191);           // e.g. login:ip:1.2.3.4
            $t->dateTime('hit_at');
            $t->index(['limit_key', 'hit_at']);
        });

        $schema->create('email_outbox', static function (Blueprint $t): void {
            $t->id();
            $t->string('type', 40);
            $t->unsignedBigInteger('id_attendee')->nullable()->index();
            $t->string('email');
            $t->string('subject');
            $t->longText('html');
            $t->longText('text')->nullable();
            $t->string('status', 10)->default('pending'); // pending | sent | failed
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->dateTime('available_at');
            $t->dateTime('sent_at')->nullable();
            $t->text('error')->nullable();
            $t->string('unique_key', 100)->nullable()->unique(); // type + attendee; NULL for re-sends
            $t->timestamps();
            $t->index(['status', 'available_at']);
        });
    }

    public function down(Builder $schema): void
    {
        foreach (['email_outbox', 'rate_limit_hits', 'access_links', 'access_tokens'] as $table) {
            $schema->dropIfExists($table);
        }
    }
};
