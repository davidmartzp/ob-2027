<?php

declare(strict_types=1);

namespace App\Modulos\Correo;

use App\Compartido\Reloj;
use Illuminate\Database\Connection;
use Illuminate\Database\UniqueConstraintViolationException;
use Twig\Environment;

/**
 * Pone correos en la cola `email_envios`; los despacha `bin/consola correo:procesar`.
 * Las plantillas viven en `plantillas/correos/<nombre>.html.twig` (+ `.txt.twig`) y Twig
 * escapa todo dato insertado.
 */
final readonly class ColaCorreo
{
    public function __construct(private Connection $bd, private Environment $twig, private Reloj $reloj)
    {
    }

    /**
     * @param array<string, mixed> $datos      Variables de la plantilla.
     * @param string|null          $claveUnica Evita encolar dos veces lo mismo (p. ej. "instrucciones:27").
     * @return int|null Id del envío, o null si ya estaba encolado con esa clave.
     */
    public function encolar(
        string $tipo,
        string $email,
        string $asunto,
        string $plantilla,
        array $datos = [],
        ?int $idAttendee = null,
        ?string $claveUnica = null,
    ): ?int {
        $ahora = $this->reloj->ahoraSql();
        try {
            return (int) $this->bd->table('email_envios')->insertGetId([
                'tipo' => $tipo,
                'id_attendee' => $idAttendee,
                'email' => $email,
                // Un asunto nunca lleva saltos de línea (inyección de encabezados).
                'subject' => trim((string) preg_replace('/[\r\n]+/', ' ', $asunto)),
                'body' => $this->twig->render("correos/{$plantilla}.html.twig", $datos),
                'texto' => $this->twig->render("correos/{$plantilla}.txt.twig", $datos),
                'estado' => 'pending',
                'intentos' => 0,
                'disponible_en' => $ahora,
                'clave_unica' => $claveUnica,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }
}
