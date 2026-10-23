<?php

declare(strict_types=1);

namespace Tests\Functional\Workshop;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;
use Tests\Support\Workshops;

/** Instructors manage their workshops without signing in, as in the legacy site. */
final class InstructorWorkshopsTest extends FunctionalTestCase
{
    private int $me;
    private int $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->me = Attendees::create($this->db(), ['name' => 'Zoé', 'lastname' => 'Peña', 'email' => 'familia@ejemplo.com']);
        $this->other = Attendees::create($this->db(), ['name' => 'Luis']);
    }

    private static function workshop(array $overrides = []): array
    {
        return $overrides + ['name' => 'Rana saltarina', 'level' => 'P', 'public' => 'N', 'duration' => 45, 'observations' => 'Para niños'];
    }

    #[Test]
    public function an_attendee_proposes_two_workshops_which_are_active(): void
    {
        $response = $this->request('POST', "/attendees/{$this->me}/workshops", ['workshops' => [self::workshop(), self::workshop(['name' => 'Dragón', 'level' => 'A', 'public' => 'A', 'duration' => 180])]]);

        self::assertSame(201, $response->getStatusCode());
        $created = $this->json($response);
        self::assertSame(['Rana saltarina', 'Dragón'], array_column($created, 'name'));
        self::assertSame([1, 1], array_column($created, 'status'));
        self::assertSame(2, $this->db()->table('workshops')->where('id_attendee', $this->me)->count());
    }

    #[Test]
    public function one_invalid_workshop_saves_none(): void
    {
        $response = $this->request('POST', "/attendees/{$this->me}/workshops", ['workshops' => [self::workshop(), self::workshop(['level' => 'X', 'duration' => 5])]]);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(['workshops.1.level', 'workshops.1.duration'], array_keys($this->json($response)['error']['fields']));
        self::assertSame(0, $this->db()->table('workshops')->count());
    }

    #[Test]
    public function proposing_for_an_unknown_attendee_is_404(): void
    {
        self::assertSame(404, $this->request('POST', '/attendees/999999/workshops', ['workshops' => [self::workshop()]])->getStatusCode());
    }

    #[Test]
    public function my_list_shows_active_and_hidden_but_not_withdrawn(): void
    {
        Workshops::create($this->db(), $this->me, ['name' => 'Activo']);
        Workshops::create($this->db(), $this->me, ['name' => 'Oculto', 'status' => 2]);
        Workshops::create($this->db(), $this->me, ['name' => 'Retirado', 'status' => 0]);
        Workshops::create($this->db(), $this->other, ['name' => 'Ajeno']);

        $list = $this->json($this->request('GET', "/attendees/{$this->me}/workshops"));

        self::assertSame(['Activo', 'Oculto'], array_column($list, 'name'));
    }

    #[Test]
    public function workshops_are_found_by_email_for_every_family_member(): void
    {
        $son = Attendees::create($this->db(), ['name' => 'Tomás', 'lastname' => 'Peña', 'email' => 'Familia@Ejemplo.com']);
        Workshops::create($this->db(), $this->me, ['name' => 'Grulla']);
        Workshops::create($this->db(), $this->other, ['name' => 'Ajeno']);

        $response = $this->request('POST', '/workshops/by-email', ['email' => 'familia@ejemplo.com']);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        self::assertSame([[$this->me, 'Zoé Peña', ['Grulla']], [$son, 'Tomás Peña', []]], array_map(
            static fn (array $a): array => [$a['id'], $a['name'], array_column($a['workshops'], 'name')],
            $body,
        ));
        self::assertSame([], $this->json($this->request('POST', '/workshops/by-email', ['email' => 'nadie@ejemplo.com'])));
        self::assertSame(422, $this->request('POST', '/workshops/by-email', ['email' => 'no-es-correo'])->getStatusCode());
    }

    #[Test]
    public function a_workshop_is_edited_and_withdrawn(): void
    {
        $mine = Workshops::create($this->db(), $this->me);

        self::assertSame('I', $this->json($this->request('PUT', "/workshops/{$mine}", self::workshop(['level' => 'I'])))['level']);
        self::assertSame(204, $this->request('DELETE', "/workshops/{$mine}")->getStatusCode());
        self::assertSame(0, (int) $this->db()->table('workshops')->where('id', $mine)->value('status'));
        self::assertSame(404, $this->request('PUT', "/workshops/{$mine}", self::workshop())->getStatusCode(), 'A withdrawn workshop is gone');
    }
}
