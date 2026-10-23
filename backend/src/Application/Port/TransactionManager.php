<?php

declare(strict_types=1);

namespace App\Application\Port;

/** Runs a unit of work atomically; deadlocks are retried by the adapter. */
interface TransactionManager
{
    /**
     * @template T
     * @param callable(): T $work
     * @return T
     */
    public function run(callable $work): mixed;
}
