<?php

declare(strict_types=1);

namespace App\Modulos\Autenticacion;

use App\Compartido\Errores\NoAutorizado;
use App\Compartido\Errores\Prohibido;
use Psr\Http\Message\ServerRequestInterface;

/** Quién hace la petición. El middleware `Autenticar` la deja en el atributo `sesion`. */
final readonly class Sesion
{
    public const string CMS = 'cms';
    public const string ASISTENTE = 'asistente';

    public function __construct(
        public string $tipo,
        public int $idSujeto,
        public int $idToken,
    ) {
    }

    public static function de(ServerRequestInterface $peticion): self
    {
        $sesion = $peticion->getAttribute('sesion');
        if (!$sesion instanceof self) {
            throw new NoAutorizado();
        }

        return $sesion;
    }

    public function esCms(): bool
    {
        return $this->tipo === self::CMS;
    }

    /** El CMS puede todo; un asistente, solo lo suyo. @throws Prohibido */
    public function exigirAsistenteOCms(int $idAttendee): void
    {
        if (!$this->esCms() && $this->idSujeto !== $idAttendee) {
            throw new Prohibido('Solo puede gestionar sus propios datos.');
        }
    }

    /** Rutas de un asistente sobre sí mismo (`/attendees/{id}/…`). @throws Prohibido */
    public function exigirAsistente(int $idAttendee): void
    {
        if ($this->tipo !== self::ASISTENTE || $this->idSujeto !== $idAttendee) {
            throw new Prohibido('Solo puede gestionar sus propios datos.');
        }
    }
}
