<?php

declare(strict_types=1);

namespace App\Modulos\Autenticacion;

use App\Compartido\Modulo;
use Slim\Interfaces\RouteCollectorProxyInterface;

final readonly class RutasAutenticacion implements Modulo
{
    public function __construct(private Guardias $guardias)
    {
    }

    public function registrar(RouteCollectorProxyInterface $rutas): void
    {
        $c = ControladorAutenticacion::class;

        $rutas->post('/auth/login', [$c, 'iniciar']);
        $rutas->post('/auth/logout', [$c, 'cerrar'])->add($this->guardias->cms());
        $rutas->get('/auth/yo', [$c, 'yo'])->add($this->guardias->cms());

        $rutas->post('/auth/enlace-asistente', [$c, 'solicitarEnlace']);
        $rutas->post('/auth/canjear-enlace', [$c, 'canjearEnlace']);
    }
}
