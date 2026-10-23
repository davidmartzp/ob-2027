<?php

declare(strict_types=1);

namespace App\Domain\Auth;

interface UserRepository
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(string $name, string $email, string $passwordHash): User;

    public function updatePasswordHash(int $id, string $passwordHash): void;
}
