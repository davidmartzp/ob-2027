<?php

declare(strict_types=1);

namespace App\Modulos\Autenticacion;

use App\Compartido\Ajustes;
use App\Compartido\Errores\NoAutorizado;
use App\Compartido\LimiteIntentos;
use App\Compartido\Reloj;
use App\Compartido\Validador;
use App\Modulos\Attendees\Attendee;
use App\Modulos\Correo\ColaCorreo;
use DateTimeInterface;
use Illuminate\Database\Connection;

/**
 * Acceso de un asistente sin número de participante: pide un enlace con su correo y lo
 * canjea por un token de asistente. Un mismo correo puede tener varios participantes
 * (familias): se envía un solo correo con un enlace por participante.
 */
final readonly class ServicioEnlaces
{
    private const int MAX_SOLICITUDES_POR_HORA = 3;

    public function __construct(
        private Connection $bd,
        private Reloj $reloj,
        private Ajustes $ajustes,
        private LimiteIntentos $limite,
        private ColaCorreo $correo,
        private ServicioTokens $tokens,
    ) {
    }

    /** Siempre termina igual, exista o no el correo, para no revelar quién está inscrito. */
    public function solicitar(array $cuerpo): void
    {
        $email = Validador::validar($cuerpo, ['email' => 'requerido|correo|max:255'])['email'];
        $clave = 'enlace:correo:' . $email;
        $this->limite->exigirMenosDe($clave, self::MAX_SOLICITUDES_POR_HORA, 60);
        $this->limite->registrar($clave);

        $asistentes = Attendee::query()->vigentes()->whereRaw('LOWER(email) = ?', [$email])->orderBy('id')->get();
        if ($asistentes->isEmpty()) {
            return;
        }

        $minutos = $this->ajustes->entero('ENLACE_ACCESO_MINUTOS', 30);
        $caduca = $this->reloj->now()->modify("+{$minutos} minutes")->format('Y-m-d H:i:s');
        $base = rtrim($this->ajustes->texto('SITIO_URL'), '/') . '/' . ltrim($this->ajustes->texto('ENLACE_ACCESO_RUTA', 'acceso'), '/');

        $participantes = [];
        foreach ($asistentes as $asistente) {
            $secreto = bin2hex(random_bytes(32));
            $this->bd->table('enlaces_acceso')->insert([
                'hash' => hash('sha256', $secreto),
                'id_attendee' => $asistente->id,
                'caduca_en' => $caduca,
                'creado_en' => $this->reloj->ahoraSql(),
            ]);
            $participantes[] = ['nombre' => $asistente->nombreCompleto(), 'url' => $base . '?clave=' . $secreto];
        }

        $this->correo->encolar(
            'enlace-acceso',
            $asistentes->first()->email,
            'Tu enlace para entrar a Origami Bogotá',
            'enlace-acceso',
            ['participantes' => $participantes, 'minutos' => $minutos],
        );
    }

    /** @return array{token: string, token_type: string, expires_at: string, attendee: array{id: int, name: string}} */
    public function canjear(array $cuerpo): array
    {
        $secreto = (string) ($cuerpo['clave'] ?? '');
        $invalido = new NoAutorizado('El enlace no es válido, caducó o ya se usó. Pide uno nuevo.');
        if (!preg_match('/^[a-f0-9]{64}$/', $secreto)) {
            throw $invalido;
        }

        $ahora = $this->reloj->ahoraSql();
        $enlace = $this->bd->table('enlaces_acceso')
            ->where('hash', hash('sha256', $secreto))
            ->whereNull('usado_en')
            ->where('caduca_en', '>', $ahora)
            ->first();
        // Marcarlo usado con la condición `usado_en IS NULL` evita que dos canjes simultáneos ganen ambos.
        $marcados = $enlace === null ? 0 : $this->bd->table('enlaces_acceso')
            ->where('id', $enlace->id)
            ->whereNull('usado_en')
            ->update(['usado_en' => $ahora]);
        $asistente = $marcados === 1 ? Attendee::query()->vigentes()->find($enlace->id_attendee) : null;
        if ($asistente === null) {
            throw $invalido;
        }

        $emitido = $this->tokens->emitir(Sesion::ASISTENTE, $asistente->id);

        return [
            'token' => $emitido['token'],
            'token_type' => 'Bearer',
            'expires_at' => $emitido['caduca_en']->format(DateTimeInterface::ATOM),
            'attendee' => ['id' => $asistente->id, 'name' => $asistente->nombreCompleto()],
        ];
    }
}
