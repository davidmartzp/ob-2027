<?php

declare(strict_types=1);

namespace Pruebas\Plataforma;

use App\Compartido\Json;
use App\Compartido\Validador;
use PHPUnit\Framework\Attributes\Test;
use Pruebas\CasoFuncional;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class ErroresTest extends CasoFuncional
{
    protected function setUp(): void
    {
        parent::setUp();
        // Rutas solo de prueba para provocar cada tipo de error (Slim no admite closures static).
        $this->app->post('/prueba/validar', function (ServerRequestInterface $p, ResponseInterface $r): ResponseInterface {
            $datos = Validador::validar((array) $p->getParsedBody(), [
                'name' => 'requerido|texto|max:10',
                'email' => 'requerido|correo',
                'age' => 'requerido|entero:1,120',
                'days' => 'requerido|en:T,S,D',
            ]);

            return Json::responder($r, $datos);
        });
        $this->app->get('/prueba/explota', function (): never {
            throw new RuntimeException('SELECT * FROM secreto WHERE clave = 123');
        });
    }

    #[Test]
    public function ruta_inexistente_es_404_en_json(): void
    {
        $respuesta = $this->pedir('GET', '/no-existe');

        self::assertSame(404, $respuesta->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $respuesta->getHeaderLine('Content-Type'));
        self::assertSame('no_encontrado', $this->json($respuesta)['error']['codigo']);
    }

    #[Test]
    public function metodo_no_permitido_es_405(): void
    {
        $respuesta = $this->pedir('DELETE', '/salud');

        self::assertSame(405, $respuesta->getStatusCode());
        self::assertSame('metodo_no_permitido', $this->json($respuesta)['error']['codigo']);
    }

    #[Test]
    public function validacion_fallida_lista_cada_campo(): void
    {
        $respuesta = $this->pedir('POST', '/prueba/validar', ['name' => '', 'email' => 'pepito', 'age' => '200', 'days' => 'X']);

        self::assertSame(422, $respuesta->getStatusCode());
        $error = $this->json($respuesta)['error'];
        self::assertSame('validacion', $error['codigo']);
        self::assertSame(['name', 'email', 'age', 'days'], array_keys($error['campos']));
        self::assertSame('Escriba un correo válido.', $error['campos']['email']);
    }

    #[Test]
    public function validacion_correcta_devuelve_los_valores_convertidos(): void
    {
        $respuesta = $this->pedir('POST', '/prueba/validar', ['name' => ' Ana ', 'email' => 'Ana@Ejemplo.COM', 'age' => '30', 'days' => 'T', 'extra' => 'x']);

        self::assertSame(200, $respuesta->getStatusCode());
        self::assertSame(['name' => 'Ana', 'email' => 'ana@ejemplo.com', 'age' => 30, 'days' => 'T'], $this->json($respuesta));
    }

    #[Test]
    public function json_mal_formado_es_400(): void
    {
        $respuesta = $this->pedir('POST', '/prueba/validar', '{"name": ');

        self::assertSame(400, $respuesta->getStatusCode());
        self::assertSame('peticion_invalida', $this->json($respuesta)['error']['codigo']);
    }

    #[Test]
    public function error_inesperado_sin_detalles_internos_en_produccion(): void
    {
        $registro = ini_set('error_log', '/dev/null');
        $respuesta = $this->pedir('GET', '/prueba/explota');
        ini_set('error_log', (string) $registro);

        self::assertSame(500, $respuesta->getStatusCode());
        $cuerpo = (string) $respuesta->getBody();
        self::assertSame('error_interno', $this->json($respuesta)['error']['codigo']);
        self::assertStringNotContainsString('secreto', $cuerpo);
        self::assertStringNotContainsString('.php', $cuerpo);
        self::assertArrayNotHasKey('detalle', $this->json($respuesta)['error']);
    }

    #[Test]
    public function encabezados_de_seguridad_en_toda_respuesta(): void
    {
        $respuesta = $this->pedir('GET', '/no-existe');

        self::assertSame('nosniff', $respuesta->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('DENY', $respuesta->getHeaderLine('X-Frame-Options'));
    }
}
