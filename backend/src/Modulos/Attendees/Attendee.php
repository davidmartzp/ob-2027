<?php

declare(strict_types=1);

namespace App\Modulos\Attendees;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Asistente del congreso (antes `Assistant`/`assistants`).
 * Estados: 0 pendiente, 1 confirmado, 5 eliminado (baja lógica).
 *
 * @property int         $id
 * @property int|null    $code
 * @property string|null $special_code
 * @property string      $name
 * @property string      $lastname
 * @property string      $email
 * @property int         $status
 */
final class Attendee extends Model
{
    public const int PENDIENTE = 0;
    public const int CONFIRMADO = 1;
    public const int ELIMINADO = 5;

    protected $table = 'attendees';
    protected $guarded = ['id'];

    /** @param Builder<self> $consulta */
    public function scopeVigentes(Builder $consulta): void
    {
        $consulta->where('status', '!=', self::ELIMINADO);
    }

    public function nombreCompleto(): string
    {
        return trim($this->name . ' ' . $this->lastname);
    }
}
