<?php

declare(strict_types=1);

/*
 * Definiciones del contenedor que el autowiring no puede deducir solo.
 * Controladores y servicios se inyectan sin declararlos aquí.
 */

use App\Compartido\Ajustes;
use App\Compartido\BaseDatos\Migrador;
use App\Compartido\BaseDatos\Sembrador;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

return static fn (Ajustes $ajustes, string $raiz): array => [
    Ajustes::class => $ajustes,
    'raiz' => $raiz,
    ResponseFactoryInterface::class => DI\get(ResponseFactory::class),

    Capsule::class => static function () use ($ajustes): Capsule {
        $capsula = new Capsule();
        $driver = $ajustes->texto('DB_CONNECTION', 'mariadb');
        $capsula->addConnection($driver === 'sqlite'
            ? [
                'driver' => 'sqlite',
                'database' => $ajustes->texto('DB_DATABASE'),
                'foreign_key_constraints' => true,
            ]
            : [
                'driver' => $driver,
                'host' => $ajustes->texto('DB_HOST'),
                'port' => $ajustes->entero('DB_PORT', 3306),
                'database' => $ajustes->texto('DB_DATABASE'),
                'username' => $ajustes->texto('DB_USERNAME'),
                'password' => $ajustes->texto('DB_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'strict' => true,
            ]);

        return $capsula;
    },
    Connection::class => static fn (ContainerInterface $c): Connection => $c->get(Capsule::class)->getConnection(),

    // Plantillas de correo: `.html.twig` se escapan como HTML; `.txt.twig`, no (estrategia por nombre).
    Environment::class => static fn (): Environment => new Environment(new FilesystemLoader($raiz . '/plantillas'), [
        'autoescape' => 'name',
        'strict_variables' => true,
        'cache' => false,
    ]),

    Migrador::class => static fn (ContainerInterface $c): Migrador => new Migrador(
        $c->get(Connection::class),
        $raiz . '/database/migraciones',
    ),
    Sembrador::class => static fn (ContainerInterface $c): Sembrador => new Sembrador(
        $c->get(Connection::class),
        $raiz . '/database/semillas',
    ),
];
