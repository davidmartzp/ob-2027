<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

/** A `bin/console` command. Register new ones in `config/console.php`. */
interface Command
{
    public function name(): string;

    public function description(): string;

    /** @param list<string> $arguments @return int Exit code. */
    public function run(array $arguments): int;
}
