<?php

declare(strict_types=1);

/* Módulos de la API (cada uno registra sus rutas). Módulo nuevo = carpeta en src/Modulos + alta aquí. */

return [
    App\Modulos\Salud\RutasSalud::class,
    App\Modulos\Autenticacion\RutasAutenticacion::class,
];
