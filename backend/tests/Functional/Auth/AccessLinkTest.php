<?php

declare(strict_types=1);

namespace Tests\Functional\Auth;

use App\Domain\Attendee\AttendeeStatus;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\FunctionalTestCase;

final class AccessLinkTest extends FunctionalTestCase
{
    protected function environment(): array
    {
        return ['SITE_URL' => 'https://origamibogota.com', 'ACCESS_LINK_PATH' => 'acceso'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $base = ['lastname' => 'Rojas', 'age' => 40, 'phone' => '3001234567', 'paydate' => '2027-03-01 10:00:00', 'status' => AttendeeStatus::Confirmed->value];
        $this->db()->table('attendees')->insert([
            ['id' => 1, 'name' => 'Marta', 'email' => 'familia@ejemplo.com'] + $base,
            ['id' => 2, 'name' => 'Tomás', 'email' => 'Familia@Ejemplo.com', 'age' => 9] + $base,
            ['id' => 3, 'name' => 'Borrado', 'email' => 'familia@ejemplo.com', 'status' => AttendeeStatus::Deleted->value] + $base,
        ]);
    }

    private function requestLink(string $email): ResponseInterface
    {
        return $this->request('POST', '/auth/attendee-link', ['email' => $email]);
    }

    /** Keys of the links in the queued email. @return list<string> */
    private function keysInEmail(): array
    {
        $text = (string) $this->db()->table('email_outbox')->where('type', 'access-link')->value('text');
        preg_match_all('#https://origamibogota\.com/acceso\?key=([a-f0-9]{64})#', $text, $matches);

        return $matches[1];
    }

    #[Test]
    public function unregistered_email_gets_the_same_answer_and_nothing_is_sent(): void
    {
        $registered = $this->requestLink('familia@ejemplo.com');
        $unregistered = $this->requestLink('nadie@ejemplo.com');

        self::assertSame(202, $registered->getStatusCode());
        self::assertSame(202, $unregistered->getStatusCode());
        self::assertSame((string) $registered->getBody(), (string) $unregistered->getBody());
        self::assertSame(1, $this->db()->table('email_outbox')->count());
    }

    #[Test]
    public function a_family_gets_one_email_with_one_link_per_attendee(): void
    {
        $this->requestLink('FAMILIA@ejemplo.com');

        $email = $this->db()->table('email_outbox')->first();
        self::assertSame('pending', $email->status);
        self::assertStringContainsString('Entrar como Marta Rojas', $email->html);
        self::assertStringContainsString('Entrar como Tomás Rojas', $email->html);
        self::assertStringNotContainsString('Borrado', $email->html);
        self::assertCount(2, $this->keysInEmail());
        self::assertSame(2, $this->db()->table('access_links')->count());
        self::assertSame(0, $this->db()->table('access_links')->whereIn('hash', $this->keysInEmail())->count(), 'Keys are not stored in plain text');
    }

    #[Test]
    public function redeeming_gives_an_attendee_token_for_that_person(): void
    {
        $this->requestLink('familia@ejemplo.com');
        [$martaKey] = $this->keysInEmail();

        $response = $this->request('POST', '/auth/redeem-link', ['key' => $martaKey]);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        self::assertSame(['id' => 1, 'name' => 'Marta Rojas'], $body['attendee']);
        self::assertSame('attendee', $this->db()->table('access_tokens')->where('hash', hash('sha256', $body['token']))->value('type'));
    }

    #[Test]
    public function a_reused_link_is_401(): void
    {
        $this->requestLink('familia@ejemplo.com');
        [$key] = $this->keysInEmail();

        self::assertSame(200, $this->request('POST', '/auth/redeem-link', ['key' => $key])->getStatusCode());
        self::assertSame(401, $this->request('POST', '/auth/redeem-link', ['key' => $key])->getStatusCode());
    }

    #[Test]
    public function an_expired_link_is_401(): void
    {
        $this->clock->set('2027-06-05 08:00');
        $this->requestLink('familia@ejemplo.com');
        [$key] = $this->keysInEmail();

        $this->clock->advance('+31 minutes');
        self::assertSame(401, $this->request('POST', '/auth/redeem-link', ['key' => $key])->getStatusCode());
    }

    #[Test]
    public function a_made_up_key_is_401(): void
    {
        self::assertSame(401, $this->request('POST', '/auth/redeem-link', ['key' => str_repeat('b', 64)])->getStatusCode());
        self::assertSame(401, $this->request('POST', '/auth/redeem-link', ['key' => "' OR 1=1 --"])->getStatusCode());
    }

    #[Test]
    public function the_fourth_request_in_an_hour_is_429(): void
    {
        foreach (range(1, 3) as $_) {
            self::assertSame(202, $this->requestLink('familia@ejemplo.com')->getStatusCode());
        }

        self::assertSame(429, $this->requestLink('familia@ejemplo.com')->getStatusCode());
        self::assertSame(3, $this->db()->table('email_outbox')->count());
    }

    #[Test]
    public function attendee_data_is_escaped_in_the_email(): void
    {
        $this->db()->table('attendees')->insert(['id' => 9, 'name' => '<script>alert(1)</script>', 'lastname' => 'X', 'age' => 30, 'email' => 'raro@ejemplo.com', 'phone' => '1', 'paydate' => '2027-03-01']);

        $this->requestLink('raro@ejemplo.com');

        $html = (string) $this->db()->table('email_outbox')->value('html');
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }
}
