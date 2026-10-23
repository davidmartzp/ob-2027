<?php

declare(strict_types=1);

namespace Tests\Functional\Attendee;

use App\Domain\Attendee\AttendeeStatus;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;

final class VerifyCodeTest extends FunctionalTestCase
{
    private int $id;

    protected function environment(): array
    {
        return ['EVENT_SATURDAY' => '2027-06-05', 'EVENT_SUNDAY' => '2027-06-06'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock->set('2027-06-05 10:00');
        $this->id = Attendees::create($this->db(), ['name' => 'Zoé', 'lastname' => 'Peña', 'email' => 'zoe@ejemplo.com', 'code' => 27]);
    }

    private function verify(string $email, string $code): ResponseInterface
    {
        return $this->request('POST', '/attendees/verify-code', ['email' => $email, 'code' => $code]);
    }

    #[Test]
    public function right_email_and_number_in_turn_are_valid(): void
    {
        $body = $this->json($this->verify('Zoe@Ejemplo.com', '27'));

        self::assertTrue($body['valid']);
        self::assertSame($this->id, $body['id']);
        self::assertSame('Zoé Peña', $body['name']);
        self::assertArrayNotHasKey('token', $body, 'Attendees do not sign in');
    }

    #[Test]
    public function the_special_code_also_works(): void
    {
        Attendees::create($this->db(), ['email' => 'staff@ejemplo.com', 'code' => null, 'special_code' => 'A12']);

        $body = $this->json($this->verify('staff@ejemplo.com', 'a12'));

        self::assertTrue($body['valid']);
        self::assertSame('A12', $body['code']);
    }

    #[Test]
    public function wrong_email_or_number_give_the_same_answer(): void
    {
        $wrongCode = $this->json($this->verify('zoe@ejemplo.com', '28'));
        $wrongEmail = $this->json($this->verify('otra@ejemplo.com', '27'));

        self::assertFalse($wrongCode['valid']);
        self::assertSame($wrongCode, $wrongEmail);
        self::assertArrayNotHasKey('token', $wrongCode);
    }

    #[Test]
    public function the_legacy_master_code_does_nothing(): void
    {
        $body = $this->json($this->verify('zoe@ejemplo.com', '99099'));

        self::assertFalse($body['valid']);
    }

    #[Test]
    public function deleted_attendees_cannot_enter(): void
    {
        $this->db()->table('attendees')->where('id', $this->id)->update(['status' => AttendeeStatus::Deleted->value]);

        self::assertFalse($this->json($this->verify('zoe@ejemplo.com', '27'))['valid']);
    }

    #[Test]
    public function outside_the_turn_it_is_not_valid(): void
    {
        $this->clock->set('2027-06-05 07:30');

        $body = $this->json($this->verify('zoe@ejemplo.com', '27'));

        self::assertFalse($body['valid']);
        self::assertStringContainsString('08:00', $body['message']);
    }

    #[Test]
    public function rehearsal_mode_is_ignored_in_production(): void
    {
        $this->clock->set('2026-12-01 10:00');
        $rehearsal = $this->withEnvironment(['APP_ENV' => 'testing', 'TURN_BYPASS' => 'true']);
        $production = $this->withEnvironment(['APP_ENV' => 'production', 'TURN_BYPASS' => 'true', 'MAILER_DSN' => 'smtps://u:p@smtp.example.com:465']);

        self::assertTrue($rehearsal['valid']);
        self::assertFalse($production['valid']);
    }

    #[Test]
    public function guessing_numbers_is_limited_per_email(): void
    {
        foreach (range(100, 109) as $guess) {
            $this->verify('zoe@ejemplo.com', (string) $guess);
        }

        self::assertSame(429, $this->verify('zoe@ejemplo.com', '27')->getStatusCode());
    }

    /** Boots a separate app with extra environment and the same data, and verifies there. */
    private function withEnvironment(array $environment): array
    {
        $this->setUpApp($environment);
        $this->id = Attendees::create($this->db(), ['email' => 'zoe@ejemplo.com', 'code' => 27]);

        return $this->json($this->verify('zoe@ejemplo.com', '27'));
    }
}
