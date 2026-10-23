<?php

declare(strict_types=1);

namespace Tests\Functional\Workshop;

use App\Application\Auth\TokenService;
use App\Domain\Auth\TokenType;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;
use Tests\Support\Workshops;

final class WorkshopListingTest extends FunctionalTestCase
{
    private int $instructor;
    private string $cmsToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->instructor = Attendees::create($this->db(), ['name' => 'Zoé', 'lastname' => 'Peña', 'email' => 'zoe@ejemplo.com', 'phone' => '3009998877', 'idname' => '1020304050']);
        $this->cmsToken = $this->service(TokenService::class)->issue(TokenType::Cms, 1)['token'];
    }

    private function patch(int $id, array $body): ResponseInterface
    {
        return $this->request('PATCH', "/workshops/{$id}", $body, self::bearer($this->cmsToken));
    }

    #[Test]
    public function the_public_list_has_active_workshops_with_instructor_and_partners_but_no_contact_data(): void
    {
        $active = Workshops::create($this->db(), $this->instructor, ['name' => 'Activo', 'image' => 'taller_7.jpg']);
        Workshops::create($this->db(), $this->instructor, ['name' => 'Oculto', 'status' => 2]);
        Workshops::create($this->db(), $this->instructor, ['name' => 'Retirado', 'status' => 0]);
        Workshops::partner($this->db(), [$active]);
        Workshops::partner($this->db(), [$active], ['name' => 'Inactivo', 'status' => 0]);

        $response = $this->request('GET', '/workshops');

        self::assertSame(200, $response->getStatusCode());
        $list = $this->json($response);
        self::assertSame(['Activo'], array_column($list, 'name'));
        self::assertSame(['id' => $this->instructor, 'name' => 'Zoé', 'lastname' => 'Peña'], $list[0]['instructor']);
        self::assertSame('http://api.test/images/taller_7.jpg', $list[0]['image'], 'Legacy image names keep working');
        self::assertSame(['Papelería El Pliegue'], array_column($list[0]['partners'], 'name'));
        self::assertSame('https://wa.me/573001234567?text=Hola%2C%20vengo%20del%20congreso', $list[0]['partners'][0]['whatsapp_url']);
        $body = (string) $response->getBody();
        foreach (['zoe@ejemplo.com', '3009998877', '1020304050'] as $private) {
            self::assertStringNotContainsString($private, $body);
        }
    }

    #[Test]
    public function the_cms_list_has_every_non_withdrawn_workshop_with_scheduling_fields(): void
    {
        Workshops::create($this->db(), $this->instructor, ['name' => 'B activo']);
        Workshops::create($this->db(), $this->instructor, ['name' => 'A oculto', 'status' => 2]);
        Workshops::create($this->db(), $this->instructor, ['name' => 'Retirado', 'status' => 0]);

        $list = $this->json($this->request('GET', '/cms/workshops', headers: self::bearer($this->cmsToken)));

        self::assertSame(['A oculto', 'B activo'], array_column($list, 'name'));
        self::assertSame(['tickets', 'part', 'relation'], array_values(array_intersect(array_keys($list[0]), ['tickets', 'part', 'relation'])));
        self::assertSame(401, $this->request('GET', '/cms/workshops')->getStatusCode());
    }

    #[Test]
    public function the_cms_sets_capacity_status_and_two_part_link(): void
    {
        $part1 = Workshops::create($this->db(), $this->instructor);
        $part2 = Workshops::create($this->db(), $this->instructor, ['name' => 'Grulla (parte 2)']);

        self::assertSame(20, $this->json($this->patch($part1, ['tickets' => 20]))['tickets']);
        $linked = $this->json($this->patch($part2, ['part' => 2, 'relation' => $part1]));
        self::assertSame([2, $part1], [$linked['part'], $linked['relation']]);
        self::assertSame(2, $this->json($this->patch($part1, ['status' => 2]))['status']);
        self::assertSame(20, $this->json($this->patch($part1, ['status' => 1]))['tickets'], 'Keys not sent stay as they were');
    }

    #[Test]
    public function invalid_patches_are_rejected(): void
    {
        $id = Workshops::create($this->db(), $this->instructor);

        self::assertSame(422, $this->patch($id, ['relation' => $id])->getStatusCode(), 'Cannot continue itself');
        self::assertSame(422, $this->patch($id, ['relation' => 999999])->getStatusCode());
        self::assertSame(422, $this->patch($id, ['tickets' => -1])->getStatusCode());
        self::assertSame(422, $this->patch($id, ['status' => 7])->getStatusCode());
        self::assertSame(404, $this->patch(999999, ['tickets' => 5])->getStatusCode());
    }

    #[Test]
    public function only_the_cms_can_patch(): void
    {
        $id = Workshops::create($this->db(), $this->instructor);

        self::assertSame(401, $this->request('PATCH', "/workshops/{$id}", ['tickets' => 999])->getStatusCode());
    }
}
