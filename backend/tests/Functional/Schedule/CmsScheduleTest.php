<?php

declare(strict_types=1);

namespace Tests\Functional\Schedule;

use App\Application\Auth\TokenService;
use App\Domain\Auth\TokenType;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;
use Tests\Support\Schedule;
use Tests\Support\Workshops;

final class CmsScheduleTest extends FunctionalTestCase
{
    private string $cms;
    private int $room;
    private int $workshop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cms = $this->service(TokenService::class)->issue(TokenType::Cms, 1)['token'];
        $this->room = Schedule::room($this->db());
        $this->workshop = Workshops::create($this->db(), Attendees::create($this->db(), ['name' => 'Zoé', 'lastname' => 'Peña Rojas']));
    }

    private function cms(string $method, string $path, ?array $body = null): ResponseInterface
    {
        return $this->request($method, $path, $body, self::bearer($this->cms));
    }

    private function create(array $overrides = []): ResponseInterface
    {
        return $this->cms('POST', '/events', $overrides + ['id_room' => $this->room, 'day' => 'saturday', 'start_time' => '10:00', 'end_time' => '12:00', 'id_workshop' => $this->workshop]);
    }

    #[Test]
    public function rooms_and_activities_for_the_cms(): void
    {
        Schedule::activity($this->db());

        self::assertSame([['id' => $this->room, 'name' => 'Sala 1']], $this->json($this->cms('GET', '/rooms')));
        self::assertSame('Almuerzo', $this->json($this->cms('GET', '/activities'))[0]['name']);
        self::assertSame(401, $this->request('GET', '/rooms')->getStatusCode());
    }

    #[Test]
    public function an_event_is_created_with_normalised_day_and_times(): void
    {
        $response = $this->create();

        self::assertSame(201, $response->getStatusCode());
        $event = $this->json($response);
        self::assertSame(['Saturday', '10:00:00', '12:00:00'], [$event['day'], $event['start_time'], $event['end_time']]);
        self::assertSame([$event], $this->json($this->cms('GET', '/events')));
    }

    #[Test]
    public function a_workshop_and_an_activity_at_once_is_422(): void
    {
        $response = $this->create(['id_activity' => Schedule::activity($this->db())]);

        self::assertSame(422, $response->getStatusCode());
        self::assertArrayHasKey('id_workshop', $this->json($response)['error']['fields']);
        self::assertSame(422, $this->create(['id_workshop' => null])->getStatusCode(), 'Neither is also wrong');
    }

    #[Test]
    public function invalid_events_are_422(): void
    {
        self::assertSame(422, $this->create(['end_time' => '09:00'])->getStatusCode(), 'Ends before it starts');
        self::assertSame(422, $this->create(['day' => 'Monday'])->getStatusCode());
        self::assertSame(422, $this->create(['id_room' => 999])->getStatusCode());
        $withdrawn = Workshops::create($this->db(), 1, ['status' => 0]);
        self::assertSame(422, $this->create(['id_workshop' => $withdrawn])->getStatusCode(), 'A withdrawn workshop cannot be scheduled');
    }

    #[Test]
    public function overlapping_in_the_same_room_and_day_is_409(): void
    {
        $this->create();

        self::assertSame(409, $this->create(['start_time' => '11:00', 'end_time' => '13:00'])->getStatusCode());
        self::assertSame(201, $this->create(['start_time' => '12:00', 'end_time' => '13:00'])->getStatusCode(), 'Back to back is fine');
        self::assertSame(201, $this->create(['day' => 'Sunday'])->getStatusCode(), 'Another day is fine');
        self::assertSame(201, $this->create(['id_room' => Schedule::room($this->db(), 'Sala 2')])->getStatusCode(), 'Another room is fine');
    }

    #[Test]
    public function deleting_an_event_removes_its_enrolments_and_gives_the_places_back(): void
    {
        $this->db()->table('workshops')->where('id', $this->workshop)->update(['tickets' => 3]);
        $event = Schedule::event($this->db(), $this->room, $this->workshop);
        foreach (range(1, 5) as $_) {
            $this->db()->table('events_attendees')->insert(['id_attendee' => Attendees::create($this->db()), 'id_event' => $event]);
        }

        self::assertSame(204, $this->cms('DELETE', "/events/{$event}")->getStatusCode());
        self::assertSame(0, $this->db()->table('events_attendees')->count());
        self::assertSame(8, (int) $this->db()->table('workshops')->where('id', $this->workshop)->value('tickets'));
        self::assertSame(404, $this->cms('DELETE', "/events/{$event}")->getStatusCode());
    }

    #[Test]
    public function attendance_list_is_ordered_by_participant_number(): void
    {
        $event = Schedule::event($this->db(), $this->room, $this->workshop);
        foreach ([12, 3, 7] as $code) {
            $this->db()->table('events_attendees')->insert(['id_attendee' => Attendees::create($this->db(), ['code' => $code, 'lastname' => 'Pérez Gómez']), 'id_event' => $event]);
        }

        $list = $this->json($this->cms('GET', "/events/{$event}/attendees"));

        self::assertSame([3, 7, 12], array_column($list, 'code'));
        self::assertStringEndsWith(' Pérez', $list[0]['name'], 'First surname only');
    }

    #[Test]
    public function workshop_events_for_printing_lists(): void
    {
        $event = Schedule::event($this->db(), $this->room, $this->workshop);
        Schedule::event($this->db(), $this->room, null, '13:00:00', '14:00:00', overrides: ['id_activity' => Schedule::activity($this->db())]);

        self::assertSame([['id_event' => $event, 'workshop_name' => 'Grulla clásica', 'instructor' => ['id' => 1, 'name' => 'Zoé Peña']]], $this->json($this->cms('GET', '/events/workshops')));
    }
}
