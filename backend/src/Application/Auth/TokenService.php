<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Auth\AccessTokenRepository;
use App\Domain\Auth\Session;
use App\Domain\Auth\TokenType;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Opaque access tokens: 32 random bytes handed out once (64 hex chars); only their
 * SHA-256 is stored. They expire (CMS and attendee lifetimes differ) and can be revoked.
 */
final readonly class TokenService
{
    public function __construct(
        private AccessTokenRepository $tokens,
        private ClockInterface $clock,
        private AuthSettings $settings,
    ) {
    }

    /** @return array{token: string, expires_at: DateTimeImmutable} */
    public function issue(TokenType $type, int $subjectId): array
    {
        $token = bin2hex(random_bytes(32));
        $hours = $type === TokenType::Cms ? $this->settings->cmsTokenHours : $this->settings->attendeeTokenHours;
        $now = $this->clock->now();
        $expiresAt = $now->modify("+{$hours} hours");

        $this->tokens->store($type, $subjectId, hash('sha256', $token), $expiresAt, $now);

        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    public function resolve(string $token): ?Session
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        return $this->tokens->findActive(hash('sha256', $token), $this->clock->now());
    }

    public function revoke(Session $session): void
    {
        $this->tokens->revoke($session->tokenId, $this->clock->now());
    }

    public function revokeAll(TokenType $type, int $subjectId): void
    {
        $this->tokens->revokeAllFor($type, $subjectId, $this->clock->now());
    }
}
