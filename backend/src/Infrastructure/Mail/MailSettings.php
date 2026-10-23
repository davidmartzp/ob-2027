<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

final readonly class MailSettings
{
    public function __construct(
        public string $fromAddress,
        public string $fromName,
        public string $teamAddress,
        public int $sendPerMinute,
    ) {
    }
}
