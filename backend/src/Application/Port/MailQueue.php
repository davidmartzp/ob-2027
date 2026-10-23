<?php

declare(strict_types=1);

namespace App\Application\Port;

/** Outbox for emails; `bin/console mail:send` (cron) delivers them. */
interface MailQueue
{
    /** @return int|null Outbox id, or null if an email with the same unique key was already queued. */
    public function queue(EmailMessage $message): ?int;
}
