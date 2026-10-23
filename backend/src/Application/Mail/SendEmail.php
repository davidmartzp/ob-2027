<?php

declare(strict_types=1);

namespace App\Application\Mail;

use App\Application\Port\EmailMessage;
use App\Application\Port\MailQueue;
use App\Application\Shared\Validator;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Shared\Exception\ValidationException;

/**
 * POST /emails/{type}: the CMS sends an email type by hand (replaces sendNumbers,
 * sendInstructions, sendBook…). Each attendee gets each type once; `test` sends a
 * single sample to one address; `ids` restricts (and for resendable types, re-sends).
 */
final readonly class SendEmail
{
    public function __construct(private AttendeeRepository $attendees, private MailQueue $mail)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{queued: int}|array{test_sent_to: string}
     */
    public function execute(string $typeKey, array $input): array
    {
        $type = EmailCatalog::manual($typeKey);
        $data = Validator::validate($input, ['ids' => 'list', 'test' => 'email|max:255']);
        $ids = $data['ids'] === null ? null : array_values(array_unique(array_map('intval', $data['ids'])));
        if ($type->requiresIds && $ids === null && $data['test'] === null) {
            throw ValidationException::field('ids', 'Este correo solo se envía a los asistentes que indiques.');
        }

        $recipients = $this->attendees->findRecipients($type->recipients, $ids);

        if ($data['test'] !== null) {
            $sample = $recipients[0] ?? throw ValidationException::field('test', 'No hay ningún destinatario con el que armar la prueba.');
            $this->mail->queueAndDeliver(new EmailMessage(
                type: 'test:' . $type->key,
                to: $data['test'],
                subject: '[PRUEBA] ' . $type->subject,
                template: $type->template,
                data: EmailVariables::for($sample),
            ));

            return ['test_sent_to' => $data['test']];
        }

        $resend = $ids !== null && $type->resendable;
        $queued = 0;
        foreach ($recipients as $attendee) {
            $id = $this->mail->queue(new EmailMessage(
                type: $type->key,
                to: $attendee->email,
                subject: $type->subject,
                template: $type->template,
                data: EmailVariables::for($attendee),
                attendeeId: $attendee->id,
                uniqueKey: $resend ? null : "{$type->key}:{$attendee->id}",
            ));
            $queued += $id === null ? 0 : 1;
        }

        return ['queued' => $queued];
    }
}
