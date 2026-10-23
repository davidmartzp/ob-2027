<?php

declare(strict_types=1);

namespace Tests\Functional\Platform;

use PHPUnit\Framework\Attributes\Test;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\Support\FunctionalTestCase;

final class HealthTest extends FunctionalTestCase
{
    #[Test]
    public function healthy_with_empty_mail_queue(): void
    {
        $response = $this->request('GET', '/health');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['status' => 'ok', 'mail_queue_minutes' => null], $this->json($response));
    }

    #[Test]
    public function reports_the_age_of_the_oldest_pending_email(): void
    {
        $this->clock->set('2027-06-05 10:00');
        $base = ['type' => 'welcome', 'email' => 'a@b.co', 'subject' => 'Hola', 'html' => '<p>Hola</p>', 'status' => 'pending'];
        $this->db()->table('email_outbox')->insert([
            $base + ['available_at' => '2027-06-05 09:15:00'],
            $base + ['available_at' => '2027-06-05 09:55:00'],
            ['status' => 'sent', 'available_at' => '2027-06-05 05:00:00'] + $base,
            $base + ['available_at' => '2027-06-05 11:00:00'], // not due yet
        ]);

        self::assertSame(45, $this->json($this->request('GET', '/health'))['mail_queue_minutes']);
    }

    #[Test]
    public function without_database_it_is_503(): void
    {
        $log = ini_set('error_log', '/dev/null');
        $app = self::bootApp(['DB_DATABASE' => '/path/that/does/not/exist/db.sqlite']);
        $response = $app->handle((new ServerRequestFactory())->createServerRequest('GET', '/health'));
        ini_set('error_log', (string) $log);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('database_unavailable', json_decode((string) $response->getBody(), true)['status']);
    }
}
