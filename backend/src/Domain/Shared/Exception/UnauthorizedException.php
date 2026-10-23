<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class UnauthorizedException extends DomainException
{
    public function __construct(string $message = 'Hace falta iniciar sesión.')
    {
        parent::__construct($message);
    }
}
