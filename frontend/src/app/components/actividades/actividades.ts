import { Component } from '@angular/core';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import { Reveal } from '@core/reveal';
import { Separador } from '@components/separador/separador';
import { PapelPlegado } from '@components/papel-plegado/papel-plegado';
import actividades from '@contenido/actividades.json';
import { Actividad } from '@core/modelos';

/* ============ ACTIVIDADES (hero de sección) ============
   La home no lista todo: muestra las «destacadas» (contenido/actividades.json, 2–3)
   como fotos impresas pegadas con cinta, enteras y sin recortar, en una fila —carrusel
   en móvil— y cierra con una tarjeta «+N planes más» hacia la página de actividades.
   Sin foto, la tarjeta muestra el papel plegado. */

@Component({
  selector: 'app-actividades',
  imports: [Enlace, Separador, Reveal, PapelPlegado],
  templateUrl: './actividades.html',
  styleUrl: './actividades.css',
})
export class Actividades {
  private readonly contenido = traducir(actividades);
  protected readonly t = this.contenido.seccion;
  protected readonly categorias: Record<string, string> = this.contenido.categorias;
  /** En el orden de «destacadas»; un id que no exista se ignora. */
  protected readonly destacadas = this.contenido.destacadas
    .map((id) => (this.contenido.items as Actividad[]).find((a) => a.id === id))
    .filter((a): a is Actividad => !!a);
  protected readonly restantes = this.contenido.items.length - this.destacadas.length;
}
