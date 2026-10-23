<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Infrastructure\Config\Settings;
use App\Infrastructure\Persistence\Migration\Migrator;

final readonly class RollbackCommand implements Command
{
    public function __construct(private Migrator $migrator, private Settings $settings)
    {
    }

    public function name(): string
    {
        return 'rollback';
    }

    public function description(): string
    {
        return 'Undo the last batch of migrations (blocked in production unless --force)';
    }

    public function run(array $arguments): int
    {
        if ($this->settings->isProduction() && !in_array('--force', $arguments, true)) {
            fwrite(STDERR, "In production a rollback can delete data. If that is what you want: rollback --force\n");

            return 1;
        }
        Output::list('Rolled back migrations', $this->migrator->rollback());

        return 0;
    }
}
