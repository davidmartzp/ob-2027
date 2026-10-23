<?php

declare(strict_types=1);

namespace App\Compartido\Middleware;

use App\Compartido\Ajustes;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CORS solo para los orígenes de `CORS_ORIGENES` (sitio y CMS). Responde las
 * verificaciones previas `OPTIONS` sin llegar a las rutas. Sin cookies: la API usa
 * tokens Bearer, así que no se envía `Allow-Credentials`.
 */
final readonly class Cors implements MiddlewareInterface
{
    private const string METODOS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
    private const string ENCABEZADOS = 'Authorization, Content-Type, Accept';

    public function __construct(private ResponseFactoryInterface $fabrica, private Ajustes $ajustes)
    {
    }

    public function process(ServerRequestInterface $peticion, RequestHandlerInterface $manejador): ResponseInterface
    {
        $origen = $peticion->getHeaderLine('Origin');
        $esPrevia = $peticion->getMethod() === 'OPTIONS' && $peticion->hasHeader('Access-Control-Request-Method');

        $respuesta = $esPrevia
            ? $this->fabrica->createResponse(204)
            : $manejador->handle($peticion);

        $respuesta = $respuesta->withAddedHeader('Vary', 'Origin');
        if ($origen === '' || !in_array($origen, $this->ajustes->lista('CORS_ORIGENES'), true)) {
            return $respuesta;
        }

        $respuesta = $respuesta->withHeader('Access-Control-Allow-Origin', $origen);
        if ($esPrevia) {
            $respuesta = $respuesta
                ->withHeader('Access-Control-Allow-Methods', self::METODOS)
                ->withHeader('Access-Control-Allow-Headers', self::ENCABEZADOS)
                ->withHeader('Access-Control-Max-Age', '600');
        }

        return $respuesta;
    }
}
