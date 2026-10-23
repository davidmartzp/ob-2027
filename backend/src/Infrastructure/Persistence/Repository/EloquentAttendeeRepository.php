<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Attendee\Attendee;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Attendee\AttendeeStatus;
use App\Domain\Attendee\CodePolicy;
use App\Domain\Attendee\RecipientGroup;
use App\Infrastructure\Persistence\Model\AttendeeModel;
use Illuminate\Database\Connection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use RuntimeException;

final readonly class EloquentAttendeeRepository implements AttendeeRepository
{
    /** Retries if, despite the lock, a registration hits the unique index on code. */
    private const int REGISTER_ATTEMPTS = 5;
    /** Named lock that serialises number assignment (MariaDB GET_LOCK; SQLite needs none). */
    private const string CODE_LOCK = 'origami.attendees.code';
    private const int LOCK_TIMEOUT_SECONDS = 10;

    public function __construct(private Connection $db)
    {
    }

    public function findActive(int $id): ?Attendee
    {
        return AttendeeModel::query()->active()->find($id)?->toEntity();
    }

    public function findActiveByEmail(string $email): array
    {
        return $this->entities(AttendeeModel::query()
            ->active()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->orderBy('id')
            ->get());
    }

    public function findActiveByEmailAndCode(string $email, string $code): ?Attendee
    {
        return AttendeeModel::query()
            ->active()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->where(static function ($query) use ($code): void {
                $query->whereRaw('UPPER(special_code) = ?', [strtoupper($code)]);
                if (ctype_digit($code)) {
                    $query->orWhere('code', (int) $code);
                }
            })
            ->orderBy('id')
            ->first()
            ?->toEntity();
    }

    public function listActive(): array
    {
        return $this->entities(AttendeeModel::query()->active()->orderBy('paydate')->orderBy('id')->get());
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

        return $this->entities($query->get());
    }

    public function register(array $fields, bool $assignCode): Attendee
    {
        if (!$assignCode) {
            return AttendeeModel::query()->create($fields + ['code' => null])->toEntity();
        }

        return $this->withCodeLock(static function () use ($fields): Attendee {
            for ($attempt = 1; $attempt <= self::REGISTER_ATTEMPTS; $attempt++) {
                try {
                    $fields['code'] = (int) AttendeeModel::query()->max('code') + 1;

                    return AttendeeModel::query()->create($fields)->toEntity();
                } catch (UniqueConstraintViolationException) {
                    usleep(random_int(5_000, 30_000));
                }
            }

            throw new RuntimeException('Could not assign a participant number after several attempts.');
        });
    }

    public function update(int $id, array $fields): Attendee
    {
        $model = AttendeeModel::query()->findOrFail($id);
        $model->fill($fields)->save();

        return $model->refresh()->toEntity();
    }

    public function codeTakenByOther(int $code, int $exceptId): bool
    {
        return AttendeeModel::query()->where('code', $code)->whereKeyNot($exceptId)->exists();
    }

    public function confirmPending(array $ids): int
    {
        return AttendeeModel::query()
            ->whereIn('id', $ids)
            ->where('status', AttendeeStatus::Pending->value)
            ->update(['status' => AttendeeStatus::Confirmed->value]);
    }

    public function assignCodes(CodePolicy $policy, bool $fromScratch): int
    {
        return $this->withCodeLock(fn (): int => $this->db->transaction(static function () use ($policy, $fromScratch): int {
            if ($fromScratch) {
                AttendeeModel::query()->whereNotNull('code')->update(['code' => null]);
            }
            $next = (int) AttendeeModel::query()->max('code') + 1;

            $assigned = 0;
            $candidates = AttendeeModel::query()->active()->whereNull('code')->orderBy('paydate')->orderBy('id')->get();
            foreach ($candidates as $model) {
                if ($policy->allows($model->toEntity())) {
                    $model->code = $next++;
                    $model->save();
                    $assigned++;
                }
            }

            return $assigned;
        }));
    }

    /**
     * Runs `$work` while holding the named lock, so registrations and bulk numbering never
     * read the same "highest number". A named lock (unlike SELECT … FOR UPDATE on MAX) takes
     * no gap locks on the table, which is what made concurrent inserts deadlock on MariaDB.
     *
     * @template T
     * @param callable(): T $work
     * @return T
     */
    private function withCodeLock(callable $work): mixed
    {
        if ($this->db->getDriverName() === 'sqlite') {
            return $work();
        }
        $locked = $this->db->selectOne('SELECT GET_LOCK(?, ?) AS ok', [self::CODE_LOCK, self::LOCK_TIMEOUT_SECONDS]);
        if ((int) ($locked->ok ?? 0) !== 1) {
            throw new RuntimeException('Timed out waiting to assign a participant number.');
        }
        try {
            return $work();
        } finally {
            $this->db->select('SELECT RELEASE_LOCK(?)', [self::CODE_LOCK]);
        }
    }

    /** @param Collection<int, AttendeeModel> $models @return list<Attendee> */
    private function entities(Collection $models): array
    {
        return $models->map(static fn (AttendeeModel $model): Attendee => $model->toEntity())->values()->all();
    }
}
