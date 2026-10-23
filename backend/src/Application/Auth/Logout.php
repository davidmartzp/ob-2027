<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Auth\Session;

final readonly class Logout
{
    public function __construct(private TokenService $tokens)
    {
    }

    public function execute(Session $session): void
    {
        $this->tokens->revoke($session);
    }
}
