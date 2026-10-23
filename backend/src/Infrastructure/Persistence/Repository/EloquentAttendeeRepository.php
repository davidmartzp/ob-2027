<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Attendee\Attendee;
use App\Domain\Attendee\AttendeeRepository;
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
}
