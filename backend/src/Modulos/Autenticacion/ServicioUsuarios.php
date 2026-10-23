<?php

declare(strict_types=1);

namespace App\Modulos\Autenticacion;

use App\Compartido\Errores\Conflicto;
use App\Compartido\Errores\NoEncontrado;
use App\Compartido\Errores\Validacion;
use App\Compartido\Validador;

/** Alta y cambio de contraseña de usuarios del CMS. Solo desde la consola: la API no registra usuarios. */
final readonly class ServicioUsuarios
{
    public const int LONGITUD_MINIMA = 10;

    public function __construct(private ServicioTokens $tokens)
    {
    }

    public function crear(string $email, string $nombre, string $clave): Usuario
    {
        $datos = Validador::validar(['email' => $email, 'name' => $nombre, 'clave' => $clave], [
            'email' => 'requerido|correo|max:255',
            'name' => 'requerido|texto|max:255',
            'clave' => 'requerido|texto',
        ]);
        $this->exigirClaveSegura($clave);
        if (Usuario::query()->where('email', $datos['email'])->exists()) {
            throw new Conflicto("Ya existe un usuario con el correo {$datos['email']}.");
        }

        return Usuario::query()->create([
            'email' => $datos['email'],
            'name' => $datos['name'],
            'password' => self::hash($clave),
        ]);
    }

    /** Cambia la contraseña y cierra todas las sesiones abiertas de ese usuario. */
    public function cambiarClave(string $email, string $clave): Usuario
    {
        $this->exigirClaveSegura($clave);
        $usuario = Usuario::query()->where('email', mb_strtolower(trim($email)))->first()
            ?? throw new NoEncontrado("No hay ningún usuario con el correo {$email}.");
        $usuario->password = self::hash($clave);
        $usuario->save();
        $this->tokens->revocarTodos(Sesion::CMS, $usuario->id);

        return $usuario;
    }

    /** Argon2id cuando el PHP del servidor lo trae; si no, bcrypt (lo que ya usa la base heredada). */
    public static function hash(string $clave): string
    {
        return password_hash($clave, self::algoritmo());
    }

    public static function algoritmo(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    private function exigirClaveSegura(string $clave): void
    {
        if (mb_strlen($clave) < self::LONGITUD_MINIMA) {
            throw new Validacion(['clave' => 'La contraseña debe tener al menos ' . self::LONGITUD_MINIMA . ' caracteres.']);
        }
    }
}
