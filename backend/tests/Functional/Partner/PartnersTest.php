<?php

declare(strict_types=1);

namespace Tests\Functional\Partner;

use App\Application\Auth\TokenService;
use App\Domain\Auth\TokenType;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;
use Tests\Support\Workshops;

final class PartnersTest extends FunctionalTestCase
{
    private string $cms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cms = $this->service(TokenService::class)->issue(TokenType::Cms, 1)['token'];
    }

    private function cms(string $method, string $path, ?array $body = null): ResponseInterface
    {
        return $this->request($method, $path, $body, self::bearer($this->cms));
    }

    private static function partner(array $overrides = []): array
    {
        return $overrides + ['name' => 'Papelería El Pliegue', 'whatsapp' => '573001234567', 'message' => 'Hola, vengo del congreso'];
    }

    #[Test]
    public function the_cms_creates_a_partner_with_its_whatsapp_link(): void
    {
        $response = $this->cms('POST', '/partners', self::partner());

        self::assertSame(201, $response->getStatusCode());
        $body = $this->json($response);
        self::assertSame('https://wa.me/573001234567?text=Hola%2C%20vengo%20del%20congreso', $body['whatsapp_url']);
        self::assertNull($body['logo']);
        self::assertSame([$body], $this->json($this->request('GET', '/partners')), 'Public list');
    }

    #[Test]
    public function the_whatsapp_number_must_be_digits_only(): void
    {
        $response = $this->cms('POST', '/partners', self::partner(['whatsapp' => '+57 300-123']));

        self::assertSame(422, $response->getStatusCode());
        self::assertArrayHasKey('whatsapp', $this->json($response)['error']['fields']);
        self::assertSame(422, $this->cms('POST', '/partners', self::partner(['whatsapp' => '1234567']))->getStatusCode(), 'Too short');
    }

    #[Test]
    public function editing_and_unknown_partner(): void
    {
        $id = $this->json($this->cms('POST', '/partners', self::partner()))['id'];

        self::assertSame('Nuevo nombre', $this->json($this->cms('PUT', "/partners/{$id}", self::partner(['name' => 'Nuevo nombre'])))['name']);
        self::assertSame(404, $this->cms('PUT', '/partners/999999', self::partner())->getStatusCode());
    }

    #[Test]
    public function deactivating_hides_it_everywhere_but_keeps_the_links(): void
    {
        $instructor = Attendees::create($this->db());
        $a = Workshops::create($this->db(), $instructor);
        $b = Workshops::create($this->db(), $instructor);
        $partner = Workshops::partner($this->db(), [$a, $b]);

        self::assertSame(204, $this->cms('DELETE', "/partners/{$partner}")->getStatusCode());

        self::assertSame([], $this->json($this->request('GET', '/partners')));
        self::assertSame([], $this->json($this->request('GET', "/workshops/{$a}/partners")));
        self::assertSame([[], []], array_column($this->json($this->request('GET', '/workshops')), 'partners'));
        self::assertSame(2, $this->db()->table('workshop_partner')->where('id_partner', $partner)->count());
    }

    #[Test]
    public function management_requires_the_cms(): void
    {
        $attendee = $this->service(TokenService::class)->issue(TokenType::Attendee, 1)['token'];

        self::assertSame(401, $this->request('POST', '/partners', self::partner())->getStatusCode());
        self::assertSame(401, $this->request('POST', '/partners', self::partner(), self::bearer($attendee))->getStatusCode());
        self::assertSame(401, $this->request('DELETE', '/partners/1', headers: self::bearer($attendee))->getStatusCode());
    }

    #[Test]
    public function the_logo_follows_the_image_rules(): void
    {
        $id = $this->json($this->cms('POST', '/partners', self::partner()))['id'];

        $svg = $this->upload("/partners/{$id}/logo", 'logo', '<svg xmlns="http://www.w3.org/2000/svg"/>', 'logo.svg', self::bearer($this->cms));
        self::assertSame(422, $svg->getStatusCode());
        self::assertArrayHasKey('logo', $this->json($svg)['error']['fields']);

        $png = $this->upload("/partners/{$id}/logo", 'logo', Workshops::png(), 'logo.png', self::bearer($this->cms));
        self::assertSame(200, $png->getStatusCode());
        self::assertMatchesRegularExpression('#^http://api\.test/images/partner-\d+-[a-f0-9]{16}\.png$#', $this->json($png)['logo']);
        self::assertSame($this->json($png)['logo'], $this->json($this->request('GET', '/partners'))[0]['logo']);
    }

    #[Test]
    public function the_author_replaces_the_partners_of_a_workshop(): void
    {
        $author = Attendees::create($this->db());
        $workshop = Workshops::create($this->db(), $author);
        $p1 = Workshops::partner($this->db(), [$workshop], ['name' => 'Uno']);
        $p2 = Workshops::partner($this->db(), [$workshop], ['name' => 'Dos']);
        $p3 = Workshops::partner($this->db(), [], ['name' => 'Tres']);
        $token = $this->service(TokenService::class)->issue(TokenType::Attendee, $author)['token'];

        $response = $this->request('PUT', "/workshops/{$workshop}/partners", ['partner_ids' => [$p2, $p3]], self::bearer($token));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['Dos', 'Tres'], array_column($this->json($response), 'name'));
        self::assertSame([$p2, $p3], $this->db()->table('workshop_partner')->where('id_workshop', $workshop)->orderBy('id_partner')->pluck('id_partner')->map(fn ($id) => (int) $id)->all());
        self::assertNotContains($p1, array_column($this->json($this->request('GET', "/workshops/{$workshop}/partners")), 'id'));
    }

    #[Test]
    public function replacing_needs_permission_and_existing_partners(): void
    {
        $author = Attendees::create($this->db());
        $workshop = Workshops::create($this->db(), $author);
        $p1 = Workshops::partner($this->db(), [$workshop]);
        $stranger = $this->service(TokenService::class)->issue(TokenType::Attendee, 999)['token'];

        self::assertSame(401, $this->request('PUT', "/workshops/{$workshop}/partners", ['partner_ids' => []])->getStatusCode());
        self::assertSame(403, $this->request('PUT', "/workshops/{$workshop}/partners", ['partner_ids' => []], self::bearer($stranger))->getStatusCode());
        self::assertSame(422, $this->cms('PUT', "/workshops/{$workshop}/partners", ['partner_ids' => [$p1, 999999]])->getStatusCode());
        self::assertSame(1, $this->db()->table('workshop_partner')->where('id_workshop', $workshop)->count(), 'Nothing changed');
        self::assertSame([], $this->json($this->cms('PUT', "/workshops/{$workshop}/partners", ['partner_ids' => []])), 'An empty list clears them');
    }
}
