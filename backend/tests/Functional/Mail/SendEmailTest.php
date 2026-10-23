<?php

declare(strict_types=1);

namespace Tests\Functional\Mail;

use App\Application\Auth\TokenService;
use App\Domain\Attendee\AttendeeStatus;
use App\Domain\Auth\TokenType;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;

final class SendEmailTest extends FunctionalTestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->service(TokenService::class)->issue(TokenType::Cms, 1)['token'];
    }

    private function send(string $type, array $body = []): ResponseInterface
    {
        return $this->request('POST', "/emails/{$type}", $body, self::bearer($this->token));
    }

    private function outboxCount(string $type): int
    {
        return $this->db()->table('email_outbox')->where('type', $type)->count();
    }

    #[Test]
    public function requires_a_cms_session(): void
    {
        self::assertSame(401, $this->request('POST', '/emails/book')->getStatusCode());
        self::assertSame(401, $this->request('POST', '/emails/book', [], self::bearer(str_repeat('a', 64)))->getStatusCode());
    }

    #[Test]
    public function instructions_go_to_confirmed_attendees_only(): void
    {
        foreach (range(1, 8) as $_) {
            Attendees::create($this->db());
        }
        Attendees::create($this->db(), ['status' => AttendeeStatus::Pending->value]);
        Attendees::create($this->db(), ['status' => AttendeeStatus::Deleted->value]);

        $response = $this->send('instructions');

        self::assertSame(202, $response->getStatusCode());
        self::assertSame(['queued' => 8], $this->json($response));
        self::assertSame(8, $this->outboxCount('instructions'));
    }

    #[Test]
    public function general_info_goes_to_everyone_not_deleted(): void
    {
        Attendees::create($this->db());
        Attendees::create($this->db(), ['status' => AttendeeStatus::Pending->value]);
        Attendees::create($this->db(), ['status' => AttendeeStatus::Deleted->value]);

        self::assertSame(['queued' => 2], $this->json($this->send('general-info')));
    }

    #[Test]
    public function launching_twice_sends_once(): void
    {
        Attendees::create($this->db());
        Attendees::create($this->db());

        self::assertSame(['queued' => 2], $this->json($this->send('book')));
        self::assertSame(['queued' => 0], $this->json($this->send('book')));
    }

    #[Test]
    public function the_code_email_only_goes_to_numbered_attendees_and_can_be_resent(): void
    {
        $numbered = Attendees::create($this->db(), ['code' => 27]);
        Attendees::create($this->db(), ['code' => null]);

        self::assertSame(['queued' => 1], $this->json($this->send('code')));
        self::assertSame(['queued' => 0], $this->json($this->send('code')));
        self::assertSame(['queued' => 1], $this->json($this->send('code', ['ids' => [$numbered]])), 'Explicit ids re-send');
        self::assertSame(2, $this->outboxCount('code'));
    }

    #[Test]
    public function a_test_send_goes_only_to_the_test_address_and_marks_nobody(): void
    {
        Attendees::create($this->db(), ['name' => 'Zoé']);

        $response = $this->send('general-info', ['test' => 'equipo@origamibogota.com']);

        self::assertSame(['test_sent_to' => 'equipo@origamibogota.com'], $this->json($response));
        self::assertSame(['equipo@origamibogota.com'], $this->mailer->recipients());
        self::assertStringStartsWith('[PRUEBA]', $this->mailer->sent[0]->getSubject());
        self::assertStringContainsString('Zoé', (string) $this->mailer->sent[0]->getHtmlBody());
        self::assertSame(['queued' => 1], $this->json($this->send('general-info')), 'The real send is still pending for everyone');
    }

    #[Test]
    public function the_correction_requires_explicit_ids(): void
    {
        $id = Attendees::create($this->db());

        $response = $this->send('correction');
        self::assertSame(422, $response->getStatusCode());
        self::assertArrayHasKey('ids', $this->json($response)['error']['fields']);
        self::assertSame(['queued' => 1], $this->json($this->send('correction', ['ids' => [$id]])));
    }

    #[Test]
    public function unknown_and_automatic_types_are_404(): void
    {
        self::assertSame(404, $this->send('does-not-exist')->getStatusCode());
        self::assertSame(404, $this->send('team-notice')->getStatusCode());
        self::assertSame(404, $this->send('access-link')->getStatusCode());
    }

    #[Test]
    public function recipients_preview_counts_without_sending(): void
    {
        $first = Attendees::create($this->db(), ['code' => 1]);
        Attendees::create($this->db(), ['code' => 2]);
        Attendees::create($this->db(), ['code' => null]);
        $this->send('code', ['ids' => [$first]]);

        $body = $this->json($this->request('GET', '/emails/code/recipients', headers: self::bearer($this->token)));

        self::assertSame(2, $body['count']);
        self::assertSame([true, false], array_column($body['recipients'], 'already_sent'));
        self::assertSame(1, $this->outboxCount('code'), 'Nothing new was queued');
    }

    #[Test]
    public function the_log_shows_status_totals(): void
    {
        Attendees::create($this->db());
        Attendees::create($this->db());
        $this->send('book');
        $this->service(\App\Infrastructure\Mail\OutboxProcessor::class)->deliver((int) $this->db()->table('email_outbox')->min('id'));

        $body = $this->json($this->request('GET', '/emails/log?type=book', headers: self::bearer($this->token)));

        self::assertSame(2, $body['total']);
        self::assertSame(1, $body['totals']['sent']);
        self::assertSame(1, $body['totals']['pending']);
        self::assertArrayNotHasKey('html', $body['items'][0], 'The log does not repeat email bodies');
    }
}
