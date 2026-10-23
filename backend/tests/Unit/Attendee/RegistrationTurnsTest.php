<?php

declare(strict_types=1);

namespace Tests\Unit\Attendee;

use App\Domain\Attendee\Attendee;
use App\Domain\Attendee\AttendeeStatus;
use App\Domain\Attendee\RegistrationTurns;
use App\Domain\Attendee\TurnSettings;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Saturday 2027-06-05 and Sunday 2027-06-06; groups of 25 every 10 minutes from 08:00 to 19:00. */
final class RegistrationTurnsTest extends TestCase
{
    private function turns(bool $bypass = false): RegistrationTurns
    {
        return new RegistrationTurns(new TurnSettings('2027-06-05', '2027-06-06', bypass: $bypass));
    }

    private function attendee(?int $code = 1, string $days = 'T', ?string $specialCode = null): Attendee
    {
        return new Attendee(1, 'Zoé', 'Peña', 'z@x.co', 30, AttendeeStatus::Confirmed, code: $code, specialCode: $specialCode, days: $days);
    }

    private static function at(string $moment): DateTimeImmutable
    {
        return new DateTimeImmutable($moment, new DateTimeZone('America/Bogota'));
    }

    #[Test]
    public function group_3_waits_until_08_20(): void
    {
        $early = $this->turns()->decide($this->attendee(60), self::at('2027-06-05 08:05'));
        $onTime = $this->turns()->decide($this->attendee(60), self::at('2027-06-05 08:20'));

        self::assertFalse($early->open);
        self::assertStringContainsString('08:20', $early->message);
        self::assertTrue($onTime->open);
    }

    #[Test]
    public function the_first_group_enters_at_the_start(): void
    {
        self::assertTrue($this->turns()->decide($this->attendee(25), self::at('2027-06-05 08:00'))->open);
        self::assertFalse($this->turns()->decide($this->attendee(26), self::at('2027-06-05 08:09'))->open);
    }

    #[Test]
    public function before_the_start_of_the_day(): void
    {
        $decision = $this->turns()->decide($this->attendee(1), self::at('2027-06-05 07:59'));

        self::assertFalse($decision->open);
        self::assertStringContainsString('inician a las 08:00', $decision->message);
    }

    #[Test]
    public function a_day_the_attendee_did_not_choose(): void
    {
        $decision = $this->turns()->decide($this->attendee(1, days: 'D'), self::at('2027-06-05 10:00'));

        self::assertFalse($decision->open);
        self::assertStringContainsString('el domingo', $decision->message);
        self::assertTrue($this->turns()->decide($this->attendee(1, days: 'D'), self::at('2027-06-06 10:00'))->open);
    }

    #[Test]
    public function after_closing_on_saturday_points_to_sunday(): void
    {
        $decision = $this->turns()->decide($this->attendee(60), self::at('2027-06-05 19:30'));

        self::assertFalse($decision->open);
        self::assertStringContainsString('08:20 el domingo', $decision->message);
    }

    #[Test]
    public function after_closing_on_the_last_day(): void
    {
        $decision = $this->turns()->decide($this->attendee(1), self::at('2027-06-06 19:30'));

        self::assertFalse($decision->open);
        self::assertStringContainsString('no hay más días', $decision->message);
    }

    #[Test]
    public function a_group_whose_turn_would_start_after_closing(): void
    {
        // Number 2000 → group 80 → 08:00 + 790 min = 21:10, after 19:00.
        $decision = $this->turns()->decide($this->attendee(2000), self::at('2027-06-05 18:00'));

        self::assertFalse($decision->open);
        self::assertStringContainsString('ya ha concluido', $decision->message);
    }

    #[Test]
    public function days_before_the_congress(): void
    {
        $decision = $this->turns()->decide($this->attendee(60), self::at('2027-05-20 10:00'));

        self::assertFalse($decision->open);
        self::assertStringContainsString('08:20 el sábado', $decision->message);
    }

    #[Test]
    public function after_the_congress(): void
    {
        self::assertStringContainsString('ya terminaron', $this->turns()->decide($this->attendee(1), self::at('2027-06-10 10:00'))->message);
    }

    #[Test]
    public function a_special_code_uses_its_digits(): void
    {
        $decision = $this->turns()->decide($this->attendee(null, specialCode: 'A60'), self::at('2027-06-05 08:05'));

        self::assertStringContainsString('08:20', $decision->message);
    }

    #[Test]
    public function rehearsal_mode_opens_any_time(): void
    {
        self::assertTrue($this->turns(bypass: true)->decide($this->attendee(2000), self::at('2026-12-01 03:00'))->open);
    }
}
