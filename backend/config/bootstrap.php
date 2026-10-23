<?php

declare(strict_types=1);

/*
 * Builds the application: .env → Settings → container (PHP-DI) → Eloquent → Slim.
 * Shared by public/index.php, bin/console and the tests.
 *
 * $environment overrides environment variables; $definitions overrides container entries
 * (tests use both, e.g. to freeze the clock).
 */

use App\Infrastructure\Config\Settings;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\App;
use Slim\Factory\AppFactory;

return static function (array $environment = [], array $definitions = []): App {
    $root = dirname(__DIR__);
    // Tests are hermetic: they run on phpunit.xml's variables only, never on the local .env.
    if (getenv('APP_ENV') !== 'testing') {
        Dotenv::createImmutable($root)->safeLoad();
    }

    $settings = Settings::fromEnvironment($environment);
    date_default_timezone_set('America/Bogota');

    $builder = new ContainerBuilder();
    $builder->useAutowiring(true);
    $builder->addDefinitions((require __DIR__ . '/container.php')($settings, $root));
    $builder->addDefinitions($definitions);
    $container = $builder->build();

    // Eloquent is global: models use it without injection.
    $container->get(Capsule::class)->setAsGlobal();
    $container->get(Capsule::class)->bootEloquent();

    $app = AppFactory::createFromContainer($container);
    $app->setBasePath(rtrim($settings->string('APP_BASE_PATH'), '/'));

    (require __DIR__ . '/middleware.php')($app, $settings);
    (require __DIR__ . '/routes.php')($app);

    return $app;
};
