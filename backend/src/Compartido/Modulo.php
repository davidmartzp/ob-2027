<?php

declare(strict_types=1);

namespace App\Compartido;

use Slim\Interfaces\RouteCollectorProxyInterface;

/** Un servicio de la API: registra sus rutas. Se da de alta en `config/rutas.php`. */
interface Modulo
{
    public function registrar(RouteCollectorProxyInterface $rutas): void;
}
