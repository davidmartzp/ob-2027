<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use RuntimeException;

/** Thrown by the JSON body parser; mapped to 400 `bad_request`. */
final class InvalidJsonException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El cuerpo no es un JSON válido.');
    }
}
