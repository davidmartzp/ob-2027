<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

use App\Domain\Shared\Exception\ForbiddenException;

/**
 * The workshop-picking window is closed. The site signs the attendee out when it sees it
 * (legacy `"logout": true`), so the HTTP layer adds that flag.
 */
final class EnrolmentClosedException extends ForbiddenException
{
}
