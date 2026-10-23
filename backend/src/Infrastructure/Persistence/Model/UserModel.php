<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Model;

use App\Domain\Auth\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model of the legacy `users` table.
 *
 * @property int    $id
 * @property string $name
 * @property string $email
 * @property string $password
 */
final class UserModel extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];

    public function toEntity(): User
    {
        return new User($this->id, $this->name, $this->email, $this->password);
    }
}
