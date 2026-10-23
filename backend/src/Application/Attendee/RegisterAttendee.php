<?php

declare(strict_types=1);

namespace App\Application\Attendee;

use App\Application\Mail\EmailCatalog;
use App\Application\Mail\EmailVariables;
use App\Application\Mail\TeamMailbox;
use App\Application\Port\EmailMessage;
use App\Application\Port\MailQueue;
use App\Application\Port\RateLimiter;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Attendee\AttendeeStatus;
use App\Domain\Attendee\CodePolicy;
use Psr\Clock\ClockInterface;

/**
 * POST /attendees: registration from the public site. Eligible attendees get their
 * number right away; the welcome email and the team notice are queued (a mail server
 * failure never breaks the registration).
 */
final readonly class RegisterAttendee
{
    private const int MAX_PER_HOUR_PER_IP = 10;

    public function __construct(
        private AttendeeRepository $attendees,
        private CodePolicy $codes,
        private RateLimiter $limiter,
        private MailQueue $mail,
        private TeamMailbox $team,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{id: int, status: int, code: int|null}
     */
    public function execute(array $input, string $ip): array
    {
        $key = 'register:ip:' . $ip;
        $this->limiter->ensureBelow($key, self::MAX_PER_HOUR_PER_IP, 60);
        $this->limiter->hit($key);

        // Honeypot: a field humans never see. Bots that fill it get a normal-looking answer.
        if (trim((string) ($input['website'] ?? '')) !== '') {
            return ['id' => 0, 'status' => AttendeeStatus::Pending->value, 'code' => null];
        }

        $fields = AttendeeInput::forRegistration($input, $this->clock->now()->format('Y-m-d H:i:s'));
        $fields['status'] = AttendeeStatus::Pending->value;
        $fields['staff'] = false;
        $attendee = $this->attendees->register($fields, $this->codes->isEligible($fields['age'], false, $fields['paymethod']));

        $variables = EmailVariables::for($attendee);
        foreach (['welcome' => $attendee->email, 'team-notice' => $this->team->address] as $typeKey => $to) {
            $type = EmailCatalog::get($typeKey);
            $this->mail->queue(new EmailMessage(
                type: $type->key,
                to: $to,
                subject: $type->subject,
                template: $type->template,
                data: $variables,
                attendeeId: $attendee->id,
                uniqueKey: "{$type->key}:{$attendee->id}",
            ));
        }

        return ['id' => $attendee->id, 'status' => $attendee->status->value, 'code' => $attendee->code];
    }
}
