<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Shared\Exception\TooManyAttemptsException;

/** Sliding-window attempt limiter. The key says what is limited, e.g. `login:ip:1.2.3.4`. */
interface RateLimiter
{
    /** @throws TooManyAttemptsException when there are already `$max` hits in the last `$minutes`. */
    public function ensureBelow(string $key, int $max, int $minutes): void;

    public function hit(string $key): void;

    public function clear(string $key): void;
}
