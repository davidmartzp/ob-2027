<?php

declare(strict_types=1);

namespace App\Modulos\Salud;

use App\Compartido\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ControladorSalud
{
    public function __construct(private ServicioSalud $servicio)
    {
    }

    public function consultar(ServerRequestInterface $peticion, ResponseInterface $respuesta): ResponseInterface
    {
        $estado = $this->servicio->estado();

        return Json::responder($respuesta, $estado, $estado['estado'] === 'ok' ? 200 : 503);
    }
}
