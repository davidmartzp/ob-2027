<?php

declare(strict_types=1);

namespace Tests\Functional\Attendee;

use App\Application\Auth\TokenService;
use App\Domain\Attendee\AttendeeStatus;
use App\Domain\Auth\TokenType;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;

final class CmsAttendeesTest extends FunctionalTestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->service(TokenService::class)->issue(TokenType::Cms, 1)['token'];
    }

    private function cms(string $method, string $path, ?array $body = null): ResponseInterface
    {
        return $this->request($method, $path, $body, self::bearer($this->token));
    }

    private function code(int $id): ?int
    {
        $code = $this->db()->table('attendees')->where('id', $id)->value('code');

        return $code === null ? null : (int) $code;
    }

    #[Test]
    public function every_cms_route_requires_a_cms_token(): void
    {
        foreach ([['GET', '/attendees'], ['GET', '/attendees/1'], ['PUT', '/attendees/1'], ['DELETE', '/attendees/1'], ['POST', '/attendees/confirm'], ['POST', '/attendees/codes'], ['POST', '/attendees/codes/regenerate']] as [$method, $path]) {
            self::assertSame(401, $this->request($method, $path)->getStatusCode(), "{$method} {$path}");
        }
    }

    #[Test]
    public function the_list_is_ordered_by_paydate_without_deleted_and_with_booleans(): void
    {
        Attendees::create($this->db(), ['name' => 'Segunda', 'paydate' => '2027-03-02 10:00:00', 'cards' => 1]);
        Attendees::create($this->db(), ['name' => 'Primera', 'paydate' => '2027-03-01 10:00:00']);
        Attendees::create($this->db(), ['name' => 'Borrada', 'status' => AttendeeStatus::Deleted->value]);

        $list = $this->json($this->cms('GET', '/attendees'));

        self::assertSame(['Primera', 'Segunda'], array_column($list, 'name'));
        self::assertTrue($list[1]['cards']);
        self::assertFalse($list[1]['staff']);
        self::assertSame(['id', 'code', 'special_code', 'name', 'lastname', 'age', 'country', 'city', 'email', 'phone', 'companion', 'idname', 'paydate', 'paymethod', 'receipt', 'origamigroup', 'info', 'cardsgroup', 'cards', 'days', 'staff', 'go_to_chiva', 'printCertificate', 'chiva_companions', 'pay_amount', 'status', 'created_at', 'updated_at'], array_keys($list[0]));
    }

    #[Test]
    public function show_and_unknown_attendee(): void
    {
        $id = Attendees::create($this->db(), ['name' => 'Zoé']);

        self::assertSame('Zoé', $this->json($this->cms('GET', "/attendees/{$id}"))['name']);
        self::assertSame(404, $this->cms('GET', '/attendees/999999')->getStatusCode());
        self::assertSame(404, $this->cms('PUT', '/attendees/999999', ['name' => 'X'])->getStatusCode());
    }

    #[Test]
    public function the_cms_edits_including_number_staff_and_amount(): void
    {
        $id = Attendees::create($this->db(), ['name' => 'Zoé']);
        $current = $this->json($this->cms('GET', "/attendees/{$id}"));

        $response = $this->cms('PUT', "/attendees/{$id}", ['lastname' => 'Peña Rojas', 'code' => 77, 'special_code' => 'A1', 'staff' => true, 'pay_amount' => '180000', 'status' => 1] + $current);

        self::assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        self::assertSame('Peña Rojas', $body['lastname']);
        self::assertSame(77, $body['code']);
        self::assertTrue($body['staff']);
        self::assertSame('180000', $body['pay_amount']);
    }

    #[Test]
    public function a_number_already_used_by_someone_else_is_409(): void
    {
        Attendees::create($this->db(), ['code' => 5]);
        $id = Attendees::create($this->db(), ['code' => 6]);
        $current = $this->json($this->cms('GET', "/attendees/{$id}"));

        self::assertSame(409, $this->cms('PUT', "/attendees/{$id}", ['code' => 5] + $current)->getStatusCode());
        self::assertSame(200, $this->cms('PUT', "/attendees/{$id}", ['code' => 6] + $current)->getStatusCode(), 'Keeping its own number is fine');
    }

    #[Test]
    public function edits_are_validated(): void
    {
        $id = Attendees::create($this->db());
        $current = $this->json($this->cms('GET', "/attendees/{$id}"));

        $response = $this->cms('PUT', "/attendees/{$id}", ['email' => 'no-es-correo', 'status' => 9] + $current);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(['email', 'status'], array_keys($this->json($response)['error']['fields']));
    }

    #[Test]
    public function delete_is_soft(): void
    {
        $id = Attendees::create($this->db());

        self::assertSame(204, $this->cms('DELETE', "/attendees/{$id}")->getStatusCode());
        self::assertSame(5, (int) $this->db()->table('attendees')->where('id', $id)->value('status'));
        self::assertSame([], $this->json($this->cms('GET', '/attendees')));
    }

    #[Test]
    public function bulk_confirm_only_touches_pending_ones(): void
    {
        $a = Attendees::create($this->db(), ['status' => AttendeeStatus::Pending->value]);
        $b = Attendees::create($this->db(), ['status' => AttendeeStatus::Pending->value]);
        $c = Attendees::create($this->db(), ['status' => AttendeeStatus::Confirmed->value]);
        $d = Attendees::create($this->db(), ['status' => AttendeeStatus::Deleted->value]);

        self::assertSame(['confirmed' => 2], $this->json($this->cms('POST', '/attendees/confirm', ['ids' => [$a, $b, $c, $d]])));
        self::assertSame(5, (int) $this->db()->table('attendees')->where('id', $d)->value('status'));
    }

    #[Test]
    public function late_registrations_get_numbers_after_the_highest(): void
    {
        Attendees::create($this->db(), ['code' => 120]);
        $may3 = Attendees::create($this->db(), ['code' => null, 'paydate' => '2027-05-03 10:00:00']);
        $may1 = Attendees::create($this->db(), ['code' => null, 'paydate' => '2027-05-01 10:00:00']);
        $child = Attendees::create($this->db(), ['code' => null, 'age' => 10]);
        $guest = Attendees::create($this->db(), ['code' => null, 'paymethod' => 'Invitado(a)']);
        $staff = Attendees::create($this->db(), ['code' => null, 'staff' => 1]);

        self::assertSame(['assigned' => 2], $this->json($this->cms('POST', '/attendees/codes')));
        self::assertSame(121, $this->code($may1));
        self::assertSame(122, $this->code($may3));
        self::assertNull($this->code($child));
        self::assertNull($this->code($guest));
        self::assertNull($this->code($staff));
    }

    #[Test]
    public function regenerate_renumbers_everyone_by_paydate(): void
    {
        $march3 = Attendees::create($this->db(), ['code' => 1, 'paydate' => '2027-03-03 10:00:00']);
        $march1 = Attendees::create($this->db(), ['code' => 2, 'paydate' => '2027-03-01 10:00:00']);
        $march2 = Attendees::create($this->db(), ['code' => 3, 'paydate' => '2027-03-02 10:00:00']);
        $deleted = Attendees::create($this->db(), ['code' => 4, 'status' => AttendeeStatus::Deleted->value]);

        self::assertSame(['assigned' => 3], $this->json($this->cms('POST', '/attendees/codes/regenerate', ['confirmation' => 'regenerate'])));
        self::assertSame([3, 1, 2], [$this->code($march3), $this->code($march1), $this->code($march2)]);
        self::assertNull($this->code($deleted));
    }

    #[Test]
    public function regenerate_without_confirmation_changes_nothing(): void
    {
        $id = Attendees::create($this->db(), ['code' => 9]);

        $response = $this->cms('POST', '/attendees/codes/regenerate', []);

        self::assertSame(422, $response->getStatusCode());
        self::assertArrayHasKey('confirmation', $this->json($response)['error']['fields']);
        self::assertSame(9, $this->code($id));
    }
}
