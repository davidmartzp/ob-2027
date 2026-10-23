<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Port\PasswordHasher;
use App\Application\Shared\Validator;
use App\Domain\Auth\User;
use App\Domain\Auth\UserRepository;
use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\ValidationException;

/** Creates a CMS user. Console only: the API never registers users. */
final readonly class CreateUser
{
    public const int MIN_PASSWORD_LENGTH = 10;

    public function __construct(private UserRepository $users, private PasswordHasher $hasher)
    {
    }

    public function execute(string $email, string $name, string $password): User
    {
        $data = Validator::validate(['email' => $email, 'name' => $name], [
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
        ]);
        self::ensureStrongPassword($password);
        if ($this->users->findByEmail($data['email']) !== null) {
            throw new ConflictException("Ya existe un usuario con el correo {$data['email']}.");
        }

        return $this->users->create($data['name'], $data['email'], $this->hasher->hash($password));
    }

    public static function ensureStrongPassword(string $password): void
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw ValidationException::field('password', 'La contraseña debe tener al menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres.');
        }
    }
}
