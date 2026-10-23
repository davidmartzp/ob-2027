<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Port\PasswordHasher;

/**
 * PHP's password_* functions: Argon2id when the server's PHP has it, bcrypt otherwise.
 * Legacy Laravel bcrypt hashes (`$2y$…`) verify and are upgraded on the next login.
 */
final class NativePasswordHasher implements PasswordHasher
{
    /** bcrypt of a discarded random password: only used to spend the same time on unknown users. */
    private const string DUMMY_HASH = '$2y$12$vP8D66lGDOFr.EZp4FrKuOqEnG0Gn.rDdRQF0dv80LMGw2dJF/eLq';

    public function hash(string $password): string
    {
        return password_hash($password, self::algorithm());
    }

    public function verify(string $password, ?string $hash): bool
    {
        $valid = password_verify($password, $hash ?? self::DUMMY_HASH);

        return $hash !== null && $valid;
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::algorithm());
    }

    private static function algorithm(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }
}
