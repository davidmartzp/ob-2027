<?php

declare(strict_types=1);

namespace App\Modulos\Autenticacion;

use App\Compartido\Errores\NoAutorizado;
use App\Compartido\Errores\NoEncontrado;
use App\Compartido\LimiteIntentos;
use App\Compartido\Validador;
use DateTimeInterface;

/** Inicio y cierre de sesión del CMS. */
final readonly class ServicioAutenticacion
{
    private const int MAX_FALLOS = 5;
    private const int VENTANA_MINUTOS = 15;
    private const string CREDENCIALES_INCORRECTAS = 'Correo o contraseña incorrectos.';
    /** bcrypt de una clave aleatoria descartada: solo sirve para gastar el mismo tiempo. */
    private const string HASH_DE_RELLENO = '$2y$12$vP8D66lGDOFr.EZp4FrKuOqEnG0Gn.rDdRQF0dv80LMGw2dJF/eLq';

    public function __construct(private ServicioTokens $tokens, private LimiteIntentos $limite)
    {
    }

    /**
     * @param array<string, mixed> $cuerpo
     * @return array{access_token: string, token_type: string, expires_at: string, user: array{id: int, name: string, email: string}}
     */
    public function iniciar(array $cuerpo, string $ip): array
    {
        $datos = Validador::validar($cuerpo, ['email' => 'requerido|correo', 'password' => 'requerido|texto']);
        $claveCorreo = 'login:correo:' . $datos['email'];
        $claveIp = 'login:ip:' . $ip;

        // El límite se revisa antes de mirar la contraseña: pasado el tope, ni la correcta entra.
        $this->limite->exigirMenosDe($claveCorreo, self::MAX_FALLOS, self::VENTANA_MINUTOS);
        $this->limite->exigirMenosDe($claveIp, self::MAX_FALLOS, self::VENTANA_MINUTOS);

        $usuario = Usuario::query()->where('email', $datos['email'])->first();
        // Con un correo inexistente también se verifica un hash, para que el tiempo de respuesta no lo delate.
        $hash = $usuario?->password ?? self::HASH_DE_RELLENO;
        if (!password_verify($datos['password'], $hash) || $usuario === null) {
            $this->limite->registrar($claveCorreo);
            $this->limite->registrar($claveIp);
            throw new NoAutorizado(self::CREDENCIALES_INCORRECTAS);
        }

        $this->limite->limpiar($claveCorreo);
        if (password_needs_rehash($usuario->password, ServicioUsuarios::algoritmo())) {
            $usuario->password = ServicioUsuarios::hash($datos['password']);
            $usuario->save();
        }

        $emitido = $this->tokens->emitir(Sesion::CMS, $usuario->id);

        return [
            'access_token' => $emitido['token'],
            'token_type' => 'Bearer',
            'expires_at' => $emitido['caduca_en']->format(DateTimeInterface::ATOM),
            'user' => $usuario->publico(),
        ];
    }

    public function cerrar(Sesion $sesion): void
    {
        $this->tokens->revocar($sesion);
    }

    /** @return array{id: int, name: string, email: string} */
    public function usuario(Sesion $sesion): array
    {
        $usuario = Usuario::query()->find($sesion->idSujeto) ?? throw new NoEncontrado('El usuario ya no existe.');

        return $usuario->publico();
    }
}
