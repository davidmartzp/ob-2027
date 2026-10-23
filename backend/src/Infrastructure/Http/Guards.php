<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Auth\TokenService;
use App\Domain\Auth\TokenType;
use App\Infrastructure\Http\Middleware\Authenticate;

/** Authentication middleware for `config/routes.php`: `->add($guards->cms())`. Only the CMS signs in. */
final readonly class Guards
{
    public function __construct(private TokenService $tokens)
    {
    }

    public function cms(): Authenticate
    {
        return new Authenticate($this->tokens, [TokenType::Cms]);
    }
}
