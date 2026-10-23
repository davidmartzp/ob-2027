<?php

declare(strict_types=1);

namespace App\Domain\Attendee;

/** Values inherited from the legacy `assistants.status` column. */
enum AttendeeStatus: int
{
    case Pending = 0;
    case Confirmed = 1;
    case Deleted = 5;
}
