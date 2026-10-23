<?php

declare(strict_types=1);

namespace Tests\Functional\Auth;

use App\Application\Auth\ChangeUserPassword;
use App\Application\Auth\CreateUser;
use App\Domain\Shared\Exception\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\FunctionalTestCase;

final class LoginTest extends FunctionalTestCase
{
    private const string PASSWORD = 'origami-bogota-2027';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service(CreateUser::class)->execute('equipo@origamibogota.com', 'Equipo OB', self::PASSWORD);
    }

    private function login(string $email = 'equipo@origamibogota.com', string $password = self::PASSWORD, string $ip = '203.0.113.10'): ResponseInterface
    {
        return $this->request('POST', '/auth/login', ['email' => $email, 'password' => $password], ip: $ip);
    }

    #[Test]
    public function valid_credentials_return_a_token_and_the_user_without_password(): void
    {
        $response = $this->login();

        self::assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $body['access_token']);
        self::assertSame('Bearer', $body['token_type']);
        self::assertSame(['id', 'name', 'email'], array_keys($body['user']));
        self::assertStringNotContainsString('password', (string) $response->getBody());
    }

    #[Test]
    public function the_token_is_not_stored_in_plain_text(): void
    {
        $token = $this->json($this->login())['access_token'];

        $row = $this->db()->table('access_tokens')->first();
        self::assertNotSame($token, $row->hash);
        self::assertSame(hash('sha256', $token), $row->hash);
    }

    #[Test]
    public function unknown_email_and_wrong_password_look_the_same(): void
    {
        $wrongPassword = $this->login(password: 'not-the-password');
        $unknownEmail = $this->login(email: 'nobody@example.com');

        self::assertSame(401, $wrongPassword->getStatusCode());
        self::assertSame(401, $unknownEmail->getStatusCode());
        self::assertSame((string) $wrongPassword->getBody(), (string) $unknownEmail->getBody());
    }

    #[Test]
    public function legacy_laravel_hash_still_works_and_is_upgraded(): void
    {
        $this->db()->table('users')->insert([
            'name' => 'Legacy user',
            'email' => 'legacy@origamibogota.com',
            'password' => password_hash('same-old-password', PASSWORD_BCRYPT, ['cost' => 10]),
        ]);

        self::assertSame(200, $this->login('legacy@origamibogota.com', 'same-old-password')->getStatusCode());
        self::assertStringStartsWith('$argon2id$', $this->db()->table('users')->where('email', 'legacy@origamibogota.com')->value('password'));
        self::assertSame(200, $this->login('legacy@origamibogota.com', 'same-old-password')->getStatusCode());
    }

    #[Test]
    public function sixth_failure_per_email_is_429_even_with_the_right_password(): void
    {
        foreach (range(1, 5) as $i) {
            // A different IP each time: what is limited here is the email.
            self::assertSame(401, $this->login(password: 'wrong', ip: "198.51.100.{$i}")->getStatusCode());
        }

        $response = $this->login(ip: '198.51.100.99');
        self::assertSame(429, $response->getStatusCode());
        self::assertSame('too_many_requests', $this->json($response)['error']['code']);
    }

    #[Test]
    public function limit_per_ip_even_when_the_email_changes(): void
    {
        foreach (range(1, 5) as $i) {
            $this->login(email: "other{$i}@example.com", password: 'wrong', ip: '192.0.2.7');
        }

        self::assertSame(429, $this->login(ip: '192.0.2.7')->getStatusCode());
        self::assertSame(200, $this->login(ip: '192.0.2.8')->getStatusCode());
    }

    #[Test]
    public function the_15_minute_window_frees_up(): void
    {
        foreach (range(1, 5) as $i) {
            $this->login(password: 'wrong', ip: "198.51.100.{$i}");
        }
        $this->clock->advance('+16 minutes');

        self::assertSame(200, $this->login()->getStatusCode());
    }

    #[Test]
    public function logout_revokes_the_token(): void
    {
        $token = $this->json($this->login())['access_token'];
        self::assertSame(200, $this->request('GET', '/auth/me', headers: self::bearer($token))->getStatusCode());

        self::assertSame(204, $this->request('POST', '/auth/logout', headers: self::bearer($token))->getStatusCode());
        self::assertSame(401, $this->request('GET', '/auth/me', headers: self::bearer($token))->getStatusCode());
    }

    #[Test]
    public function me_returns_the_session_user(): void
    {
        $token = $this->json($this->login())['access_token'];

        self::assertSame('equipo@origamibogota.com', $this->json($this->request('GET', '/auth/me', headers: self::bearer($token)))['email']);
    }

    #[Test]
    public function changing_the_password_closes_open_sessions(): void
    {
        $token = $this->json($this->login())['access_token'];

        $this->service(ChangeUserPassword::class)->execute('equipo@origamibogota.com', 'a-new-long-password');

        self::assertSame(401, $this->request('GET', '/auth/me', headers: self::bearer($token))->getStatusCode());
        self::assertSame(200, $this->login(password: 'a-new-long-password')->getStatusCode());
    }

    #[Test]
    public function the_api_does_not_register_users_and_short_passwords_are_rejected(): void
    {
        self::assertSame(404, $this->request('POST', '/register', ['email' => 'x@y.co', 'password' => '123456'])->getStatusCode());

        $this->expectException(ValidationException::class);
        $this->service(CreateUser::class)->execute('new@example.com', 'New', 'short');
    }
}
