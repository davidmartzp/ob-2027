<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migration;

use Illuminate\Database\Schema\Builder;

/**
 * A file in `database/migrations/YYYY_MM_DD_HHMMSS_name.php` returns
 * `new class implements Migration { … }`. Use only what both MariaDB and SQLite support.
 */
interface Migration
{
    public function up(Builder $schema): void;

    public function down(Builder $schema): void;
}
