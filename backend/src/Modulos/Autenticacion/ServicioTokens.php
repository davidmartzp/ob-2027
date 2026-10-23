<?php

declare(strict_types=1);

namespace App\Modulos\Autenticacion;

use App\Compartido\Ajustes;
use App\Compartido\Reloj;
use DateTimeImmutable;
use Illuminate\Database\Connection;

/**
 * Tokens opacos: 32 bytes aleatorios que se entregan una sola vez; en la base solo queda
 * su SHA-256. Caducan (CMS: TOKEN_CMS_HORAS, asistente: TOKEN_ASISTENTE_HORAS) y se revocan.
 */
final readonly class ServicioTokens
{
    public function __construct(private Connection $bd, private Reloj $reloj, private Ajustes $ajustes)
    {
    }

    /** @return array{token: string, caduca_en: DateTimeImmutable} */
    public function emitir(string $tipo, int $idSujeto): array
    {
        $token = bin2hex(random_bytes(32));
        $horas = $tipo === Sesion::CMS
            ? $this->ajustes->entero('TOKEN_CMS_HORAS', 12)
            : $this->ajustes->entero('TOKEN_ASISTENTE_HORAS', 2);
        $caduca = $this->reloj->now()->modify("+{$horas} hours");

        $this->bd->table('tokens_acceso')->insert([
            'tipo' => $tipo,
            'id_sujeto' => $idSujeto,
            'hash' => hash('sha256', $token),
            'caduca_en' => $caduca->format('Y-m-d H:i:s'),
            'creado_en' => $this->reloj->ahoraSql(),
        ]);

        return ['token' => $token, 'caduca_en' => $caduca];
    }

    /** La sesión de un token vigente, o null si no existe, caducó o fue revocado. */
    public function resolver(string $token): ?Sesion
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $fila = $this->bd->table('tokens_acceso')
            ->where('hash', hash('sha256', $token))
            ->whereNull('revocado_en')
            ->where('caduca_en', '>', $this->reloj->ahoraSql())
            ->first();
        if ($fila === null) {
            return null;
        }
        $this->bd->table('tokens_acceso')->where('id', $fila->id)->update(['ultimo_uso_en' => $this->reloj->ahoraSql()]);

        return new Sesion($fila->tipo, (int) $fila->id_sujeto, (int) $fila->id);
    }

    public function revocar(Sesion $sesion): void
    {
        $this->bd->table('tokens_acceso')->where('id', $sesion->idToken)->update(['revocado_en' => $this->reloj->ahoraSql()]);
    }

    /** Al cambiar la contraseña de un usuario se cierran todas sus sesiones. */
    public function revocarTodos(string $tipo, int $idSujeto): void
    {
        $this->bd->table('tokens_acceso')
            ->where('tipo', $tipo)
            ->where('id_sujeto', $idSujeto)
            ->whereNull('revocado_en')
            ->update(['revocado_en' => $this->reloj->ahoraSql()]);
    }
}
