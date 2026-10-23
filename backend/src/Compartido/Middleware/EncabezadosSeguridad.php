<?php

declare(strict_types=1);

namespace App\Compartido\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Encabezados defensivos para una API JSON que nunca se muestra como página. */
final class EncabezadosSeguridad implements MiddlewareInterface
{
    public function process(ServerRequestInterface $peticion, RequestHandlerInterface $manejador): ResponseInterface
    {
        return $manejador->handle($peticion)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
    }
}
