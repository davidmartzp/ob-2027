<?php

declare(strict_types=1);

namespace App\Application\Attendee;

use App\Application\Auth\TokenService;
use App\Application\Port\RateLimiter;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Attendee\RegistrationTurns;
use App\Domain\Auth\TokenType;
use DateTimeInterface;
use Psr\Clock\ClockInterface;

/**
 * POST /attendees/verify-code: email + participant number (or special code). Within the
 * attendee's turn it returns an attendee token to pick workshops. There is no master code.
 */
final readonly class VerifyAttendeeCode
{
    /** Failed checks per email in 15 minutes: numbers are short, so guessing must be slow. */
    private const int MAX_FAILURES = 10;

    public function __construct(
        private AttendeeRepository $attendees,
        private RegistrationTurns $turns,
        private TokenService $tokens,
        private RateLimiter $limiter,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function execute(array $input): array
    {
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        if ($code === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['valid' => false, 'message' => 'Escribe tu correo y tu número de participante.'];
        }

        $key = 'verify-code:email:' . $email;
        $this->limiter->ensureBelow($key, self::MAX_FAILURES, 15);

        $attendee = $this->attendees->findActiveByEmailAndCode($email, $code);
        if ($attendee === null) {
            $this->limiter->hit($key);

            return ['valid' => false, 'message' => 'Correo o número de participante incorrectos.'];
        }

        $turn = $this->turns->decide($attendee, $this->clock->now());
        if (!$turn->open) {
            return ['valid' => false, 'message' => $turn->message];
        }

        $issued = $this->tokens->issue(TokenType::Attendee, $attendee->id);

        return [
            'valid' => true,
            'id' => $attendee->id,
            'code' => $attendee->specialCode ?? $attendee->code,
            'name' => $attendee->fullName(),
            'token' => $issued['token'],
            'expires_at' => $issued['expires_at']->format(DateTimeInterface::ATOM),
            'message' => $turn->message,
        ];
    }
}
