<?php

declare(strict_types=1);

namespace App\Modulos\Autenticacion;

use App\Compartido\Errores\NoAutorizado;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Exige `Authorization: Bearer <token>` vigente de alguno de los tipos indicados y deja la
 * `Sesion` en el atributo `sesion`. Un token de otro tipo cuenta como ausente (401).
 * En las rutas se usa a través de `Guardias` (`cms()`, `asistente()`, `cmsOAsistente()`).
 */
final readonly class Autenticar implements MiddlewareInterface
{
    /** @param list<string> $tipos */
    public function __construct(private ServicioTokens $tokens, private array $tipos)
    {
    }

    public function process(ServerRequestInterface $peticion, RequestHandlerInterface $manejador): ResponseInterface
    {
        $encabezado = $peticion->getHeaderLine('Authorization');
        $token = preg_match('/^Bearer\s+(\S+)$/i', $encabezado, $partes) === 1 ? $partes[1] : '';
        $sesion = $token === '' ? null : $this->tokens->resolver($token);

        if ($sesion === null || !in_array($sesion->tipo, $this->tipos, true)) {
            throw new NoAutorizado($token === '' ? 'Hace falta iniciar sesión.' : 'La sesión no es válida o caducó.');
        }

        return $manejador->handle($peticion->withAttribute('sesion', $sesion));
    }
}
