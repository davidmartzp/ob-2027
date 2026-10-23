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

final class EnrolmentTest extends FunctionalTestCase
{
    private int $room;
    private int $instructor;
    private int $me;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->room = Schedule::room($this->db());
        $this->instructor = Attendees::create($this->db());
        $this->me = Attendees::create($this->db(), ['age' => 30]);
        $this->token = $this->tokenFor($this->me);
    }

    private function tokenFor(int $attendeeId): string
    {
        return $this->service(TokenService::class)->issue(TokenType::Attendee, $attendeeId)['token'];
    }

    private function enrol(int $eventId, ?int $attendee = null, ?string $token = null): ResponseInterface
    {
        $attendee ??= $this->me;

        return $this->request('POST', "/attendees/{$attendee}/events", ['id_event' => $eventId], self::bearer($token ?? $this->token));
    }

    private function workshopEvent(array $workshop = [], string $start = '10:00:00', string $end = '12:00:00', string $day = 'Saturday'): array
    {
        $workshopId = Workshops::create($this->db(), $this->instructor, $workshop);

        return [$workshopId, Schedule::event($this->db(), $this->room, $workshopId, $start, $end, $day)];
    }

    private function tickets(int $workshopId): int
    {
        return (int) $this->db()->table('workshops')->where('id', $workshopId)->value('tickets');
    }

    #[Test]
    public function enrolling_takes_a_place(): void
    {
        [$workshop, $event] = $this->workshopEvent(['tickets' => 5]);

        $response = $this->enrol($event);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(['enrolled' => [$event]], $this->json($response));
        self::assertSame(4, $this->tickets($workshop));
        self::assertSame([$event], $this->json($this->request('GET', "/attendees/{$this->me}/events", headers: self::bearer($this->token))));
    }

    #[Test]
    public function the_last_place_goes_to_one_attendee_only(): void
    {
        [$workshop, $event] = $this->workshopEvent(['tickets' => 1]);
        $other = Attendees::create($this->db());

        self::assertSame(201, $this->enrol($event)->getStatusCode());
        $second = $this->enrol($event, $other, $this->tokenFor($other));

        self::assertSame(409, $second->getStatusCode());
        self::assertStringContainsString('No quedan cupos', $this->json($second)['error']['message']);
        self::assertSame(0, $this->tickets($workshop));
    }

    #[Test]
    public function enrolling_twice_is_409(): void
    {
        [, $event] = $this->workshopEvent();
        $this->enrol($event);

        self::assertSame(409, $this->enrol($event)->getStatusCode());
    }

    #[Test]
    public function a_time_clash_is_409_naming_the_other_workshop(): void
    {
        [, $first] = $this->workshopEvent(['name' => 'Grulla'], '10:00:00', '12:00:00');
        [, $clash] = $this->workshopEvent(['name' => 'Rana'], '11:00:00', '12:30:00');
        [, $sunday] = $this->workshopEvent(['name' => 'Dragón'], '11:00:00', '12:30:00', 'Sunday');
        $this->enrol($first);

        $response = $this->enrol($clash);

        self::assertSame(409, $response->getStatusCode());
        self::assertStringContainsString('Grulla', $this->json($response)['error']['message']);
        self::assertSame(201, $this->enrol($sunday)->getStatusCode(), 'Same hour on another day is fine');
    }

    #[Test]
    public function children_cannot_enter_adult_workshops(): void
    {
        [$adult, $event] = $this->workshopEvent(['public' => 'A', 'tickets' => 5]);
        [, $kids] = $this->workshopEvent(['public' => 'N'], '14:00:00', '15:00:00');
        $child = Attendees::create($this->db(), ['age' => 11]);

        self::assertSame(422, $this->enrol($event, $child, $this->tokenFor($child))->getStatusCode());
        self::assertSame(5, $this->tickets($adult), 'No place was taken');
        self::assertSame(201, $this->enrol($kids, $child, $this->tokenFor($child))->getStatusCode());
    }

    #[Test]
    public function withdrawn_workshops_and_activities_cannot_be_picked(): void
    {
        [$workshop, $event] = $this->workshopEvent();
        $this->db()->table('workshops')->where('id', $workshop)->update(['status' => 0]);
        $lunch = Schedule::event($this->db(), $this->room, null, '13:00:00', '14:00:00', overrides: ['id_activity' => Schedule::activity($this->db())]);

        self::assertSame(422, $this->enrol($event)->getStatusCode());
        self::assertSame(422, $this->enrol($lunch)->getStatusCode());
    }

    #[Test]
    public function part_one_enrols_in_part_two_as_well(): void
    {
        [$part1, $event1] = $this->workshopEvent(['name' => 'Dragón (1)', 'tickets' => 5], '10:00:00', '12:00:00');
        [$part2, $event2] = $this->workshopEvent(['name' => 'Dragón (2)', 'tickets' => 5, 'part' => 2, 'relation' => $part1], '14:00:00', '16:00:00');

        self::assertSame(['enrolled' => [$event1, $event2]], $this->json($this->enrol($event1)));
        self::assertSame([4, 4], [$this->tickets($part1), $this->tickets($part2)]);
    }

    #[Test]
    public function part_two_enrols_in_part_one_as_well(): void
    {
        [$part1, $event1] = $this->workshopEvent(['tickets' => 5]);
        [, $event2] = $this->workshopEvent(['part' => 2, 'relation' => $part1], '14:00:00', '16:00:00');

        self::assertSame(['enrolled' => [$event1, $event2]], $this->json($this->enrol($event2)));
    }

    #[Test]
    public function if_part_two_is_full_nothing_is_enrolled(): void
    {
        [$part1, $event1] = $this->workshopEvent(['tickets' => 5]);
        $this->workshopEvent(['name' => 'Parte 2 llena', 'tickets' => 0, 'part' => 2, 'relation' => $part1], '14:00:00', '16:00:00');

        $response = $this->enrol($event1);

        self::assertSame(409, $response->getStatusCode());
        self::assertStringContainsString('Parte 2 llena', $this->json($response)['error']['message']);
        self::assertSame(5, $this->tickets($part1));
        self::assertSame(0, $this->db()->table('events_attendees')->count());
    }

    #[Test]
    public function cancelling_one_part_cancels_both_and_gives_places_back(): void
    {
        [$part1, $event1] = $this->workshopEvent(['tickets' => 1]);
        [$part2, $event2] = $this->workshopEvent(['tickets' => 1, 'part' => 2, 'relation' => $part1], '14:00:00', '16:00:00');
        $this->enrol($event1);
        self::assertSame([0, 0], [$this->tickets($part1), $this->tickets($part2)]);

        self::assertSame(204, $this->request('DELETE', "/attendees/{$this->me}/events/{$event2}", headers: self::bearer($this->token))->getStatusCode());

        self::assertSame([1, 1], [$this->tickets($part1), $this->tickets($part2)]);
        self::assertSame([], $this->json($this->request('GET', "/attendees/{$this->me}/events", headers: self::bearer($this->token))));
    }

    #[Test]
    public function cancelling_something_not_enrolled_gives_nothing_back(): void
    {
        [$workshop, $event] = $this->workshopEvent(['tickets' => 3]);

        $this->request('DELETE', "/attendees/{$this->me}/events/{$event}", headers: self::bearer($this->token));

        self::assertSame(3, $this->tickets($workshop));
    }

    #[Test]
    public function only_the_attendee_themself_with_an_attendee_token(): void
    {
        [, $event] = $this->workshopEvent();
        $cms = $this->service(TokenService::class)->issue(TokenType::Cms, 1)['token'];

        self::assertSame(401, $this->request('POST', "/attendees/{$this->me}/events", ['id_event' => $event])->getStatusCode());
        self::assertSame(403, $this->enrol($event, $this->instructor)->getStatusCode(), 'Someone else\'s id');
        self::assertSame(401, $this->enrol($event, token: $cms)->getStatusCode(), 'The CMS does not enrol people');
    }
}
