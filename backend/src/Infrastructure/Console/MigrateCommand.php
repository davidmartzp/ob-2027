<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Infrastructure\Persistence\Migration\Migrator;

final readonly class MigrateCommand implements Command
{
    public function __construct(private Migrator $migrator)
    {
    }

    public function name(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Apply pending migrations (one batch)';
    }

    public function run(array $arguments): int
    {
        Output::list('Applied migrations', $this->migrator->migrate());

        return 0;
    }
}
