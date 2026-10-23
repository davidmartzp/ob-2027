<?php

declare(strict_types=1);

namespace Pruebas\Autenticacion;

use App\Compartido\Json;
use App\Modulos\Autenticacion\Guardias;
use App\Modulos\Autenticacion\ServicioTokens;
use App\Modulos\Autenticacion\Sesion;
use PHPUnit\Framework\Attributes\Test;
use Pruebas\CasoFuncional;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TokensTest extends CasoFuncional
{
    protected function setUp(): void
    {
        parent::setUp();
        $guardias = $this->app->getContainer()->get(Guardias::class);
        // Rutas solo de prueba con cada guardia (las reales llegan en los grupos 5 a 8).
        $this->app->get('/prueba/cms', fn (ServerRequestInterface $p, ResponseInterface $r) => Json::responder($r, ['ok' => true]))
            ->add($guardias->cms());
        $this->app->put('/prueba/attendees/{id}', function (ServerRequestInterface $p, ResponseInterface $r, array $args) {
            Sesion::de($p)->exigirAsistente((int) $args['id']);

            return Json::responder($r, ['ok' => true]);
        })->add($guardias->asistente());
    }

    private function token(string $tipo, int $id): string
    {
        return $this->app->getContainer()->get(ServicioTokens::class)->emitir($tipo, $id)['token'];
    }

    #[Test]
    public function ruta_privada_sin_token_es_401(): void
    {
        $respuesta = $this->pedir('GET', '/prueba/cms');

        self::assertSame(401, $respuesta->getStatusCode());
        self::assertSame('no_autorizado', $this->json($respuesta)['error']['codigo']);
    }

    #[Test]
    public function token_inventado_es_401(): void
    {
        self::assertSame(401, $this->pedir('GET', '/prueba/cms', encabezados: self::bearer(str_repeat('a', 64)))->getStatusCode());
        self::assertSame(401, $this->pedir('GET', '/prueba/cms', encabezados: ['Authorization' => 'Basic dXNlcjpwYXNz'])->getStatusCode());
    }

    #[Test]
    public function token_caducado_es_401(): void
    {
        $this->reloj()->fijar('2027-06-05 08:00');
        $token = $this->token(Sesion::CMS, 1);
        self::assertSame(200, $this->pedir('GET', '/prueba/cms', encabezados: self::bearer($token))->getStatusCode());

        $this->reloj()->avanzar('+12 hours +1 minute');
        self::assertSame(401, $this->pedir('GET', '/prueba/cms', encabezados: self::bearer($token))->getStatusCode());
    }

    #[Test]
    public function token_de_asistente_caduca_a_las_dos_horas(): void
    {
        $this->reloj()->fijar('2027-06-05 08:00');
        $token = $this->token(Sesion::ASISTENTE, 7);

        $this->reloj()->avanzar('+1 hour +59 minutes');
        self::assertSame(200, $this->pedir('PUT', '/prueba/attendees/7', encabezados: self::bearer($token))->getStatusCode());
        $this->reloj()->avanzar('+2 minutes');
        self::assertSame(401, $this->pedir('PUT', '/prueba/attendees/7', encabezados: self::bearer($token))->getStatusCode());
    }

    #[Test]
    public function token_de_asistente_en_ruta_del_cms_es_401(): void
    {
        $respuesta = $this->pedir('GET', '/prueba/cms', encabezados: self::bearer($this->token(Sesion::ASISTENTE, 7)));

        self::assertSame(401, $respuesta->getStatusCode());
    }

    #[Test]
    public function asistente_sobre_datos_de_otro_es_403(): void
    {
        $token = $this->token(Sesion::ASISTENTE, 7);

        self::assertSame(200, $this->pedir('PUT', '/prueba/attendees/7', encabezados: self::bearer($token))->getStatusCode());
        $respuesta = $this->pedir('PUT', '/prueba/attendees/8', encabezados: self::bearer($token));
        self::assertSame(403, $respuesta->getStatusCode());
        self::assertSame('prohibido', $this->json($respuesta)['error']['codigo']);
    }
}
