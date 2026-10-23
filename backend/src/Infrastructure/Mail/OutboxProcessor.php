<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use Illuminate\Database\Connection;
use Psr\Clock\ClockInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Delivers the `email_outbox`: up to MAIL_SEND_PER_MINUTE per run (cron every minute),
 * pausing MAIL_SEND_INTERVAL_MS between emails (SMTP servers refuse bursts),
 * 3 attempts at most (retries after 1 and 5 minutes), then `failed`. Each row is
 * claimed with a conditional update so two workers never send the same email.
 */
final readonly class OutboxProcessor
{
    public const int MAX_ATTEMPTS = 3;
    /** Minutes to wait after the 1st and 2nd failed attempt. */
    private const array RETRY_MINUTES = [1 => 1, 2 => 5];
    /** A row stuck in `sending` longer than this (worker died) goes back to `pending`. */
    private const int STUCK_MINUTES = 10;
    private const string DATETIME = 'Y-m-d H:i:s';

    public function __construct(
        private Connection $db,
        private MailerInterface $mailer,
        private ClockInterface $clock,
        private MailSettings $settings,
    ) {
    }

    /** @return array{sent: int, failed: int, retrying: int} */
    public function processDue(): array
    {
        $now = $this->clock->now();
        $this->db->table('email_outbox')
            ->where('status', 'sending')
            ->where('updated_at', '<', $now->modify('-' . self::STUCK_MINUTES . ' minutes')->format(self::DATETIME))
            ->update(['status' => 'pending']);

        $ids = $this->db->table('email_outbox')
            ->where('status', 'pending')
            ->where('available_at', '<=', $now->format(self::DATETIME))
            ->orderBy('available_at')
            ->orderBy('id')
            ->limit($this->settings->sendPerMinute)
            ->pluck('id')
            ->all();

        $result = ['sent' => 0, 'failed' => 0, 'retrying' => 0];
        foreach ($ids as $index => $id) {
            if ($index > 0 && $this->settings->sendIntervalMs > 0) {
                usleep($this->settings->sendIntervalMs * 1000);
            }
            $outcome = $this->deliver((int) $id);
            if ($outcome !== null) {
                $result[$outcome]++;
            }
        }

        return $result;
    }

    /** @return 'sent'|'failed'|'retrying'|null null when another worker already took it. */
    public function deliver(int $id): ?string
    {
        $now = $this->clock->now()->format(self::DATETIME);
        $claimed = $this->db->table('email_outbox')
            ->where('id', $id)
            ->where('status', 'pending')
            ->update(['status' => 'sending', 'updated_at' => $now]);
        if ($claimed !== 1) {
            return null;
        }

        $row = $this->db->table('email_outbox')->where('id', $id)->first();
        $attempts = (int) $row->attempts + 1;
        try {
            $this->mailer->send((new Email())
                ->from(new Address($this->settings->fromAddress, $this->settings->fromName))
                ->to($row->email)
                ->subject($row->subject)
                ->html($row->html)
                ->text($row->text ?? ''));

            $this->db->table('email_outbox')->where('id', $id)->update([
                'status' => 'sent', 'attempts' => $attempts, 'sent_at' => $now, 'error' => null, 'updated_at' => $now,
            ]);

            return 'sent';
        } catch (Throwable $error) {
            error_log("[mail] Outbox #{$id} attempt {$attempts} failed: " . $error->getMessage());
            $final = $attempts >= self::MAX_ATTEMPTS;
            $this->db->table('email_outbox')->where('id', $id)->update([
                'status' => $final ? 'failed' : 'pending',
                'attempts' => $attempts,
                'error' => mb_substr($error->getMessage(), 0, 2000),
                'available_at' => $final ? $row->available_at : $this->clock->now()->modify('+' . self::RETRY_MINUTES[$attempts] . ' minutes')->format(self::DATETIME),
                'updated_at' => $now,
            ]);

            return $final ? 'failed' : 'retrying';
        }
    }
}
