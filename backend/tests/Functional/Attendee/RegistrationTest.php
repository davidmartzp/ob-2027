<?php

declare(strict_types=1);

namespace Tests\Functional\Attendee;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;

final class RegistrationTest extends FunctionalTestCase
{
    protected function environment(): array
    {
        return ['MAIL_TEAM_ADDRESS' => 'equipo@origamibogota.test'];
    }

    /** The exact JSON the new site sends (`EnvioInscripcion` in frontend/src/app/core/registro.ts). */
    private static function form(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Zoé', 'lastname' => 'Peña', 'age' => 34, 'country' => 'Colombia (+57)', 'city' => 'Bogotá',
            'email' => 'zoe@ejemplo.com', 'phone' => '3001234567', 'companion' => '', 'idname' => '1020304050',
            'paymethod' => 'Nequi', 'paydate' => '2027-03-01', 'receipt' => 'R-001', 'group' => 0, 'origamigroup' => '',
            'medium' => 'Instagram', 'othermedium' => '', 'info' => 'Instagram', 'cards' => 1, 'cardgroup' => '',
            'days' => 'T', 'printCertificate' => 1, 'go_to_chiva' => 0, 'chiva_companions' => 0,
        ];
    }

    private function register(array $form, string $ip = '203.0.113.10'): ResponseInterface
    {
        return $this->request('POST', '/attendees', $form, ip: $ip);
    }

    #[Test]
    public function a_valid_registration_is_pending_and_gets_the_next_number(): void
    {
        Attendees::create($this->db(), ['code' => 140]);

        $response = $this->register(self::form());

        self::assertSame(201, $response->getStatusCode());
        $body = $this->json($response);
        self::assertSame(0, $body['status']);
        self::assertSame(141, $body['code']);
        $row = $this->db()->table('attendees')->where('id', $body['id'])->first();
        self::assertSame('Zoé', $row->name);
        self::assertSame('2027-03-01 00:00:00', $row->paydate);
        self::assertSame(1, (int) $row->cards);
        self::assertSame(1, (int) $row->printCertificate);
    }

    #[Test]
    public function children_and_guests_get_no_number(): void
    {
        self::assertNull($this->json($this->register(self::form(['age' => 9])))['code']);
        self::assertNull($this->json($this->register(self::form(['paymethod' => 'Invitado(a)'])))['code']);
        self::assertSame(1, $this->json($this->register(self::form(['paymethod' => 'Becado(a)'])))['code']);
    }

    #[Test]
    public function consecutive_registrations_get_consecutive_numbers(): void
    {
        $codes = array_map(fn (int $i) => $this->json($this->register(self::form(['email' => "p{$i}@ejemplo.com"])))['code'], range(1, 5));

        self::assertSame([1, 2, 3, 4, 5], $codes);
    }

    #[Test]
    public function the_free_text_medium_wins(): void
    {
        $id = $this->json($this->register(self::form(['info' => 'Otro', 'othermedium' => 'Me contó mi tía'])))['id'];

        self::assertSame('Me contó mi tía', $this->db()->table('attendees')->where('id', $id)->value('info'));
    }

    #[Test]
    public function welcome_and_team_notice_are_queued(): void
    {
        $id = $this->json($this->register(self::form()))['id'];

        $emails = $this->db()->table('email_outbox')->orderBy('id')->get(['type', 'email', 'html']);
        self::assertSame(['welcome', 'team-notice'], $emails->pluck('type')->all());
        self::assertSame(['zoe@ejemplo.com', 'equipo@origamibogota.test'], $emails->pluck('email')->all());
        self::assertStringContainsString('Zoé', $emails[0]->html);
        self::assertStringContainsString('R-001', $emails[1]->html, 'The team sees the payment receipt');
        self::assertSame(2, $this->db()->table('email_outbox')->where('id_attendee', $id)->count());
    }

    #[Test]
    public function a_down_mail_server_does_not_break_the_registration(): void
    {
        $this->mailer->failing = true;

        self::assertSame(201, $this->register(self::form())->getStatusCode());
        self::assertSame(1, $this->db()->table('attendees')->count());
    }

    #[Test]
    public function invalid_data_is_rejected_field_by_field(): void
    {
        $response = $this->register(self::form(['email' => 'pepito', 'age' => 200, 'days' => 'X', 'name' => '', 'paymethod' => str_repeat('x', 21)]));

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(['name', 'age', 'email', 'paymethod', 'days'], array_keys($this->json($response)['error']['fields']));
        self::assertSame(0, $this->db()->table('attendees')->count());
    }

    #[Test]
    public function families_can_share_an_email(): void
    {
        $this->register(self::form(['email' => 'familia@ejemplo.com']));

        self::assertSame(201, $this->register(self::form(['email' => 'familia@ejemplo.com', 'name' => 'Tomás', 'age' => 9]))->getStatusCode());
    }

    #[Test]
    public function the_honeypot_answers_ok_but_saves_nothing(): void
    {
        $response = $this->register(self::form(['website' => 'http://spam.example']));

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(0, $this->db()->table('attendees')->count());
        self::assertSame(0, $this->db()->table('email_outbox')->count());
    }

    #[Test]
    public function the_eleventh_registration_in_an_hour_from_one_ip_is_429(): void
    {
        foreach (range(1, 10) as $i) {
            self::assertSame(201, $this->register(self::form(['email' => "p{$i}@ejemplo.com"]), '198.51.100.1')->getStatusCode());
        }

        self::assertSame(429, $this->register(self::form(), '198.51.100.1')->getStatusCode());
        self::assertSame(201, $this->register(self::form(), '198.51.100.2')->getStatusCode());
    }
}
