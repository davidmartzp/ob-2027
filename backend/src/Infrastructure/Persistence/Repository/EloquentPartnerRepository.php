<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Partner\PartnerRepository;
use App\Infrastructure\Persistence\Model\PartnerModel;

final class EloquentPartnerRepository implements PartnerRepository
{
    public function activeByWorkshop(array $workshopIds): array
    {
        if ($workshopIds === []) {
            return [];
        }
        $rows = PartnerModel::query()
            ->join('workshop_partner', 'workshop_partner.id_partner', '=', 'partners.id')
            ->whereIn('workshop_partner.id_workshop', $workshopIds)
            ->where('partners.status', 1)
            ->orderBy('partners.name')
            ->get(['partners.*', 'workshop_partner.id_workshop']);

        $byWorkshop = [];
        foreach ($rows as $row) {
            $byWorkshop[(int) $row->getAttribute('id_workshop')][] = $row->toEntity();
        }

        return $byWorkshop;
    }
}
