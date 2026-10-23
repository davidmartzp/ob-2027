<?php

declare(strict_types=1);

namespace App\Application\Mail;

use App\Domain\Attendee\RecipientGroup;

/** One kind of email: its subject, template and who receives it when sent in bulk. */
final readonly class EmailType
{
    public function __construct(
        public string $key,
        public string $subject,
        /** `resources/emails/<template>.html.twig`. */
        public string $template,
        /** Bulk recipients; null = not sendable from POST /emails/{type} (automatic emails). */
        public ?RecipientGroup $recipients,
        /** Only to explicit `ids` (no "send to everyone"). */
        public bool $requiresIds = false,
        /** Sending again with explicit `ids` re-sends to people who already got it. */
        public bool $resendable = false,
    ) {
    }

    public function isManual(): bool
    {
        return $this->recipients !== null;
    }
}
