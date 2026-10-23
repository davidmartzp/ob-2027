<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Partner\Partner;
use App\Domain\Partner\PartnerRepository;
use App\Infrastructure\Persistence\Model\PartnerModel;
use Illuminate\Database\Connection;

final readonly class EloquentPartnerRepository implements PartnerRepository
{
    public function __construct(private Connection $db)
    {
    }

    public function find(int $id): ?Partner
    {
        return PartnerModel::query()->find($id)?->toEntity();
    }

    public function listActive(): array
    {
        return PartnerModel::query()->where('status', 1)->orderBy('name')->get()
            ->map(static fn (PartnerModel $model): Partner => $model->toEntity())->all();
    }

    public function create(array $fields): Partner
    {
        return PartnerModel::query()->create($fields)->refresh()->toEntity();
    }

    public function update(int $id, array $fields): Partner
    {
        $model = PartnerModel::query()->findOrFail($id);
        $model->fill($fields)->save();

        return $model->refresh()->toEntity();
    }

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

    public function existingIds(array $ids): array
    {
        return $ids === [] ? [] : PartnerModel::query()->whereIn('id', $ids)->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)->all();
    }

    public function replaceForWorkshop(int $workshopId, array $partnerIds): void
    {
        $this->db->transaction(function () use ($workshopId, $partnerIds): void {
            $this->db->table('workshop_partner')->where('id_workshop', $workshopId)->delete();
            $this->db->table('workshop_partner')->insert(array_map(
                static fn (int $partnerId): array => ['id_workshop' => $workshopId, 'id_partner' => $partnerId],
                $partnerIds,
            ));
        });
    }
}
