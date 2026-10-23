<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Port\RateLimiter;
use App\Domain\Shared\Exception\TooManyAttemptsException;
use Illuminate\Database\Connection;
use Psr\Clock\ClockInterface;

/** Rate limiter on the `rate_limit_hits` table, so it works on shared hosting without Redis. */
final readonly class DbRateLimiter implements RateLimiter
{
    private const string DATETIME = 'Y-m-d H:i:s';

    public function __construct(private Connection $db, private ClockInterface $clock)
    {
    }

    public function ensureBelow(string $key, int $max, int $minutes): void
    {
        $since = $this->clock->now()->modify("-{$minutes} minutes")->format(self::DATETIME);
        $hits = $this->db->table('rate_limit_hits')->where('limit_key', $key)->where('hit_at', '>', $since)->count();
        if ($hits >= $max) {
            throw new TooManyAttemptsException();
        }
    }

    public function hit(string $key): void
    {
        $now = $this->clock->now();
        $this->db->table('rate_limit_hits')->insert(['limit_key' => $key, 'hit_at' => $now->format(self::DATETIME)]);
        // Lazy cleanup: no limit looks back more than a day.
        if (random_int(1, 50) === 1) {
            $this->db->table('rate_limit_hits')->where('hit_at', '<', $now->modify('-1 day')->format(self::DATETIME))->delete();
        }
    }

    public function clear(string $key): void
    {
        $this->db->table('rate_limit_hits')->where('limit_key', $key)->delete();
    }
}
