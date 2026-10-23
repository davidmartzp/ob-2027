<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Auth\User;
use App\Domain\Auth\UserRepository;
use App\Infrastructure\Persistence\Model\UserModel;

final class EloquentUserRepository implements UserRepository
{
    public function findById(int $id): ?User
    {
        return UserModel::query()->find($id)?->toEntity();
    }

    public function findByEmail(string $email): ?User
    {
        return UserModel::query()->where('email', $email)->first()?->toEntity();
    }

    public function create(string $name, string $email, string $passwordHash): User
    {
        return UserModel::query()->create(['name' => $name, 'email' => $email, 'password' => $passwordHash])->toEntity();
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        UserModel::query()->whereKey($id)->update(['password' => $passwordHash]);
    }
}
