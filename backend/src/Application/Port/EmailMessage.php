<?php

declare(strict_types=1);

namespace App\Application\Port;

/** An email to queue: rendered from `resources/emails/<template>.html.twig` (+ `.txt.twig`). */
final readonly class EmailMessage
{
    /**
     * @param array<string, mixed> $data      Template variables (escaped by the renderer).
     * @param string|null          $uniqueKey Prevents queueing the same email twice, e.g. "instructions:27".
     */
    public function __construct(
        public string $type,
        public string $to,
        public string $subject,
        public string $template,
        public array $data = [],
        public ?int $attendeeId = null,
        public ?string $uniqueKey = null,
    ) {
    }
}
