<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Port\PasswordHasher;
use App\Domain\Auth\TokenType;
use App\Domain\Auth\User;
use App\Domain\Auth\UserRepository;
use App\Domain\Shared\Exception\NotFoundException;

/** Changes a CMS user's password and closes all their open sessions. Console only. */
final readonly class ChangeUserPassword
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private TokenService $tokens,
    ) {
    }

    public function execute(string $email, string $password): User
    {
        CreateUser::ensureStrongPassword($password);
        $user = $this->users->findByEmail(mb_strtolower(trim($email)))
            ?? throw new NotFoundException("No hay ningún usuario con el correo {$email}.");

        $this->users->updatePasswordHash($user->id, $this->hasher->hash($password));
        $this->tokens->revokeAll(TokenType::Cms, $user->id);

        return $user;
    }
}
