<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class ConflictException extends DomainException
{
    public function __construct(string $message = 'La operación entra en conflicto con el estado actual.')
    {
        parent::__construct($message);
    }
}
