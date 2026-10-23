<?php

declare(strict_types=1);

namespace App\Compartido;

use Psr\Http\Message\ResponseInterface;

/** Escribe respuestas JSON en UTF-8 sin escapar tildes ni barras. */
final class Json
{
    public static function responder(ResponseInterface $respuesta, mixed $datos, int $estado = 200): ResponseInterface
    {
        $respuesta->getBody()->write(json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        ));

        return $respuesta
            ->withStatus($estado)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    public static function vacio(ResponseInterface $respuesta, int $estado = 204): ResponseInterface
    {
        return $respuesta->withStatus($estado);
    }
}
