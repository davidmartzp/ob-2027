<?php

declare(strict_types=1);

namespace App\Application\Attendee;

use App\Application\Mail\EmailCatalog;
use App\Application\Port\EmailMessage;
use App\Application\Port\MailQueue;
use App\Application\Port\RateLimiter;
use App\Application\Shared\Validator;
use App\Domain\Attendee\Attendee;
use App\Domain\Attendee\AttendeeRepository;

/**
 * POST /attendees/remind-code: emails the numbers of everyone registered with that email
 * (replaces lookup-code, which returned them to anyone who knew the email).
 */
final readonly class RemindAttendeeCode
{
    private const int MAX_PER_HOUR = 3;

    public function __construct(
        private AttendeeRepository $attendees,
        private RateLimiter $limiter,
        private MailQueue $mail,
    ) {
    }

    /** @param array<string, mixed> $input */
    public function execute(array $input): void
    {
        $email = Validator::validate($input, ['email' => 'required|email|max:255'])['email'];
        $key = 'remind-code:email:' . $email;
        $this->limiter->ensureBelow($key, self::MAX_PER_HOUR, 60);
        $this->limiter->hit($key);

        $attendees = $this->attendees->findActiveByEmail($email);
        if ($attendees === []) {
            return;
        }

        $type = EmailCatalog::get('code-reminder');
        $this->mail->queueAndDeliver(new EmailMessage(
            type: $type->key,
            to: $attendees[0]->email,
            subject: $type->subject,
            template: $type->template,
            data: ['participants' => array_map(
                static fn (Attendee $a): array => ['name' => $a->fullName(), 'code' => $a->displayCode()],
                $attendees,
            )],
        ));
    }
}
