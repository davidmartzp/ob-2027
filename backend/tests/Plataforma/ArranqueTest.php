<?php

declare(strict_types=1);

namespace Pruebas\Plataforma;

use PHPUnit\Framework\Attributes\Test;
use Pruebas\CasoFuncional;
use RuntimeException;

final class ArranqueTest extends CasoFuncional
{
    #[Test]
    public function falla_nombrando_la_variable_que_falta(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/DB_HOST/');

        self::crearApp(['DB_CONNECTION' => 'mariadb', 'DB_HOST' => '', 'DB_USERNAME' => 'origami']);
    }

    #[Test]
    public function respeta_la_ruta_base(): void
    {
        $app = self::crearApp(['APP_BASE_PATH' => '/api/']);
        $peticion = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('GET', '/api/salud');

        self::assertSame(200, $app->handle($peticion)->getStatusCode());
    }
}
