<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Auth\Session;
use App\Domain\Auth\UserRepository;
use App\Domain\Shared\Exception\NotFoundException;

final readonly class GetCurrentUser
{
    public function __construct(private UserRepository $users)
    {
    }

    /** @return array{id: int, name: string, email: string} */
    public function execute(Session $session): array
    {
        $user = $this->users->findById($session->subjectId) ?? throw new NotFoundException('El usuario ya no existe.');

        return $user->toPublicArray();
    }
}
