<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Domain\Shared\Exception\ForbiddenException;

/** The authenticated caller of a request, resolved from a valid access token. */
final readonly class Session
{
    public function __construct(
        public TokenType $type,
        public int $subjectId,
        public int $tokenId,
    ) {
    }

    public function isCms(): bool
    {
        return $this->type === TokenType::Cms;
    }

    /** Attendee routes over their own data (`/attendees/{id}/…`). */
    public function requireAttendee(int $attendeeId): void
    {
        if ($this->type !== TokenType::Attendee || $this->subjectId !== $attendeeId) {
            throw new ForbiddenException('Solo puede gestionar sus propios datos.');
        }
    }

    /** The CMS can act on anything; an attendee only on what is theirs. */
    public function requireCmsOrOwner(int $ownerAttendeeId): void
    {
        if (!$this->isCms() && $this->subjectId !== $ownerAttendeeId) {
            throw new ForbiddenException('Solo puede gestionar sus propios datos.');
        }
    }
}
