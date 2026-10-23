<?php

declare(strict_types=1);

/*
 * Slim ejecuta el middleware en orden inverso al de alta: el último añadido es el más
 * externo. Por eso CORS y los encabezados van al final: así envuelven también las
 * respuestas de error.
 */

use App\Compartido\Ajustes;
use App\Compartido\Errores\PeticionInvalida;
use App\Compartido\Middleware\Cors;
use App\Compartido\Middleware\EncabezadosSeguridad;
use App\Compartido\Middleware\ManejadorErrores;
use Slim\App;
use Slim\Middleware\BodyParsingMiddleware;

return static function (App $app, Ajustes $ajustes): void {
    $contenedor = $app->getContainer();

    // JSON mal formado = 400 (por defecto Slim lo dejaría pasar como cuerpo vacío).
    $app->add(new BodyParsingMiddleware([
        'application/json' => static function (string $entrada): ?array {
            if (trim($entrada) === '') {
                return null;
            }
            $datos = json_decode($entrada, true);
            if (!is_array($datos)) {
                throw new PeticionInvalida('El cuerpo no es un JSON válido.');
            }

            return $datos;
        },
    ]));
    $app->addRoutingMiddleware();

    $errores = $app->addErrorMiddleware($ajustes->depurar(), false, false);
    $errores->setDefaultErrorHandler($contenedor->get(ManejadorErrores::class));

    $app->add(EncabezadosSeguridad::class);
    $app->add(Cors::class);
};
