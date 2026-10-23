<?php

declare(strict_types=1);

namespace App\Application\Partner;

use App\Application\Port\ImageStorage;
use App\Domain\Partner\Partner;

/** Public JSON shape of a partner. */
final readonly class PartnerView
{
    public function __construct(private ImageStorage $images)
    {
    }

    /** @return array{id: int, name: string, logo: string|null, whatsapp: string, message: string, whatsapp_url: string} */
    public function toArray(Partner $partner): array
    {
        return [
            'id' => $partner->id,
            'name' => $partner->name,
            'logo' => $this->images->url($partner->logo),
            'whatsapp' => $partner->whatsapp,
            'message' => $partner->message,
            'whatsapp_url' => $partner->whatsappUrl(),
        ];
    }
}
