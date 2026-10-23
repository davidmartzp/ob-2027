<?php

declare(strict_types=1);

/*
 * Arma la aplicación: .env → ajustes → contenedor (PHP-DI) → Eloquent → Slim.
 * Lo comparten public/index.php, bin/consola y las pruebas (tests/CasoFuncional.php).
 * `$sobrescribir` reemplaza variables de entorno (solo lo usan las pruebas).
 */

use App\Compartido\Ajustes;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\App;
use Slim\Factory\AppFactory;

return static function (array $sobrescribir = []): App {
    $raiz = dirname(__DIR__);
    Dotenv::createImmutable($raiz)->safeLoad();

    $ajustes = Ajustes::desdeEntorno($sobrescribir);
    date_default_timezone_set('America/Bogota');

    $constructor = new ContainerBuilder();
    $constructor->useAutowiring(true);
    $constructor->addDefinitions((require __DIR__ . '/dependencias.php')($ajustes, $raiz));
    $contenedor = $constructor->build();

    // Eloquent queda global: los modelos lo usan sin inyección.
    $contenedor->get(Capsule::class)->setAsGlobal();
    $contenedor->get(Capsule::class)->bootEloquent();

    $app = AppFactory::createFromContainer($contenedor);
    $app->setBasePath(rtrim($ajustes->texto('APP_BASE_PATH'), '/'));

    (require __DIR__ . '/middleware.php')($app, $ajustes);
    foreach (require __DIR__ . '/rutas.php' as $modulo) {
        $contenedor->get($modulo)->registrar($app);
    }

    return $app;
};
