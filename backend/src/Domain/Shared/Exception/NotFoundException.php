<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class NotFoundException extends DomainException
{
    public function __construct(string $message = 'No encontramos lo que busca.')
    {
        parent::__construct($message);
    }
}
