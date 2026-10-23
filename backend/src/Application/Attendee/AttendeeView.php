<?php

declare(strict_types=1);

namespace App\Application\Attendee;

use App\Domain\Attendee\Attendee;

/** JSON shape of an attendee for the CMS (the fields the legacy CMS already reads). */
final class AttendeeView
{
    /** @return array<string, mixed> */
    public static function toArray(Attendee $a): array
    {
        return [
            'id' => $a->id,
            'code' => $a->code,
            'special_code' => $a->specialCode,
            'name' => $a->name,
            'lastname' => $a->lastname,
            'age' => $a->age,
            'country' => $a->country,
            'city' => $a->city,
            'email' => $a->email,
            'phone' => $a->phone,
            'companion' => $a->companion,
            'idname' => $a->idname,
            'paydate' => $a->paydate,
            'paymethod' => $a->paymethod,
            'receipt' => $a->receipt,
            'origamigroup' => $a->origamigroup,
            'info' => $a->info,
            'cardsgroup' => $a->cardsgroup,
            'cards' => $a->cards,
            'days' => $a->days,
            'staff' => $a->staff,
            'go_to_chiva' => $a->goToChiva,
            'printCertificate' => $a->printCertificate,
            'chiva_companions' => $a->chivaCompanions,
            'pay_amount' => $a->payAmount,
            'status' => $a->status->value,
            'created_at' => $a->createdAt,
            'updated_at' => $a->updatedAt,
        ];
    }
}
