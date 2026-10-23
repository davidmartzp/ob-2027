<?php

declare(strict_types=1);

namespace App\Domain\Partner;

/** A sponsor shown next to the workshops it supports, reachable by WhatsApp. */
final readonly class Partner
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $logo,
        public string $whatsapp,
        public string $message,
        public bool $active = true,
    ) {
    }

    /** wa.me link with the pre-filled message, URL-encoded (spaces as %20). */
    public function whatsappUrl(): string
    {
        return 'https://wa.me/' . $this->whatsapp . '?text=' . rawurlencode($this->message);
    }
}
