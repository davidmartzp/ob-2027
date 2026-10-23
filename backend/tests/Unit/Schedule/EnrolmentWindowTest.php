<?php

declare(strict_types=1);

namespace Tests\Unit\Schedule;

use App\Domain\Attendee\TurnSettings;
use App\Domain\Schedule\Day;
use App\Domain\Schedule\EnrolmentClosedException;
use App\Domain\Schedule\EnrolmentWindow;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Congress on Saturday 2027-06-05 and Sunday 2027-06-06, 08:00–19:00. */
final class EnrolmentWindowTest extends TestCase
{
    private static function window(bool $bypass = false): EnrolmentWindow
    {
        return new EnrolmentWindow(new TurnSettings('2027-06-05', '2027-06-06', bypass: $bypass));
    }

    private static function at(string $moment): DateTimeImmutable
    {
        return new DateTimeImmutable($moment, new DateTimeZone('America/Bogota'));
    }

    /** @return iterable<string, array{string, list<Day>}> */
    public static function open(): iterable
    {
        yield 'Saturday 08:00' => ['2027-06-05 08:00', [Day::Saturday]];
        yield 'Saturday 18:59' => ['2027-06-05 18:59', [Day::Saturday]];
        yield 'Sunday 08:11' => ['2027-06-06 08:11', [Day::Sunday]];
        yield 'Sunday 21:00 (no closing time on Sunday)' => ['2027-06-06 21:00', [Day::Sunday]];
    }

    /** @param list<Day> $days */
    #[Test]
    #[DataProvider('open')]
    public function open_moments_show_only_that_days_events(string $moment, array $days): void
    {
        self::assertSame($days, self::window()->openDays(self::at($moment)));
    }

    /** @return iterable<string, array{string, string}> */
    public static function closed(): iterable
    {
        yield 'Saturday 07:59' => ['2027-06-05 07:59', 'entre las 08:00 y las 19:00'];
        yield 'Saturday 19:01' => ['2027-06-05 19:01', 'entre las 08:00 y las 19:00'];
        yield 'Sunday 07:59' => ['2027-06-06 07:59', 'a partir de las 08:00'];
        yield 'a Wednesday before' => ['2027-06-02 10:00', 'abre el sábado'];
        yield 'after the congress' => ['2027-06-07 10:00', 'ya terminó'];
    }

    #[Test]
    #[DataProvider('closed')]
    public function closed_moments_throw_with_the_reason(string $moment, string $message): void
    {
        $this->expectException(EnrolmentClosedException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote($message, '/') . '/');

        self::window()->openDays(self::at($moment));
    }

    #[Test]
    public function rehearsal_mode_opens_both_days_any_time(): void
    {
        self::assertSame([Day::Saturday, Day::Sunday], self::window(bypass: true)->openDays(self::at('2026-12-01 03:00')));
    }
}
