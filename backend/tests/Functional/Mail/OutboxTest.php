<?php

declare(strict_types=1);

namespace Tests\Functional\Mail;

use App\Application\Port\EmailMessage;
use App\Application\Port\MailQueue;
use App\Infrastructure\Mail\OutboxProcessor;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FunctionalTestCase;

final class OutboxTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // The failing-server tests log each failed attempt; keep the test output clean.
        $this->previousLog = (string) ini_set('error_log', '/dev/null');
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->previousLog);
        parent::tearDown();
    }

    private string $previousLog;

    private function queue(string $to = 'a@ejemplo.com', ?string $uniqueKey = null): ?int
    {
        return $this->service(MailQueue::class)->queue(new EmailMessage('book', $to, 'Páginas de origami', 'book', [], uniqueKey: $uniqueKey));
    }

    private function process(): array
    {
        return $this->service(OutboxProcessor::class)->processDue();
    }

    private function row(int $id): object
    {
        return $this->db()->table('email_outbox')->where('id', $id)->first();
    }

    #[Test]
    public function sends_due_emails_with_html_and_text(): void
    {
        $id = $this->queue();

        self::assertSame(['sent' => 1, 'failed' => 0, 'retrying' => 0], $this->process());
        self::assertSame('sent', $this->row($id)->status);
        self::assertSame(['a@ejemplo.com'], $this->mailer->recipients());
        $email = $this->mailer->sent[0];
        self::assertSame('inscripciones@origamibogota.com', $email->getFrom()[0]->getAddress());
        self::assertNotEmpty($email->getHtmlBody());
        self::assertNotEmpty($email->getTextBody());
        self::assertSame(['sent' => 0, 'failed' => 0, 'retrying' => 0], $this->process(), 'Nothing is sent twice');
    }

    #[Test]
    public function a_down_mail_server_retries_after_1_and_5_minutes_then_fails(): void
    {
        $this->clock->set('2027-06-05 10:00');
        $id = $this->queue();
        $this->mailer->failing = true;

        self::assertSame(['sent' => 0, 'failed' => 0, 'retrying' => 1], $this->process());
        self::assertSame('2027-06-05 10:01:00', $this->row($id)->available_at);
        self::assertSame(0, $this->process()['retrying'], 'Not before its time');

        $this->clock->advance('+1 minute');
        $this->process();
        self::assertSame('2027-06-05 10:06:00', $this->row($id)->available_at);

        $this->clock->advance('+5 minutes');
        self::assertSame(['sent' => 0, 'failed' => 1, 'retrying' => 0], $this->process());
        $row = $this->row($id);
        self::assertSame('failed', $row->status);
        self::assertSame(3, (int) $row->attempts);
        self::assertStringContainsString('Connection could not be established', $row->error);
    }

    #[Test]
    public function a_retry_that_succeeds_is_sent(): void
    {
        $id = $this->queue();
        $this->mailer->failing = true;
        $this->process();

        $this->mailer->failing = false;
        $this->clock->advance('+1 minute');
        $this->process();

        self::assertSame('sent', $this->row($id)->status);
        self::assertSame(2, (int) $this->row($id)->attempts);
    }

    #[Test]
    public function sends_at_most_the_configured_amount_per_run(): void
    {
        foreach (range(1, 35) as $i) {
            $this->queue("persona{$i}@ejemplo.com");
        }

        self::assertSame(30, $this->process()['sent']);
        self::assertSame(5, $this->process()['sent']);
    }

    #[Test]
    public function the_same_unique_key_is_queued_once(): void
    {
        self::assertNotNull($this->queue(uniqueKey: 'book:7'));
        self::assertNull($this->queue(uniqueKey: 'book:7'));
        self::assertSame(1, $this->db()->table('email_outbox')->count());
    }

    #[Test]
    public function an_email_stuck_in_sending_goes_back_to_pending(): void
    {
        $this->clock->set('2027-06-05 10:00');
        $id = $this->queue();
        $this->db()->table('email_outbox')->where('id', $id)->update(['status' => 'sending', 'updated_at' => '2027-06-05 09:45:00']);

        self::assertSame(1, $this->process()['sent']);
    }

    #[Test]
    public function the_console_command_runs_the_outbox(): void
    {
        $this->queue();

        ob_start();
        $exit = $this->service(\App\Infrastructure\Console\SendMailCommand::class)->run([]);
        $output = (string) ob_get_clean();

        self::assertSame(0, $exit);
        self::assertStringContainsString('Sent: 1', $output);
    }
}
