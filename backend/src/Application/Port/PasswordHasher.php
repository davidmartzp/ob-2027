<?php

declare(strict_types=1);

namespace App\Application\Port;

interface PasswordHasher
{
    public function hash(string $password): string;

    /**
     * With a null hash (unknown user) it still spends the time of a real check and
     * returns false, so response times do not reveal which emails exist.
     */
    public function verify(string $password, ?string $hash): bool;

    public function needsRehash(string $hash): bool;
}
