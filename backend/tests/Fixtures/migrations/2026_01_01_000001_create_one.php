<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return new class implements Migration {
    public function up(Builder $schema): void
    {
        $schema->create('one', static fn (Blueprint $t) => $t->id());
    }

    public function down(Builder $schema): void
    {
        $schema->drop('one');
    }
};
