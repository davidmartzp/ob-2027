<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Auth\AccessLinkRepository;
use App\Domain\Auth\TokenType;
use App\Domain\Shared\Exception\UnauthorizedException;
use DateTimeInterface;
use Psr\Clock\ClockInterface;

/** Exchanges a sign-in link key for an attendee token. Each key works once. */
final readonly class RedeemAccessLink
{
    public function __construct(
        private AccessLinkRepository $links,
        private AttendeeRepository $attendees,
        private TokenService $tokens,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{token: string, token_type: string, expires_at: string, attendee: array{id: int, name: string}}
     */
    public function execute(array $input): array
    {
        $secret = (string) ($input['key'] ?? '');
        $invalid = new UnauthorizedException('El enlace no es válido, caducó o ya se usó. Pide uno nuevo.');
        if (preg_match('/^[a-f0-9]{64}$/', $secret) !== 1) {
            throw $invalid;
        }

        $attendeeId = $this->links->consume(hash('sha256', $secret), $this->clock->now());
        $attendee = $attendeeId === null ? null : $this->attendees->findActive($attendeeId);
        if ($attendee === null) {
            throw $invalid;
        }

        $issued = $this->tokens->issue(TokenType::Attendee, $attendee->id);

        return [
            'token' => $issued['token'],
            'token_type' => 'Bearer',
            'expires_at' => $issued['expires_at']->format(DateTimeInterface::ATOM),
            'attendee' => ['id' => $attendee->id, 'name' => $attendee->fullName()],
        ];
    }
}
