<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class TooManyAttemptsException extends DomainException
{
    public function __construct(string $message = 'Demasiados intentos. Espere un momento y vuelva a intentarlo.')
    {
        parent::__construct($message);
    }
}
