<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Infrastructure\Persistence\Migration\Seeder;

final readonly class SeedCommand implements Command
{
    public function __construct(private Seeder $seeder)
    {
    }

    public function name(): string
    {
        return 'seed';
    }

    public function description(): string
    {
        return 'Run the seeds (idempotent)';
    }

    public function run(array $arguments): int
    {
        Output::list('Seeds run', $this->seeder->seed());

        return 0;
    }
}
