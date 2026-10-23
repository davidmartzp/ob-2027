<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class ValidationException extends DomainException
{
    /** @param array<string, string> $fields User-facing message per invalid field. */
    public function __construct(public readonly array $fields, string $message = 'Hay datos por corregir.')
    {
        parent::__construct($message);
    }

    public static function field(string $field, string $message): self
    {
        return new self([$field => $message]);
    }
}
