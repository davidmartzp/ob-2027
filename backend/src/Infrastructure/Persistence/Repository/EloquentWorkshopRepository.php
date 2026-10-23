<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Workshop\Workshop;
use App\Domain\Workshop\WorkshopRepository;
use App\Domain\Workshop\WorkshopStatus;
use App\Infrastructure\Persistence\Model\WorkshopModel;
use Illuminate\Support\Collection;

final class EloquentWorkshopRepository implements WorkshopRepository
{
    public function find(int $id): ?Workshop
    {
        return WorkshopModel::query()->find($id)?->toEntity();
    }

    public function create(array $fields): Workshop
    {
        return WorkshopModel::query()->create($fields)->refresh()->toEntity();
    }

    public function update(int $id, array $fields): Workshop
    {
        $model = WorkshopModel::query()->findOrFail($id);
        $model->fill($fields)->save();

        return $model->refresh()->toEntity();
    }

    public function listByAttendee(int $attendeeId): array
    {
        return $this->entities(WorkshopModel::query()
            ->where('id_attendee', $attendeeId)
            ->whereIn('status', [WorkshopStatus::Active->value, WorkshopStatus::Hidden->value])
            ->orderBy('id')
            ->get());
    }

    public function listActiveShuffled(): array
    {
        return $this->entities(WorkshopModel::query()->where('status', WorkshopStatus::Active->value)->inRandomOrder()->get());
    }

    public function listNotWithdrawn(): array
    {
        return $this->entities(WorkshopModel::query()->where('status', '!=', WorkshopStatus::Withdrawn->value)->orderBy('name')->get());
    }

    public function continuationsOf(int $id): array
    {
        return $this->entities(WorkshopModel::query()->where('relation', $id)->where('part', 2)->orderBy('id')->get());
    }

    public function lockForUpdate(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        sort($ids);

        return WorkshopModel::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()
            ->mapWithKeys(static fn (WorkshopModel $model): array => [$model->id => $model->toEntity()])
            ->all();
    }

    public function adjustTickets(int $id, int $delta): void
    {
        WorkshopModel::query()->whereKey($id)->increment('tickets', $delta);
    }

    /** @param Collection<int, WorkshopModel> $models @return list<Workshop> */
    private function entities(Collection $models): array
    {
        return $models->map(static fn (WorkshopModel $model): Workshop => $model->toEntity())->values()->all();
    }
}
