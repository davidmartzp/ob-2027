<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Model;

use App\Domain\Attendee\Attendee;
use App\Domain\Attendee\AttendeeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model of `attendees` (the legacy `assistants` table, renamed by migration).
 *
 * @property int         $id
 * @property int|null    $code
 * @property string|null $special_code
 * @property string      $name
 * @property string      $lastname
 * @property string      $email
 * @property int         $age
 * @property int         $status
 */
final class AttendeeModel extends Model
{
    protected $table = 'attendees';
    protected $guarded = ['id'];
    protected $casts = ['code' => 'integer', 'age' => 'integer', 'status' => 'integer'];

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', '!=', AttendeeStatus::Deleted->value);
    }

    public function toEntity(): Attendee
    {
        return new Attendee(
            id: $this->id,
            name: $this->name,
            lastname: $this->lastname,
            email: $this->email,
            age: $this->age,
            status: AttendeeStatus::from($this->status),
            code: $this->code,
            specialCode: $this->special_code,
        );
    }
}
