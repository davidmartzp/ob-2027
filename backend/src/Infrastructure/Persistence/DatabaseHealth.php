<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Connection;
use Psr\Clock\ClockInterface;
use Throwable;

/** Data behind GET /health. */
final readonly class DatabaseHealth
{
    public function __construct(private Connection $db, private ClockInterface $clock)
    {
    }

    /** @return array{status: string, mail_queue_minutes?: int|null} */
    public function check(): array
    {
        try {
            $this->db->select('SELECT 1');

            return ['status' => 'ok', 'mail_queue_minutes' => $this->oldestPendingEmailMinutes()];
        } catch (Throwable $error) {
            error_log('[health] Database unavailable: ' . $error->getMessage());

            return ['status' => 'database_unavailable'];
        }
    }

    /** Minutes the oldest due email has been waiting (null = empty queue), to spot a dead cron. */
    private function oldestPendingEmailMinutes(): ?int
    {
        if (!$this->db->getSchemaBuilder()->hasTable('email_outbox')) {
            return null;
        }
        $now = $this->clock->now();
        $oldest = $this->db->table('email_outbox')
            ->where('status', 'pending')
            ->where('available_at', '<=', $now->format('Y-m-d H:i:s'))
            ->min('available_at');

        return $oldest === null ? null : intdiv($now->getTimestamp() - strtotime((string) $oldest), 60);
    }
}
