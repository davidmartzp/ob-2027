<?php

declare(strict_types=1);

namespace App\Modulos\Salud;

use Illuminate\Database\Connection;
use Throwable;

final readonly class ServicioSalud
{
    public function __construct(private Connection $bd)
    {
    }

    /** @return array{estado: string, cola_correo_minutos?: int|null} */
    public function estado(): array
    {
        try {
            $this->bd->select('SELECT 1');

            return ['estado' => 'ok', 'cola_correo_minutos' => $this->antiguedadCola()];
        } catch (Throwable $error) {
            error_log('[salud] Base de datos no disponible: ' . $error->getMessage());

            return ['estado' => 'sin_base_de_datos'];
        }
    }

    /** Minutos que lleva esperando el correo pendiente más viejo (null = cola vacía), para detectar un cron caído. */
    private function antiguedadCola(): ?int
    {
        if (!$this->bd->getSchemaBuilder()->hasTable('email_envios')) {
            return null;
        }
        $masViejo = $this->bd->table('email_envios')
            ->where('estado', 'pending')
            ->where('disponible_en', '<=', date('Y-m-d H:i:s'))
            ->min('disponible_en');

        return $masViejo === null ? null : intdiv(time() - strtotime((string) $masViejo), 60);
    }
}
