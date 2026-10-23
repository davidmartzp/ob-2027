<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Port\EmailMessage;
use App\Application\Port\MailQueue;
use App\Application\Port\RateLimiter;
use App\Application\Shared\Validator;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Auth\AccessLinkRepository;
use Psr\Clock\ClockInterface;

/**
 * An attendee asks for a sign-in link by email. Several attendees can share an email
 * (families): one email goes out with one single-use link per attendee. The outcome is
 * the same whether the email is registered or not, so it reveals nothing.
 */
final readonly class RequestAccessLink
{
    private const int MAX_REQUESTS_PER_HOUR = 3;

    public function __construct(
        private AttendeeRepository $attendees,
        private AccessLinkRepository $links,
        private RateLimiter $limiter,
        private MailQueue $mail,
        private ClockInterface $clock,
        private AuthSettings $settings,
    ) {
    }

    /** @param array<string, mixed> $input */
    public function execute(array $input): void
    {
        $email = Validator::validate($input, ['email' => 'required|email|max:255'])['email'];
        $key = 'access-link:email:' . $email;
        $this->limiter->ensureBelow($key, self::MAX_REQUESTS_PER_HOUR, 60);
        $this->limiter->hit($key);

        $attendees = $this->attendees->findActiveByEmail($email);
        if ($attendees === []) {
            return;
        }

        $now = $this->clock->now();
        $expiresAt = $now->modify("+{$this->settings->accessLinkMinutes} minutes");
        $participants = [];
        foreach ($attendees as $attendee) {
            $secret = bin2hex(random_bytes(32));
            $this->links->create(hash('sha256', $secret), $attendee->id, $expiresAt, $now);
            $participants[] = ['name' => $attendee->fullName(), 'url' => $this->settings->accessLinkUrl . '?key=' . $secret];
        }

        $this->mail->queue(new EmailMessage(
            type: 'access-link',
            to: $attendees[0]->email,
            subject: 'Tu enlace para entrar a Origami Bogotá',
            template: 'access-link',
            data: ['participants' => $participants, 'minutes' => $this->settings->accessLinkMinutes],
        ));
    }
}
