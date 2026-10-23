<?php

declare(strict_types=1);

namespace App\Compartido;

use App\Compartido\Errores\Validacion;
use DateTimeImmutable;

/**
 * Validación de cuerpos de petición con reglas en texto, p. ej. `'requerido|entero:1,120'`.
 *
 * Reglas: requerido, texto, correo, entero[:min,max], decimal, booleano, en:a,b,c,
 * max:n, hora (HH:MM o HH:MM:SS), fecha (AAAA-MM-DD, con hora opcional), lista.
 * Un campo sin `requerido` que llega vacío vale `null` y no se valida más.
 * Devuelve solo los campos declarados, ya convertidos a su tipo.
 */
final class Validador
{
    /**
     * @param array<string, mixed>  $datos
     * @param array<string, string> $reglas
     * @return array<string, mixed>
     * @throws Validacion
     */
    public static function validar(array $datos, array $reglas): array
    {
        $limpios = [];
        $errores = [];

        foreach ($reglas as $campo => $definicion) {
            $valor = $datos[$campo] ?? null;
            if (is_string($valor)) {
                $valor = trim($valor);
            }
            $vacio = $valor === null || $valor === '' || $valor === [];
            $lista = explode('|', $definicion);

            if ($vacio) {
                if (in_array('requerido', $lista, true)) {
                    $errores[$campo] = 'Este campo es obligatorio.';
                } else {
                    $limpios[$campo] = null;
                }
                continue;
            }

            foreach ($lista as $regla) {
                [$nombre, $parametro] = array_pad(explode(':', $regla, 2), 2, null);
                $resultado = self::aplicar($nombre, $parametro, $valor);
                if (is_string($resultado)) {
                    $errores[$campo] = $resultado;
                    continue 2;
                }
                $valor = $resultado[0];
            }
            $limpios[$campo] = $valor;
        }

        if ($errores !== []) {
            throw new Validacion($errores);
        }

        return $limpios;
    }

    /** @return array{0: mixed}|string El valor convertido, o el mensaje de error. */
    private static function aplicar(string $regla, ?string $parametro, mixed $valor): array|string
    {
        return match ($regla) {
            'requerido' => [$valor],
            'texto' => is_scalar($valor) ? [(string) $valor] : 'Debe ser un texto.',
            'correo' => is_string($valor) && filter_var($valor, FILTER_VALIDATE_EMAIL) !== false
                ? [mb_strtolower($valor)]
                : 'Escriba un correo válido.',
            'entero' => self::entero($valor, $parametro),
            'decimal' => is_numeric($valor) ? [(float) $valor] : 'Debe ser un número.',
            'booleano' => self::booleano($valor),
            'en' => in_array((string) $valor, explode(',', (string) $parametro), true)
                ? [(string) $valor]
                : 'Valor no permitido.',
            'max' => is_string($valor) && mb_strlen($valor) > (int) $parametro
                ? "Máximo {$parametro} caracteres."
                : [$valor],
            'hora' => is_string($valor) && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $valor)
                ? [strlen($valor) === 5 ? $valor . ':00' : $valor]
                : 'Escriba una hora válida (HH:MM).',
            'fecha' => self::fecha($valor),
            'lista' => is_array($valor) && array_is_list($valor) ? [$valor] : 'Debe ser una lista.',
            default => throw new \LogicException("Regla de validación desconocida: {$regla}"),
        };
    }

    /** @return array{0: int}|string */
    private static function entero(mixed $valor, ?string $rango): array|string
    {
        if (!is_int($valor) && !(is_string($valor) && preg_match('/^-?\d+$/', $valor))) {
            return 'Debe ser un número entero.';
        }
        $numero = (int) $valor;
        if ($rango !== null) {
            [$minimo, $maximo] = array_map('intval', explode(',', $rango));
            if ($numero < $minimo || $numero > $maximo) {
                return "Debe estar entre {$minimo} y {$maximo}.";
            }
        }

        return [$numero];
    }

    /** @return array{0: bool}|string */
    private static function booleano(mixed $valor): array|string
    {
        $resultado = filter_var($valor, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $resultado === null ? 'Debe ser verdadero o falso.' : [$resultado];
    }

    /** @return array{0: string}|string */
    private static function fecha(mixed $valor): array|string
    {
        if (is_string($valor)) {
            foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $formato) {
                $fecha = DateTimeImmutable::createFromFormat('!' . $formato, $valor);
                if ($fecha !== false && $fecha->format($formato) === $valor) {
                    return [$fecha->format('Y-m-d H:i:s')];
                }
            }
        }

        return 'Escriba una fecha válida (AAAA-MM-DD).';
    }
}
