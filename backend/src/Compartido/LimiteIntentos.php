<?php

declare(strict_types=1);

namespace App\Compartido;

use App\Compartido\Errores\DemasiadosIntentos;
use Illuminate\Database\Connection;

/**
 * Límite de intentos con ventana deslizante, guardado en la tabla `intentos` (funciona en
 * un hosting compartido sin Redis). La clave dice qué se limita: `login:ip:1.2.3.4`.
 */
final readonly class LimiteIntentos
{
    public function __construct(private Connection $bd, private Reloj $reloj)
    {
    }

    /** @throws DemasiadosIntentos si ya hay `$maximo` intentos en los últimos `$minutos`. */
    public function exigirMenosDe(string $clave, int $maximo, int $minutos): void
    {
        $desde = $this->reloj->now()->modify("-{$minutos} minutes")->format('Y-m-d H:i:s');
        $usados = $this->bd->table('intentos')->where('clave', $clave)->where('ocurrido_en', '>', $desde)->count();
        if ($usados >= $maximo) {
            throw new DemasiadosIntentos();
        }
    }

    public function registrar(string $clave): void
    {
        $this->bd->table('intentos')->insert(['clave' => $clave, 'ocurrido_en' => $this->reloj->ahoraSql()]);
        // Limpieza perezosa: nada de lo que se limita mira más de un día atrás.
        if (random_int(1, 50) === 1) {
            $this->bd->table('intentos')->where('ocurrido_en', '<', $this->reloj->now()->modify('-1 day')->format('Y-m-d H:i:s'))->delete();
        }
    }

    public function limpiar(string $clave): void
    {
        $this->bd->table('intentos')->where('clave', $clave)->delete();
    }
}
