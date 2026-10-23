<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Model;

use App\Domain\Attendee\Attendee;
use App\Domain\Attendee\AttendeeStatus;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model of `attendees` (the legacy `assistants` table, renamed by migration).
 * Column names are the legacy ones, including the camelCase `printCertificate`.
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
    protected $casts = [
        'code' => 'integer',
        'age' => 'integer',
        'status' => 'integer',
        'cards' => 'boolean',
        'cardsgroup' => 'integer',
        'staff' => 'boolean',
        'go_to_chiva' => 'boolean',
        'printCertificate' => 'boolean',
        'chiva_companions' => 'integer',
    ];

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
            phone: $this->phone,
            country: $this->country,
            city: $this->city,
            paymethod: $this->paymethod,
            paydate: $this->paydate === null ? null : (string) $this->paydate,
            receipt: $this->receipt,
            companion: $this->companion,
            days: $this->days,
            idname: $this->idname,
            origamigroup: $this->origamigroup,
            info: $this->info,
            cards: (bool) $this->cards,
            cardsgroup: $this->cardsgroup,
            staff: (bool) $this->staff,
            goToChiva: (bool) $this->go_to_chiva,
            printCertificate: (bool) $this->printCertificate,
            chivaCompanions: (int) $this->chiva_companions,
            payAmount: (string) ($this->pay_amount ?? '0'),
            createdAt: $this->created_at instanceof DateTimeInterface ? $this->created_at->format('Y-m-d H:i:s') : null,
            updatedAt: $this->updated_at instanceof DateTimeInterface ? $this->updated_at->format('Y-m-d H:i:s') : null,
        );
    }
}
