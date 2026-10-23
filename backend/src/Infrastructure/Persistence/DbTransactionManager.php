<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Port\TransactionManager;
use Illuminate\Database\Connection;

/** Laravel transactions: on a MariaDB deadlock the whole unit of work is retried. */
final readonly class DbTransactionManager implements TransactionManager
{
    private const int ATTEMPTS = 3;

    public function __construct(private Connection $db)
    {
    }

    public function run(callable $work): mixed
    {
        return $this->db->transaction($work, self::ATTEMPTS);
    }
}
