<?php

declare(strict_types=1);

namespace App\Application\Mail;

use App\Application\Port\MailQueue;
use App\Domain\Attendee\Attendee;
use App\Domain\Attendee\AttendeeRepository;

/** GET /emails/{type}/recipients: who would get it, without sending anything. */
final readonly class ListEmailRecipients
{
    public function __construct(private AttendeeRepository $attendees, private MailQueue $mail)
    {
    }

    /** @return array{type: string, count: int, recipients: list<array{id: int, name: string, lastname: string, email: string, already_sent: bool}>} */
    public function execute(string $typeKey): array
    {
        $type = EmailCatalog::manual($typeKey);
        $already = array_flip($this->mail->attendeesWith($type->key));
        $recipients = array_map(static fn (Attendee $a): array => [
            'id' => $a->id,
            'name' => $a->name,
            'lastname' => $a->lastname,
            'email' => $a->email,
            'already_sent' => isset($already[$a->id]),
        ], $this->attendees->findRecipients($type->recipients));

        return ['type' => $type->key, 'count' => count($recipients), 'recipients' => $recipients];
    }
}
