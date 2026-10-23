<?php

declare(strict_types=1);

namespace App\Modulos\Autenticacion;

use Illuminate\Database\Eloquent\Model;

/**
 * Usuario del CMS (tabla heredada `users`). Se crea solo por consola.
 *
 * @property int    $id
 * @property string $name
 * @property string $email
 * @property string $password
 */
final class Usuario extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];

    /** @return array{id: int, name: string, email: string} */
    public function publico(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email];
    }
}
