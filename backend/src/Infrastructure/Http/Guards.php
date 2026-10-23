<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Auth\TokenService;
use App\Domain\Auth\TokenType;
use App\Infrastructure\Http\Middleware\Authenticate;

/** Authentication middleware ready for `config/routes.php`: `->add($guards->cms())`. */
final readonly class Guards
{
    public function __construct(private TokenService $tokens)
    {
    }

    public function cms(): Authenticate
    {
        return new Authenticate($this->tokens, [TokenType::Cms]);
    }

    public function attendee(): Authenticate
    {
        return new Authenticate($this->tokens, [TokenType::Attendee]);
    }

    /** Routes shared by the CMS and the owning attendee; the use case checks ownership. */
    public function cmsOrAttendee(): Authenticate
    {
        return new Authenticate($this->tokens, [TokenType::Cms, TokenType::Attendee]);
    }
}
