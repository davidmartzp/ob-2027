<?php

declare(strict_types=1);

namespace Tests\Functional\Workshop;

use App\Application\Auth\TokenService;
use App\Domain\Auth\TokenType;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;
use Tests\Support\Workshops;

final class InstructorWorkshopsTest extends FunctionalTestCase
{
    private int $me;
    private int $other;
    private string $myToken;
    private string $cmsToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->me = Attendees::create($this->db(), ['name' => 'Zoé']);
        $this->other = Attendees::create($this->db(), ['name' => 'Luis']);
        $tokens = $this->service(TokenService::class);
        $this->myToken = $tokens->issue(TokenType::Attendee, $this->me)['token'];
        $this->cmsToken = $tokens->issue(TokenType::Cms, 1)['token'];
    }

    private static function workshop(array $overrides = []): array
    {
        return $overrides + ['name' => 'Rana saltarina', 'level' => 'P', 'public' => 'N', 'duration' => 45, 'observations' => 'Para niños'];
    }

    #[Test]
    public function an_attendee_proposes_two_workshops_which_are_active(): void
    {
        $response = $this->request('POST', "/attendees/{$this->me}/workshops", ['workshops' => [self::workshop(), self::workshop(['name' => 'Dragón', 'level' => 'A', 'public' => 'A', 'duration' => 180])]], self::bearer($this->myToken));

        self::assertSame(201, $response->getStatusCode());
        $created = $this->json($response);
        self::assertSame(['Rana saltarina', 'Dragón'], array_column($created, 'name'));
        self::assertSame([1, 1], array_column($created, 'status'));
        self::assertSame(2, $this->db()->table('workshops')->where('id_attendee', $this->me)->count());
    }

    #[Test]
    public function one_invalid_workshop_saves_none(): void
    {
        $response = $this->request('POST', "/attendees/{$this->me}/workshops", ['workshops' => [self::workshop(), self::workshop(['level' => 'X', 'duration' => 5])]], self::bearer($this->myToken));

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(['workshops.1.level', 'workshops.1.duration'], array_keys($this->json($response)['error']['fields']));
        self::assertSame(0, $this->db()->table('workshops')->count());
    }

    #[Test]
    public function only_the_attendee_themself_can_propose_or_list(): void
    {
        self::assertSame(403, $this->request('POST', "/attendees/{$this->other}/workshops", ['workshops' => [self::workshop()]], self::bearer($this->myToken))->getStatusCode());
        self::assertSame(403, $this->request('GET', "/attendees/{$this->other}/workshops", headers: self::bearer($this->myToken))->getStatusCode());
        self::assertSame(401, $this->request('GET', "/attendees/{$this->me}/workshops")->getStatusCode());
    }

    #[Test]
    public function my_list_shows_active_and_hidden_but_not_withdrawn(): void
    {
        Workshops::create($this->db(), $this->me, ['name' => 'Activo']);
        Workshops::create($this->db(), $this->me, ['name' => 'Oculto', 'status' => 2]);
        Workshops::create($this->db(), $this->me, ['name' => 'Retirado', 'status' => 0]);
        Workshops::create($this->db(), $this->other, ['name' => 'Ajeno']);

        $list = $this->json($this->request('GET', "/attendees/{$this->me}/workshops", headers: self::bearer($this->myToken)));

        self::assertSame(['Activo', 'Oculto'], array_column($list, 'name'));
    }

    #[Test]
    public function the_author_edits_and_withdraws_but_another_attendee_cannot(): void
    {
        $mine = Workshops::create($this->db(), $this->me);
        $otherToken = $this->service(TokenService::class)->issue(TokenType::Attendee, $this->other)['token'];

        self::assertSame(403, $this->request('PUT', "/workshops/{$mine}", self::workshop(), self::bearer($otherToken))->getStatusCode());
        self::assertSame(403, $this->request('DELETE', "/workshops/{$mine}", headers: self::bearer($otherToken))->getStatusCode());

        $edited = $this->json($this->request('PUT', "/workshops/{$mine}", self::workshop(['level' => 'I']), self::bearer($this->myToken)));
        self::assertSame('I', $edited['level']);

        self::assertSame(204, $this->request('DELETE', "/workshops/{$mine}", headers: self::bearer($this->myToken))->getStatusCode());
        self::assertSame(0, (int) $this->db()->table('workshops')->where('id', $mine)->value('status'));
        self::assertSame(404, $this->request('PUT', "/workshops/{$mine}", self::workshop(), self::bearer($this->myToken))->getStatusCode(), 'A withdrawn workshop is gone');
    }

    #[Test]
    public function the_cms_can_edit_any_workshop(): void
    {
        $mine = Workshops::create($this->db(), $this->me);

        self::assertSame(200, $this->request('PUT', "/workshops/{$mine}", self::workshop(['name' => 'Corregido por el equipo']), self::bearer($this->cmsToken))->getStatusCode());
    }
}
