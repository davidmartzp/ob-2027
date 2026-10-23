<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Schedule\EnrolmentClosedException;
use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\ForbiddenException;
use App\Domain\Shared\Exception\NotFoundException;
use App\Domain\Shared\Exception\TooManyAttemptsException;
use App\Domain\Shared\Exception\UnauthorizedException;
use App\Domain\Shared\Exception\ValidationException;
use App\Infrastructure\Config\Settings;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Throwable;

/**
 * Turns any exception into `{"error": {"code", "message", ...}}` with its HTTP status.
 * Domain exceptions carry no HTTP knowledge: the mapping lives here. Unexpected errors
 * are logged; with APP_DEBUG=false the response has no traces, paths or SQL.
 */
final readonly class ErrorHandler
{
    public function __construct(private ResponseFactoryInterface $responses, private Settings $settings)
    {
    }

    public function __invoke(ServerRequestInterface $request, Throwable $error): ResponseInterface
    {
        [$status, $body] = match (true) {
            $error instanceof ValidationException => [422, ['code' => 'validation_failed', 'message' => $error->getMessage(), 'fields' => $error->fields]],
            $error instanceof NotFoundException => [404, ['code' => 'not_found', 'message' => $error->getMessage()]],
            $error instanceof UnauthorizedException => [401, ['code' => 'unauthorized', 'message' => $error->getMessage()]],
            $error instanceof EnrolmentClosedException => [403, ['code' => 'enrolment_closed', 'message' => $error->getMessage()]],
            $error instanceof ForbiddenException => [403, ['code' => 'forbidden', 'message' => $error->getMessage()]],
            $error instanceof ConflictException => [409, ['code' => 'conflict', 'message' => $error->getMessage()]],
            $error instanceof TooManyAttemptsException => [429, ['code' => 'too_many_requests', 'message' => $error->getMessage()]],
            $error instanceof InvalidJsonException => [400, ['code' => 'bad_request', 'message' => $error->getMessage()]],
            $error instanceof HttpNotFoundException => [404, ['code' => 'not_found', 'message' => 'Ruta no encontrada.']],
            $error instanceof HttpMethodNotAllowedException => [405, ['code' => 'method_not_allowed', 'message' => 'Método no permitido en esta ruta.']],
            $error instanceof HttpException => [$error->getCode(), ['code' => 'http_error', 'message' => $error->getMessage()]],
            default => [500, ['code' => 'internal_error', 'message' => 'Ocurrió un error inesperado. Intente de nuevo más tarde.']],
        };

        if ($status >= 500) {
            error_log(sprintf(
                '[api] %s %s → %s: %s at %s:%d',
                $request->getMethod(),
                $request->getUri()->getPath(),
                $error::class,
                $error->getMessage(),
                $error->getFile(),
                $error->getLine(),
            ));
            if ($this->settings->debug()) {
                $body['debug'] = [
                    'type' => $error::class,
                    'message' => $error->getMessage(),
                    'file' => $error->getFile() . ':' . $error->getLine(),
                    'trace' => explode("\n", $error->getTraceAsString()),
                ];
            }
        }

        $payload = ['error' => $body];
        if ($error instanceof EnrolmentClosedException) {
            // The site signs the attendee out when it sees this (legacy contract).
            $payload['logout'] = true;
        }
        $response = Json::respond($this->responses->createResponse(), $payload, $status);
        if ($error instanceof HttpMethodNotAllowedException) {
            $response = $response->withHeader('Allow', implode(', ', $error->getAllowedMethods()));
        }

        return $response;
    }
}
