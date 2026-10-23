<?php

declare(strict_types=1);

namespace Tests\Functional\Attendee;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;

final class RemindCodeTest extends FunctionalTestCase
{
    private function remind(string $email): ResponseInterface
    {
        return $this->request('POST', '/attendees/remind-code', ['email' => $email]);
    }

    #[Test]
    public function a_family_gets_one_email_with_every_number_and_the_response_has_none(): void
    {
        Attendees::create($this->db(), ['name' => 'Marta', 'lastname' => 'Rojas', 'email' => 'familia@ejemplo.com', 'code' => 41]);
        Attendees::create($this->db(), ['name' => 'Tomás', 'lastname' => 'Rojas', 'email' => 'familia@ejemplo.com', 'code' => null, 'age' => 9]);
        Attendees::create($this->db(), ['name' => 'Luis', 'lastname' => 'Rojas', 'email' => 'FAMILIA@ejemplo.com', 'code' => 42]);

        $response = $this->remind('familia@ejemplo.com');

        self::assertSame(202, $response->getStatusCode());
        self::assertStringNotContainsString('41', (string) $response->getBody());
        self::assertSame(['familia@ejemplo.com'], $this->mailer->recipients(), 'Sent right away, to the attendee only');
        $html = (string) $this->mailer->sent[0]->getHtmlBody();
        self::assertStringContainsString('Marta Rojas', $html);
        self::assertStringContainsString('41', $html);
        self::assertStringContainsString('42', $html);
        self::assertStringContainsString('Aún no tiene número asignado', $html);
    }

    #[Test]
    public function an_unregistered_email_gets_the_same_answer_and_no_email(): void
    {
        Attendees::create($this->db(), ['email' => 'inscrita@ejemplo.com', 'code' => 1]);

        $registered = $this->remind('inscrita@ejemplo.com');
        $unregistered = $this->remind('nadie@ejemplo.com');

        self::assertSame((string) $registered->getBody(), (string) $unregistered->getBody());
        self::assertSame(['inscrita@ejemplo.com'], $this->mailer->recipients());
    }

    #[Test]
    public function the_fourth_request_in_an_hour_is_429(): void
    {
        foreach (range(1, 3) as $_) {
            self::assertSame(202, $this->remind('alguien@ejemplo.com')->getStatusCode());
        }

        self::assertSame(429, $this->remind('alguien@ejemplo.com')->getStatusCode());
    }
}
