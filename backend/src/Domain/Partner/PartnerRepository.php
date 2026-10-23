<?php

declare(strict_types=1);

namespace App\Domain\Partner;

interface PartnerRepository
{
    /**
     * Active partners of each workshop.
     *
     * @param list<int> $workshopIds
     * @return array<int, list<Partner>> keyed by workshop id (workshops without partners are absent)
     */
    public function activeByWorkshop(array $workshopIds): array;
}
