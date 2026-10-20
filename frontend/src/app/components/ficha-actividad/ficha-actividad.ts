import { Component, input } from '@angular/core';
import actividades from '@contenido/actividades.json';
import { Actividad } from '@core/modelos';
import { Enlace } from '@core/enlace';
import { ResumenActividad } from '@components/resumen-actividad/resumen-actividad';

/* ============ FICHA DE ACTIVIDAD ============
   <details> nativo: abre/cierra con teclado sin JS y todo el texto sale en el
   prerender. name="actividad" = acordeón de una sola ficha abierta.
   La fila (summary) es el ResumenActividad que también usa la home.
   Se monta sobre el propio <li> (id = ancla, actividades#chocogami).
   Uso: <li appFicha [actividad]="a" [id]="a.id"></li> */

@Component({
  selector: 'li[appFicha]',
  imports: [Enlace, ResumenActividad],
  styleUrl: './ficha-actividad.css',
  template: `
    <details name="actividad">
      <summary [appResumenActividad]="actividad()"></summary>
      <div class="detalle">
        <div class="prosa">
          @for (p of actividad().parrafos; track $index) {
            <p>{{ p }}</p>
          }
          @if (actividad().nota) {
            <p class="pend-nota">{{ actividad().nota }}</p>
          }
          @if (actividad().enlaces?.length) {
            <ul class="enlaces">
              @for (e of actividad().enlaces; track e.href) {
                <li><a [appEnlace]="e.href">{{ e.texto }}</a></li>
              }
            </ul>
          }
        </div>
        <figure>
          @if (actividad().foto; as foto) {
            <img [src]="foto" alt="" loading="lazy">
          } @else {
            <img class="logo" [src]="logo" alt="" loading="lazy" width="100" height="100">
          }
        </figure>
      </div>
    </details>
  `,
})
export class FichaActividad {
  readonly actividad = input.required<Actividad>();
  protected readonly logo = actividades.logoSinFoto;
}
