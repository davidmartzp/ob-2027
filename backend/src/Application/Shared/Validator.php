<?php

declare(strict_types=1);

namespace App\Application\Shared;

use App\Domain\Shared\Exception\ValidationException;
use DateTimeImmutable;
use LogicException;

/**
 * Validates request input with string rules, e.g. `'required|int:1,120'`.
 *
 * Rules: required, string, email, int[:min,max], decimal, bool, in:a,b,c, max:n,
 * time (HH:MM or HH:MM:SS), date (YYYY-MM-DD, optional time), list.
 * An optional field that arrives empty becomes `null` and is not checked further.
 * Returns only the declared fields, cast to their type. Messages are in Spanish (user-facing).
 */
final class Validator
{
    /**
     * @param array<string, mixed>  $input
     * @param array<string, string> $rules
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public static function validate(array $input, array $rules): array
    {
        $clean = [];
        $errors = [];

        foreach ($rules as $field => $definition) {
            $value = $input[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $ruleList = explode('|', $definition);

            if ($value === null || $value === '' || $value === []) {
                if (in_array('required', $ruleList, true)) {
                    $errors[$field] = 'Este campo es obligatorio.';
                } else {
                    $clean[$field] = null;
                }
                continue;
            }

            foreach ($ruleList as $rule) {
                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                $result = self::apply($name, $parameter, $value);
                if (is_string($result)) {
                    $errors[$field] = $result;
                    continue 2;
                }
                $value = $result[0];
            }
            $clean[$field] = $value;
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $clean;
    }

    /** @return array{0: mixed}|string The cast value, or the error message. */
    private static function apply(string $rule, ?string $parameter, mixed $value): array|string
    {
        return match ($rule) {
            'required' => [$value],
            'string' => is_scalar($value) ? [(string) $value] : 'Debe ser un texto.',
            'email' => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false
                ? [mb_strtolower($value)]
                : 'Escriba un correo válido.',
            'int' => self::integer($value, $parameter),
            'decimal' => is_numeric($value) ? [(float) $value] : 'Debe ser un número.',
            'bool' => self::boolean($value),
            'in' => in_array((string) $value, explode(',', (string) $parameter), true)
                ? [(string) $value]
                : 'Valor no permitido.',
            'max' => is_string($value) && mb_strlen($value) > (int) $parameter
                ? "Máximo {$parameter} caracteres."
                : [$value],
            'time' => is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value)
                ? [strlen($value) === 5 ? $value . ':00' : $value]
                : 'Escriba una hora válida (HH:MM).',
            'date' => self::date($value),
            'list' => is_array($value) && array_is_list($value) ? [$value] : 'Debe ser una lista.',
            default => throw new LogicException("Unknown validation rule: {$rule}"),
        };
    }

    /** @return array{0: int}|string */
    private static function integer(mixed $value, ?string $range): array|string
    {
        if (!is_int($value) && !(is_string($value) && preg_match('/^-?\d+$/', $value))) {
            return 'Debe ser un número entero.';
        }
        $number = (int) $value;
        if ($range !== null) {
            [$min, $max] = array_map('intval', explode(',', $range));
            if ($number < $min || $number > $max) {
                return "Debe estar entre {$min} y {$max}.";
            }
        }

        return [$number];
    }

    /** @return array{0: bool}|string */
    private static function boolean(mixed $value): array|string
    {
        $result = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $result === null ? 'Debe ser verdadero o falso.' : [$result];
    }

    /** @return array{0: string}|string */
    private static function date(mixed $value): array|string
    {
        if (is_string($value)) {
            foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $format) {
                $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
                if ($date !== false && $date->format($format) === $value) {
                    return [$date->format('Y-m-d H:i:s')];
                }
            }
        }

        return 'Escriba una fecha válida (AAAA-MM-DD).';
    }
}
