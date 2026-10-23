<?php

declare(strict_types=1);

namespace Pruebas\Plataforma;

use PHPUnit\Framework\Attributes\Test;
use Pruebas\CasoFuncional;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SaludTest extends CasoFuncional
{
    #[Test]
    public function servicio_sano_con_cola_vacia(): void
    {
        $respuesta = $this->pedir('GET', '/salud');

        self::assertSame(200, $respuesta->getStatusCode());
        self::assertSame(['estado' => 'ok', 'cola_correo_minutos' => null], $this->json($respuesta));
    }

    #[Test]
    public function informa_la_antiguedad_del_correo_pendiente_mas_viejo(): void
    {
        $base = ['tipo' => 'bienvenida', 'email' => 'a@b.co', 'subject' => 'Hola', 'body' => '<p>Hola</p>', 'estado' => 'pending'];
        $this->bd()->table('email_envios')->insert([
            $base + ['disponible_en' => date('Y-m-d H:i:s', time() - 45 * 60)],
            $base + ['disponible_en' => date('Y-m-d H:i:s', time() - 5 * 60)],
            ['estado' => 'sent', 'disponible_en' => date('Y-m-d H:i:s', time() - 300 * 60)] + $base,
        ]);

        self::assertSame(45, $this->json($this->pedir('GET', '/salud'))['cola_correo_minutos']);
    }

    #[Test]
    public function sin_base_de_datos_responde_503(): void
    {
        $registro = ini_set('error_log', '/dev/null');
        $app = self::crearApp(['DB_DATABASE' => '/ruta/que/no/existe/base.sqlite']);
        $respuesta = $app->handle((new ServerRequestFactory())->createServerRequest('GET', '/salud'));
        ini_set('error_log', (string) $registro);

        self::assertSame(503, $respuesta->getStatusCode());
        self::assertSame('sin_base_de_datos', json_decode((string) $respuesta->getBody(), true)['estado']);
    }
}
