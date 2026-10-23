<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Attendee\Attendee;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Attendee\AttendeeStatus;
use App\Domain\Attendee\RecipientGroup;
use App\Infrastructure\Persistence\Model\AttendeeModel;

final class EloquentAttendeeRepository implements AttendeeRepository
{
    public function findActive(int $id): ?Attendee
    {
        return AttendeeModel::query()->active()->find($id)?->toEntity();
    }

    public function findActiveByEmail(string $email): array
    {
        return AttendeeModel::query()
            ->active()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->orderBy('id')
            ->get()
            ->map(static fn (AttendeeModel $model): Attendee => $model->toEntity())
            ->all();
    }

    public function findRecipients(RecipientGroup $group, ?array $ids = null): array
    {
        $query = AttendeeModel::query()->active()->orderBy('id');
        match ($group) {
            RecipientGroup::Active => null,
            RecipientGroup::Confirmed => $query->where('status', AttendeeStatus::Confirmed->value),
            RecipientGroup::WithCode => $query->where('code', '>', 0),
        };
        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        return $query->get()->map(static fn (AttendeeModel $model): Attendee => $model->toEntity())->all();
    }
}
