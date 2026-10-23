<?php

declare(strict_types=1);

namespace App\Compartido\BaseDatos;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use RuntimeException;

/**
 * Aplica y revierte migraciones por lotes. Lleva el registro en la tabla `migraciones`
 * (distinta de `migrations`, la de Laravel en la base heredada).
 */
final readonly class Migrador
{
    private const string TABLA = 'migraciones';

    public function __construct(private Connection $bd, private string $directorio)
    {
    }

    /** @return list<string> Migraciones aplicadas en este lote. */
    public function migrar(): array
    {
        $this->asegurarRegistro();
        $pendientes = array_diff($this->archivos(), $this->aplicadas());
        if ($pendientes === []) {
            return [];
        }

        $lote = (int) $this->bd->table(self::TABLA)->max('lote') + 1;
        foreach ($pendientes as $nombre) {
            $this->cargar($nombre)->subir($this->bd->getSchemaBuilder());
            $this->bd->table(self::TABLA)->insert([
                'migracion' => $nombre,
                'lote' => $lote,
                'aplicada_en' => date('Y-m-d H:i:s'),
            ]);
        }

        return array_values($pendientes);
    }

    /** @return list<string> Migraciones revertidas (las del último lote, en orden inverso). */
    public function revertir(): array
    {
        $this->asegurarRegistro();
        $lote = $this->bd->table(self::TABLA)->max('lote');
        if ($lote === null) {
            return [];
        }

        $nombres = $this->bd->table(self::TABLA)
            ->where('lote', $lote)
            ->orderByDesc('migracion')
            ->pluck('migracion')
            ->all();
        foreach ($nombres as $nombre) {
            $this->cargar($nombre)->bajar($this->bd->getSchemaBuilder());
            $this->bd->table(self::TABLA)->where('migracion', $nombre)->delete();
        }

        return $nombres;
    }

    /** @return list<string> */
    public function pendientes(): array
    {
        $this->asegurarRegistro();

        return array_values(array_diff($this->archivos(), $this->aplicadas()));
    }

    private function asegurarRegistro(): void
    {
        $esquema = $this->bd->getSchemaBuilder();
        if (!$esquema->hasTable(self::TABLA)) {
            $esquema->create(self::TABLA, static function (Blueprint $tabla): void {
                $tabla->increments('id');
                $tabla->string('migracion')->unique();
                $tabla->unsignedInteger('lote');
                $tabla->dateTime('aplicada_en');
            });
        }
    }

    /** @return list<string> Nombres de archivo sin extensión, en orden. */
    private function archivos(): array
    {
        $archivos = array_map(
            static fn (string $ruta): string => basename($ruta, '.php'),
            glob($this->directorio . '/*.php') ?: [],
        );
        sort($archivos);

        return $archivos;
    }

    /** @return list<string> */
    private function aplicadas(): array
    {
        return $this->bd->table(self::TABLA)->pluck('migracion')->all();
    }

    private function cargar(string $nombre): Migracion
    {
        $migracion = require $this->directorio . '/' . $nombre . '.php';
        if (!$migracion instanceof Migracion) {
            throw new RuntimeException("La migración {$nombre} debe devolver un objeto Migracion.");
        }

        return $migracion;
    }
}
