<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Application\Port\EmailMessage;
use App\Application\Port\MailQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\UniqueConstraintViolationException;
use Psr\Clock\ClockInterface;
use Twig\Environment;

/** Renders the message with Twig (data escaped) and stores it in the `email_outbox` table. */
final readonly class TwigMailQueue implements MailQueue
{
    public function __construct(private Connection $db, private Environment $twig, private ClockInterface $clock)
    {
    }

    public function queue(EmailMessage $message): ?int
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        try {
            return (int) $this->db->table('email_outbox')->insertGetId([
                'type' => $message->type,
                'id_attendee' => $message->attendeeId,
                'email' => $message->to,
                // A subject never carries line breaks (header injection).
                'subject' => trim((string) preg_replace('/[\r\n]+/', ' ', $message->subject)),
                'html' => $this->twig->render("emails/{$message->template}.html.twig", $message->data),
                'text' => $this->twig->render("emails/{$message->template}.txt.twig", $message->data),
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => $now,
                'unique_key' => $message->uniqueKey,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }
}
