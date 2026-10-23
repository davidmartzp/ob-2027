<?php

declare(strict_types=1);

namespace App\Modulos\Autenticacion;

/** Middleware de autenticación listos para las rutas: `->add($this->guardias->cms())`. */
final readonly class Guardias
{
    public function __construct(private ServicioTokens $tokens)
    {
    }

    public function cms(): Autenticar
    {
        return new Autenticar($this->tokens, [Sesion::CMS]);
    }

    public function asistente(): Autenticar
    {
        return new Autenticar($this->tokens, [Sesion::ASISTENTE]);
    }

    /** Rutas que usan el CMS y el asistente dueño del recurso; el permiso fino lo revisa el servicio. */
    public function cmsOAsistente(): Autenticar
    {
        return new Autenticar($this->tokens, [Sesion::CMS, Sesion::ASISTENTE]);
    }
}
