<?php

declare(strict_types=1);

namespace Pruebas;

use App\Compartido\BaseDatos\Migrador;
use Illuminate\Database\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Base de las pruebas funcionales: arranca la app real (config/arranque.php) sobre
 * SQLite en memoria, con todas las migraciones aplicadas.
 */
abstract class CasoFuncional extends TestCase
{
    protected App $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = self::crearApp($this->entorno());
        $this->app->getContainer()->get(Migrador::class)->migrar();
    }

    /** Variables de entorno extra para esta clase de pruebas. @return array<string, string> */
    protected function entorno(): array
    {
        return [];
    }

    /** @param array<string, string> $entorno */
    protected static function crearApp(array $entorno = []): App
    {
        return (require dirname(__DIR__) . '/config/arranque.php')($entorno);
    }

    protected function bd(): Connection
    {
        return $this->app->getContainer()->get(Connection::class);
    }

    /**
     * @param array<string, mixed>|string|null $cuerpo Array = JSON; string = cuerpo tal cual.
     * @param array<string, string>            $encabezados
     */
    protected function pedir(string $metodo, string $ruta, array|string|null $cuerpo = null, array $encabezados = [], string $ip = '203.0.113.10'): ResponseInterface
    {
        $peticion = (new ServerRequestFactory())->createServerRequest($metodo, $ruta, ['REMOTE_ADDR' => $ip]);
        if ($cuerpo !== null) {
            $encabezados += ['Content-Type' => 'application/json'];
            $peticion->getBody()->write(is_array($cuerpo) ? json_encode($cuerpo, JSON_THROW_ON_ERROR) : $cuerpo);
        }
        foreach ($encabezados as $nombre => $valor) {
            $peticion = $peticion->withHeader($nombre, $valor);
        }

        return $this->app->handle($peticion);
    }

    /** Encabezado de autorización para un token. @return array<string, string> */
    protected static function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    protected function reloj(): \App\Compartido\Reloj
    {
        return $this->app->getContainer()->get(\App\Compartido\Reloj::class);
    }

    /** @return array<mixed> */
    protected function json(ResponseInterface $respuesta): array
    {
        return json_decode((string) $respuesta->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }
}
