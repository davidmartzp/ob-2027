<?php

declare(strict_types=1);

namespace App\Application\Port;

/** Email outbox; `bin/console mail:send` (cron) delivers what is pending. */
interface MailQueue
{
    /** @return int|null Outbox id, or null if an email with the same unique key was already queued. */
    public function queue(EmailMessage $message): ?int;

    /**
     * Queues and tries to deliver right away (sign-in links, reminders). If delivery fails
     * the email stays pending for the cron; it never throws because of the mail server.
     */
    public function queueAndDeliver(EmailMessage $message): ?int;

    /** @return list<int> Attendee ids that already have this email type queued or sent. */
    public function attendeesWith(string $type): array;

    /**
     * @return array{items: list<array<string, mixed>>, totals: array<string, int>, page: int, per_page: int, total: int}
     */
    public function log(?string $type, int $page, int $perPage): array;
}
