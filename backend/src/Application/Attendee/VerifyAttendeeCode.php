<?php

declare(strict_types=1);

namespace App\Application\Attendee;

use App\Application\Port\RateLimiter;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Attendee\RegistrationTurns;
use Psr\Clock\ClockInterface;

/**
 * POST /attendees/verify-code: email + participant number (or special code), checked
 * against the attendee's turn, as in the legacy site. There is no master code.
 */
final readonly class VerifyAttendeeCode
{
    /** Failed checks per email in 15 minutes: numbers are short, so guessing must be slow. */
    private const int MAX_FAILURES = 10;

    public function __construct(
        private AttendeeRepository $attendees,
        private RegistrationTurns $turns,
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

        return [
            'valid' => true,
            'id' => $attendee->id,
            'code' => $attendee->specialCode ?? $attendee->code,
            'name' => $attendee->fullName(),
            'message' => $turn->message,
        ];
    }
}
