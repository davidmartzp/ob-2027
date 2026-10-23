<?php

declare(strict_types=1);

namespace App\Modulos\Salud;

use App\Compartido\Modulo;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class RutasSalud implements Modulo
{
    public function registrar(RouteCollectorProxyInterface $rutas): void
    {
        $rutas->get('/salud', [ControladorSalud::class, 'consultar']);
    }
}
