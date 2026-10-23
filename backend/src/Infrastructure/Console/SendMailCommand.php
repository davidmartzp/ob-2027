<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Infrastructure\Mail\OutboxProcessor;

/** The hosting's cron runs this every minute: `php bin/console mail:send`. */
final readonly class SendMailCommand implements Command
{
    public function __construct(private OutboxProcessor $outbox)
    {
    }

    public function name(): string
    {
        return 'mail:send';
    }

    public function description(): string
    {
        return 'Deliver pending emails from the outbox (run by cron every minute)';
    }

    public function run(array $arguments): int
    {
        $result = $this->outbox->processDue();
        echo "Sent: {$result['sent']} · retrying: {$result['retrying']} · failed: {$result['failed']}\n";

        return 0;
    }
}
