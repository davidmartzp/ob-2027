<?php

declare(strict_types=1);

namespace App\Application\Mail;

use App\Domain\Attendee\Attendee;

/**
 * Template variables about an attendee: the same set the legacy EmailTriggerService
 * replaced (`{{ $name }}`, `{{ $code }}`…), so the ported templates work unchanged.
 * Missing values are empty strings, as before.
 */
final class EmailVariables
{
    /** @return array<string, string> */
    public static function for(Attendee $attendee): array
    {
        return [
            'name' => $attendee->name,
            'fullname' => $attendee->fullName(),
            'email' => $attendee->email,
            'code' => $attendee->displayCode() ?? '',
            'special_code' => $attendee->specialCode ?? '',
            'phone' => $attendee->phone ?? '',
            'country' => $attendee->country ?? '',
            'city' => $attendee->city ?? '',
            'age' => (string) $attendee->age,
            'paymethod' => $attendee->paymethod ?? '',
            'paydate' => $attendee->paydate ?? '',
            'receipt' => $attendee->receipt ?? '',
            'companion' => $attendee->companion ?? '',
            'days' => $attendee->days ?? '',
        ];
    }
}
