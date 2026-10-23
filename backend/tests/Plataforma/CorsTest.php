<?php

declare(strict_types=1);

namespace Pruebas\Plataforma;

use PHPUnit\Framework\Attributes\Test;
use Pruebas\CasoFuncional;

final class CorsTest extends CasoFuncional
{
    #[Test]
    public function origen_permitido_recibe_el_encabezado(): void
    {
        $respuesta = $this->pedir('GET', '/salud', encabezados: ['Origin' => 'http://cms.test']);

        self::assertSame('http://cms.test', $respuesta->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertStringContainsString('Origin', $respuesta->getHeaderLine('Vary'));
    }

    #[Test]
    public function origen_no_permitido_no_recibe_el_encabezado(): void
    {
        $respuesta = $this->pedir('GET', '/salud', encabezados: ['Origin' => 'http://malicioso.test']);

        self::assertFalse($respuesta->hasHeader('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function verificacion_previa_responde_sin_llegar_a_las_rutas(): void
    {
        $respuesta = $this->pedir('OPTIONS', '/attendees', encabezados: [
            'Origin' => 'http://sitio.test',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type',
        ]);

        self::assertSame(204, $respuesta->getStatusCode());
        self::assertSame('http://sitio.test', $respuesta->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertStringContainsString('POST', $respuesta->getHeaderLine('Access-Control-Allow-Methods'));
        self::assertStringContainsString('Authorization', $respuesta->getHeaderLine('Access-Control-Allow-Headers'));
    }

    #[Test]
    public function los_errores_tambien_llevan_cors(): void
    {
        $respuesta = $this->pedir('GET', '/no-existe', encabezados: ['Origin' => 'http://sitio.test']);

        self::assertSame(404, $respuesta->getStatusCode());
        self::assertSame('http://sitio.test', $respuesta->getHeaderLine('Access-Control-Allow-Origin'));
    }
}
