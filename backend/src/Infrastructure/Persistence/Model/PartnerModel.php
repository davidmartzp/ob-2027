<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Model;

use App\Domain\Partner\Partner;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model of `partners` (`status` 1 active, 0 deactivated).
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $logo
 * @property string      $whatsapp
 * @property string      $message
 * @property int         $status
 */
final class PartnerModel extends Model
{
    protected $table = 'partners';
    protected $guarded = ['id'];
    protected $casts = ['status' => 'integer'];

    public function toEntity(): Partner
    {
        return new Partner($this->id, $this->name, $this->logo, $this->whatsapp, $this->message, $this->status === 1);
    }
}
