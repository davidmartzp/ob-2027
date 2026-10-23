<?php

declare(strict_types=1);

namespace App\Domain\Auth;

/** CMS user (legacy `users` table). Created only from the console. */
final readonly class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $passwordHash,
    ) {
    }

    /** @return array{id: int, name: string, email: string} */
    public function toPublicArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email];
    }
}
