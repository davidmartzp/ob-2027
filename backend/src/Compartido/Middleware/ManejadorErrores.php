<?php

declare(strict_types=1);

namespace App\Compartido\Middleware;

use App\Compartido\Ajustes;
use App\Compartido\Errores\ErrorDominio;
use App\Compartido\Json;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Throwable;

/**
 * Convierte cualquier error en `{"error": {"codigo", "mensaje", ...}}`.
 * Los errores inesperados se registran en el log; con APP_DEBUG=false la respuesta no
 * lleva trazas, rutas ni mensajes internos.
 */
final readonly class ManejadorErrores
{
    public function __construct(private ResponseFactoryInterface $fabrica, private Ajustes $ajustes)
    {
    }

    public function __invoke(ServerRequestInterface $peticion, Throwable $error): ResponseInterface
    {
        [$estado, $cuerpo] = match (true) {
            $error instanceof ErrorDominio => [
                $error->estado(),
                ['codigo' => $error->codigo(), 'mensaje' => $error->getMessage()] + $error->extra(),
            ],
            $error instanceof HttpNotFoundException => [404, ['codigo' => 'no_encontrado', 'mensaje' => 'Ruta no encontrada.']],
            $error instanceof HttpMethodNotAllowedException => [405, ['codigo' => 'metodo_no_permitido', 'mensaje' => 'Método no permitido en esta ruta.']],
            $error instanceof HttpException => [$error->getCode(), ['codigo' => 'error_http', 'mensaje' => $error->getMessage()]],
            default => [500, ['codigo' => 'error_interno', 'mensaje' => 'Ocurrió un error inesperado. Intente de nuevo más tarde.']],
        };

        if ($estado >= 500) {
            error_log(sprintf(
                '[api] %s %s → %s: %s en %s:%d',
                $peticion->getMethod(),
                $peticion->getUri()->getPath(),
                $error::class,
                $error->getMessage(),
                $error->getFile(),
                $error->getLine(),
            ));
            if ($this->ajustes->depurar()) {
                $cuerpo['detalle'] = [
                    'tipo' => $error::class,
                    'mensaje' => $error->getMessage(),
                    'archivo' => $error->getFile() . ':' . $error->getLine(),
                    'traza' => explode("\n", $error->getTraceAsString()),
                ];
            }
        }

        $respuesta = Json::responder($this->fabrica->createResponse(), ['error' => $cuerpo], $estado);
        if ($error instanceof HttpMethodNotAllowedException) {
            $respuesta = $respuesta->withHeader('Allow', implode(', ', $error->getAllowedMethods()));
        }

        return $respuesta;
    }
}
