<?php

declare(strict_types=1);

namespace Tests\Functional\Schedule;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;
use Tests\Support\Schedule;
use Tests\Support\Workshops;

final class PublicScheduleTest extends FunctionalTestCase
{
    private int $saturdayEvent;
    private int $sundayEvent;

    protected function environment(): array
    {
        return ['EVENT_SATURDAY' => '2027-06-05', 'EVENT_SUNDAY' => '2027-06-06'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->insertProgramme();
    }

    private function insertProgramme(): void
    {
        $room = Schedule::room($this->db(), 'Sala 1');
        $instructor = Attendees::create($this->db(), ['name' => 'Zoé', 'lastname' => 'Peña Rojas', 'email' => 'zoe@ejemplo.com']);
        $grulla = Workshops::create($this->db(), $instructor, ['name' => 'Grulla', 'tickets' => 2]);
        $rana = Workshops::create($this->db(), $instructor, ['name' => 'Rana']);
        $hidden = Workshops::create($this->db(), $instructor, ['name' => 'Oculto', 'status' => 2]);
        Workshops::partner($this->db(), [$grulla]);
        $this->saturdayEvent = Schedule::event($this->db(), $room, $grulla, '10:00:00', '12:00:00', 'Saturday');
        $this->sundayEvent = Schedule::event($this->db(), $room, $rana, '09:00:00', '10:00:00', 'Sunday');
        Schedule::event($this->db(), $room, $hidden, '14:00:00', '15:00:00', 'Saturday');
        Schedule::event($this->db(), $room, null, '13:00:00', '14:00:00', 'Saturday', ['id_activity' => Schedule::activity($this->db())]);
    }

    #[Test]
    public function on_saturday_morning_only_saturday_workshops_are_listed(): void
    {
        $this->clock->set('2027-06-05 08:11');

        $response = $this->request('GET', '/events/active');

        self::assertSame(200, $response->getStatusCode());
        $list = $this->json($response);
        self::assertSame([$this->saturdayEvent], array_column(array_column($list, 'event'), 'id'), 'No Sunday, hidden or activity events');
        $workshop = $list[0]['workshop'];
        self::assertSame(['id' => 1, 'name' => 'Zoé Peña'], $workshop['instructor']);
        self::assertSame(2, $workshop['tickets']);
        self::assertSame(['Papelería El Pliegue'], array_column($workshop['partners'], 'name'));
        self::assertSame(['id' => 1, 'name' => 'Sala 1'], $list[0]['room']);
        self::assertStringNotContainsString('zoe@ejemplo.com', (string) $response->getBody());
    }

    #[Test]
    public function on_sunday_only_sunday_workshops_are_listed(): void
    {
        $this->clock->set('2027-06-06 08:11');

        self::assertSame([$this->sundayEvent], array_column(array_column($this->json($this->request('GET', '/events/active')), 'event'), 'id'));
    }

    #[Test]
    public function outside_the_window_it_is_403_with_logout(): void
    {
        $this->clock->set('2027-06-05 07:59');

        $response = $this->request('GET', '/events/active');

        self::assertSame(403, $response->getStatusCode());
        $body = $this->json($response);
        self::assertTrue($body['logout']);
        self::assertSame('enrolment_closed', $body['error']['code']);
    }

    #[Test]
    public function rehearsal_mode_lists_both_days_outside_production(): void
    {
        $this->clock->set('2026-12-01 03:00');
        $this->setUpApp(['TURN_BYPASS' => 'true']);
        $this->insertProgramme();

        self::assertSame([$this->saturdayEvent, $this->sundayEvent], array_column(array_column($this->json($this->request('GET', '/events/active')), 'event'), 'id'));
    }

    #[Test]
    public function the_public_schedule_is_available_any_time(): void
    {
        $this->clock->set('2026-12-01 03:00');

        $list = $this->json($this->request('GET', '/events/schedule'));

        self::assertSame(['Grulla', 'Rana'], array_column($list, 'name'));
        self::assertSame(['id' => $this->saturdayEvent, 'day' => 'Saturday', 'start_time' => '10:00:00', 'end_time' => '12:00:00', 'room' => ['id' => 1, 'name' => 'Sala 1']], $list[0]['event']);
    }

    #[Test]
    public function an_empty_schedule_is_an_empty_list(): void
    {
        $this->setUpApp();

        self::assertSame([], $this->json($this->request('GET', '/events/schedule')));
    }
}
