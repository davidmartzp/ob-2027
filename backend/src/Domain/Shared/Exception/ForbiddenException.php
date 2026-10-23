<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

/** Not final: EnrolmentClosedException specialises it. */
class ForbiddenException extends DomainException
{
    public function __construct(string $message = 'No tiene permiso para esta operación.')
    {
        parent::__construct($message);
    }
}
