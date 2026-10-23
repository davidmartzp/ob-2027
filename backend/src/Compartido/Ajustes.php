<?php

declare(strict_types=1);

namespace App\Compartido;

use RuntimeException;

/**
 * Configuración leída del entorno (.env o variables del sistema). Inmutable.
 * Un valor vacío cuenta como ausente.
 */
final readonly class Ajustes
{
    /** @param array<string, string> $valores */
    public function __construct(private array $valores)
    {
    }

    /**
     * Toma el entorno del proceso, aplica `$sobrescribir` (pruebas) y comprueba las variables obligatorias.
     *
     * @param array<string, string> $sobrescribir
     */
    public static function desdeEntorno(array $sobrescribir = []): self
    {
        $valores = [];
        foreach ([getenv(), $_SERVER, $_ENV, $sobrescribir] as $origen) {
            foreach ($origen as $clave => $valor) {
                if (is_string($clave) && is_scalar($valor)) {
                    $valores[$clave] = (string) $valor;
                }
            }
        }

        $ajustes = new self($valores);
        $ajustes->exigir(['DB_DATABASE']);
        if ($ajustes->texto('DB_CONNECTION', 'mariadb') !== 'sqlite') {
            $ajustes->exigir(['DB_HOST', 'DB_USERNAME']);
        }

        return $ajustes;
    }

    /** @param list<string> $claves */
    private function exigir(array $claves): void
    {
        $faltan = array_values(array_filter($claves, fn (string $clave) => $this->texto($clave) === ''));
        if ($faltan !== []) {
            throw new RuntimeException('Falta configurar en .env: ' . implode(', ', $faltan));
        }
    }

    public function texto(string $clave, string $porDefecto = ''): string
    {
        $valor = trim($this->valores[$clave] ?? '');

        return $valor === '' ? $porDefecto : $valor;
    }

    public function entero(string $clave, int $porDefecto): int
    {
        $valor = $this->texto($clave);

        return is_numeric($valor) ? (int) $valor : $porDefecto;
    }

    public function booleano(string $clave, bool $porDefecto = false): bool
    {
        $valor = strtolower($this->texto($clave));

        return $valor === '' ? $porDefecto : in_array($valor, ['1', 'true', 'on', 'yes'], true);
    }

    /** @return list<string> Valores separados por comas, sin vacíos. */
    public function lista(string $clave): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->texto($clave))), 'strlen'));
    }

    public function enProduccion(): bool
    {
        return $this->texto('APP_ENV', 'production') === 'production';
    }

    public function depurar(): bool
    {
        return $this->booleano('APP_DEBUG');
    }
}
