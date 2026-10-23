<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Application\Auth\TokenService;
use App\Domain\Auth\TokenType;
use App\Domain\Shared\Exception\UnauthorizedException;
use App\Infrastructure\Http\RequestSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Requires a valid `Authorization: Bearer <token>` of one of the allowed types and stores
 * the Session on the request. A token of another type counts as missing (401).
 * Routes get it through `Guards` (cms(), attendee(), cmsOrAttendee()).
 */
final readonly class Authenticate implements MiddlewareInterface
{
    /** @param list<TokenType> $allowed */
    public function __construct(private TokenService $tokens, private array $allowed)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        $token = preg_match('/^Bearer\s+(\S+)$/i', $header, $match) === 1 ? $match[1] : '';
        $session = $token === '' ? null : $this->tokens->resolve($token);

        if ($session === null || !in_array($session->type, $this->allowed, true)) {
            throw new UnauthorizedException($token === '' ? 'Hace falta iniciar sesión.' : 'La sesión no es válida o caducó.');
        }

        return $handler->handle(RequestSession::attach($request, $session));
    }
}
