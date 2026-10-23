<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

final class Output
{
    /** @param list<string> $items */
    public static function list(string $title, array $items): void
    {
        echo $items === [] ? "{$title}: nothing to do.\n" : "{$title}:\n  " . implode("\n  ", $items) . "\n";
    }
}
