<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

final readonly class MailSettings
{
    public function __construct(
        public string $fromAddress,
        public string $fromName,
        public int $sendPerMinute,
        /** Pause between two emails of a run (SMTP servers limit emails per second; legacy slept 1 s). */
        public int $sendIntervalMs = 1000,
    ) {
    }
}
