<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DateTimeImmutable;

/** Stores only token hashes (SHA-256); the plain token is never persisted. */
interface AccessTokenRepository
{
    public function store(TokenType $type, int $subjectId, string $hash, DateTimeImmutable $expiresAt, DateTimeImmutable $now): void;

    /** The session of a token that exists, is not revoked and has not expired; marks it as used. */
    public function findActive(string $hash, DateTimeImmutable $now): ?Session;

    public function revoke(int $tokenId, DateTimeImmutable $now): void;

    public function revokeAllFor(TokenType $type, int $subjectId, DateTimeImmutable $now): void;
}
