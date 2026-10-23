<?php

declare(strict_types=1);

namespace App\Domain\Workshop;

/** Values of the legacy `workshops.status` column. */
enum WorkshopStatus: int
{
    /** Withdrawn: hidden everywhere, cannot be scheduled nor take enrolments. */
    case Withdrawn = 0;
    /** Published and schedulable. */
    case Active = 1;
    /** Visible to its author only. */
    case Hidden = 2;
}
