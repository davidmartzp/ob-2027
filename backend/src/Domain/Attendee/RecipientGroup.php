<?php

declare(strict_types=1);

namespace App\Domain\Attendee;

/** Who receives a bulk email (the filters the legacy send* endpoints used). */
enum RecipientGroup
{
    /** Not deleted (status != 5). */
    case Active;
    /** Payment confirmed (status = 1). */
    case Confirmed;
    /** Not deleted and with an assigned participant number. */
    case WithCode;
}
