<?php

declare(strict_types=1);

namespace App\Domain\Auth;

/** Who a token belongs to: a CMS user or a congress attendee. */
enum TokenType: string
{
    case Cms = 'cms';
    case Attendee = 'attendee';
}
