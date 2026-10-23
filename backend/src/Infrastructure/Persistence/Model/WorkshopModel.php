<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Model;

use App\Domain\Workshop\Workshop;
use App\Domain\Workshop\WorkshopStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model of the legacy `workshops` table (`public` = audience, `id_attendee` =
 * instructor, `tickets` = remaining capacity).
 *
 * @property int         $id
 * @property int|null    $id_attendee
 * @property string      $name
 * @property string|null $public
 * @property string|null $level
 * @property int|null    $duration
 * @property string|null $observations
 * @property string|null $image
 * @property int         $status
 * @property int         $tickets
 * @property int         $part
 * @property int|null    $relation
 */
final class WorkshopModel extends Model
{
    protected $table = 'workshops';
    protected $guarded = ['id'];
    protected $casts = [
        'id_attendee' => 'integer',
        'duration' => 'integer',
        'status' => 'integer',
        'tickets' => 'integer',
        'part' => 'integer',
        'relation' => 'integer',
    ];

    public function toEntity(): Workshop
    {
        return new Workshop(
            id: $this->id,
            attendeeId: $this->id_attendee,
            name: $this->name,
            audience: $this->public,
            level: $this->level,
            duration: $this->duration,
            observations: $this->observations,
            image: $this->image,
            status: WorkshopStatus::from($this->status ?? WorkshopStatus::Active->value),
            tickets: $this->tickets ?? 0,
            part: $this->part ?? 1,
            relation: $this->relation,
        );
    }
}
