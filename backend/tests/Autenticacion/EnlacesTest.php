<?php

declare(strict_types=1);

namespace Pruebas\Autenticacion;

use App\Modulos\Attendees\Attendee;
use PHPUnit\Framework\Attributes\Test;
use Pruebas\CasoFuncional;

final class EnlacesTest extends CasoFuncional
{
    protected function entorno(): array
    {
        return ['SITIO_URL' => 'https://origamibogota.com', 'ENLACE_ACCESO_RUTA' => 'acceso'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $base = ['lastname' => 'Rojas', 'age' => 40, 'phone' => '3001234567', 'paydate' => '2027-03-01 10:00:00', 'status' => Attendee::CONFIRMADO];
        Attendee::query()->insert([
            ['id' => 1, 'name' => 'Marta', 'email' => 'familia@ejemplo.com'] + $base,
            ['id' => 2, 'name' => 'Tomás', 'email' => 'Familia@Ejemplo.com', 'age' => 9] + $base,
            ['id' => 3, 'name' => 'Borrado', 'email' => 'familia@ejemplo.com', 'status' => Attendee::ELIMINADO] + $base,
        ]);
    }

    private function solicitar(string $email): \Psr\Http\Message\ResponseInterface
    {
        return $this->pedir('POST', '/auth/enlace-asistente', ['email' => $email]);
    }

    /** Extrae las claves de los enlaces del correo encolado. @return list<string> */
    private function clavesDelCorreo(): array
    {
        $cuerpo = (string) $this->bd()->table('email_envios')->where('tipo', 'enlace-acceso')->value('texto');
        preg_match_all('#https://origamibogota\.com/acceso\?clave=([a-f0-9]{64})#', $cuerpo, $coincidencias);

        return $coincidencias[1];
    }

    #[Test]
    public function correo_no_inscrito_responde_igual_y_no_envia_nada(): void
    {
        $inscrito = $this->solicitar('familia@ejemplo.com');
        $noInscrito = $this->solicitar('nadie@ejemplo.com');

        self::assertSame(202, $inscrito->getStatusCode());
        self::assertSame(202, $noInscrito->getStatusCode());
        self::assertSame((string) $inscrito->getBody(), (string) $noInscrito->getBody());
        self::assertSame(1, $this->bd()->table('email_envios')->count());
    }

    #[Test]
    public function familia_recibe_un_solo_correo_con_un_enlace_por_participante(): void
    {
        $this->solicitar('FAMILIA@ejemplo.com');

        $envio = $this->bd()->table('email_envios')->first();
        self::assertSame('pending', $envio->estado);
        self::assertStringContainsString('Entrar como Marta Rojas', $envio->body);
        self::assertStringContainsString('Entrar como Tomás Rojas', $envio->body);
        self::assertStringNotContainsString('Borrado', $envio->body);
        self::assertCount(2, $this->clavesDelCorreo());
        self::assertSame(2, $this->bd()->table('enlaces_acceso')->count());
        self::assertSame(0, $this->bd()->table('enlaces_acceso')->whereIn('hash', $this->clavesDelCorreo())->count(), 'Las claves no se guardan en claro');
    }

    #[Test]
    public function canjear_entrega_un_token_de_asistente_para_ese_participante(): void
    {
        $this->solicitar('familia@ejemplo.com');
        [$claveMarta] = $this->clavesDelCorreo();

        $respuesta = $this->pedir('POST', '/auth/canjear-enlace', ['clave' => $claveMarta]);

        self::assertSame(200, $respuesta->getStatusCode());
        $cuerpo = $this->json($respuesta);
        self::assertSame(['id' => 1, 'name' => 'Marta Rojas'], $cuerpo['attendee']);
        self::assertSame('asistente', $this->bd()->table('tokens_acceso')->where('hash', hash('sha256', $cuerpo['token']))->value('tipo'));
    }

    #[Test]
    public function enlace_reutilizado_es_401(): void
    {
        $this->solicitar('familia@ejemplo.com');
        [$clave] = $this->clavesDelCorreo();

        self::assertSame(200, $this->pedir('POST', '/auth/canjear-enlace', ['clave' => $clave])->getStatusCode());
        self::assertSame(401, $this->pedir('POST', '/auth/canjear-enlace', ['clave' => $clave])->getStatusCode());
    }

    #[Test]
    public function enlace_caducado_es_401(): void
    {
        $this->reloj()->fijar('2027-06-05 08:00');
        $this->solicitar('familia@ejemplo.com');
        [$clave] = $this->clavesDelCorreo();

        $this->reloj()->avanzar('+31 minutes');
        self::assertSame(401, $this->pedir('POST', '/auth/canjear-enlace', ['clave' => $clave])->getStatusCode());
    }

    #[Test]
    public function clave_inventada_es_401(): void
    {
        self::assertSame(401, $this->pedir('POST', '/auth/canjear-enlace', ['clave' => str_repeat('b', 64)])->getStatusCode());
        self::assertSame(401, $this->pedir('POST', '/auth/canjear-enlace', ['clave' => "' OR 1=1 --"])->getStatusCode());
    }

    #[Test]
    public function cuarta_solicitud_en_una_hora_es_429(): void
    {
        foreach (range(1, 3) as $_) {
            self::assertSame(202, $this->solicitar('familia@ejemplo.com')->getStatusCode());
        }

        self::assertSame(429, $this->solicitar('familia@ejemplo.com')->getStatusCode());
        self::assertSame(3, $this->bd()->table('email_envios')->count());
    }

    #[Test]
    public function datos_del_asistente_salen_escapados_en_el_correo(): void
    {
        Attendee::query()->insert(['id' => 9, 'name' => '<script>alert(1)</script>', 'lastname' => 'X', 'age' => 30, 'email' => 'raro@ejemplo.com', 'phone' => '1', 'paydate' => '2027-03-01']);

        $this->solicitar('raro@ejemplo.com');

        $html = (string) $this->bd()->table('email_envios')->value('body');
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }
}
