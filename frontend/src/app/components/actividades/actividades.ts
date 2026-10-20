import { Component } from '@angular/core';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import { Reveal } from '@core/reveal';
import { Separador } from '@components/separador/separador';
import { ActividadItem } from '@components/actividad-item/actividad-item';
import actividades from '@contenido/actividades.json';
import { Actividad } from '@core/modelos';

/* ============ ACTIVIDADES (hero de sección) ============
   La home no lista todo: invita a la página de actividades (buscador y fichas)
   y destaca unas pocas («destacadas» en contenido/actividades.json). */

@Component({
  selector: 'app-actividades',
  imports: [Enlace, Separador, Reveal, ActividadItem],
  templateUrl: './actividades.html',
  styleUrl: './actividades.css',
})
export class Actividades {
  private readonly contenido = traducir(actividades);
  protected readonly t = this.contenido.seccion;
  /** En el orden de «destacadas»; un id que no exista se ignora. */
  protected readonly destacadas = this.contenido.destacadas
    .map((id) => (this.contenido.items as Actividad[]).find((a) => a.id === id))
    .filter((a): a is Actividad => !!a);
}
