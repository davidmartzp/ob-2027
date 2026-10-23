<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Defensive headers for a JSON API that is never rendered as a page. */
final class SecurityHeaders implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
    }
}
