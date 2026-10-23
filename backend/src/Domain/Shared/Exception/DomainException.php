<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

use RuntimeException;

/**
 * Expected business error. The domain and the use cases throw it; the HTTP layer
 * (Infrastructure\Http\ErrorHandler) maps each subclass to a status code and an
 * `{"error": {"code", "message"}}` body. Messages are user-facing, in Spanish.
 */
abstract class DomainException extends RuntimeException
{
}
