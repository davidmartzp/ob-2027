<?php

declare(strict_types=1);

namespace App\Modulos\Autenticacion;

use App\Compartido\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ControladorAutenticacion
{
    public function __construct(private ServicioAutenticacion $autenticacion, private ServicioEnlaces $enlaces)
    {
    }

    public function iniciar(ServerRequestInterface $peticion, ResponseInterface $respuesta): ResponseInterface
    {
        // Solo REMOTE_ADDR: X-Forwarded-For lo puede inventar cualquiera y burlaría el límite por IP.
        $ip = (string) ($peticion->getServerParams()['REMOTE_ADDR'] ?? 'desconocida');

        return Json::responder($respuesta, $this->autenticacion->iniciar((array) $peticion->getParsedBody(), $ip));
    }

    public function cerrar(ServerRequestInterface $peticion, ResponseInterface $respuesta): ResponseInterface
    {
        $this->autenticacion->cerrar(Sesion::de($peticion));

        return Json::vacio($respuesta);
    }

    public function yo(ServerRequestInterface $peticion, ResponseInterface $respuesta): ResponseInterface
    {
        return Json::responder($respuesta, $this->autenticacion->usuario(Sesion::de($peticion)));
    }

    public function solicitarEnlace(ServerRequestInterface $peticion, ResponseInterface $respuesta): ResponseInterface
    {
        $this->enlaces->solicitar((array) $peticion->getParsedBody());

        return Json::responder($respuesta, [
            'mensaje' => 'Si ese correo está inscrito, te enviamos un enlace para entrar. Revisa también el correo no deseado.',
        ], 202);
    }

    public function canjearEnlace(ServerRequestInterface $peticion, ResponseInterface $respuesta): ResponseInterface
    {
        return Json::responder($respuesta, $this->enlaces->canjear((array) $peticion->getParsedBody()));
    }
}
