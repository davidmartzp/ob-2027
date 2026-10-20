import { Component, input } from '@angular/core';
import { Doblez } from '@components/doblez/doblez';
import { seccionVisible } from '@core/navegacion';
import { Hero } from '@components/hero/hero';
import { Patrocinadores } from '@components/patrocinadores/patrocinadores';

/* ============ MARCO DE PÁGINA INTERNA ============
   Toda página que no es la home: el hero del evento (toro y título) arriba, un doblez
   con el color de la cabecera propia de la página, el contenido y los patrocinadores al final.
   Cabecera del sitio, leyenda y pie vienen de app.html.
   Uso: <app-pagina-interna fondo="var(--frailejon)"> <section …>…</section> </app-pagina-interna> */

@Component({
  selector: 'app-pagina-interna',
  imports: [Doblez, Hero, Patrocinadores],
  template: `
    <main>
      <app-hero/>
      <app-doblez [fondo]="fondo()" montana/>
      <ng-content/>
      @if (patrocinadoresVisibles) {
        <app-patrocinadores/>
      }
    </main>
  `,
})
export class PaginaInterna {
  /** Fondo de la primera sección de la página (lo toma el doblez de arriba). */
  readonly fondo = input.required<string>();
  protected readonly patrocinadoresVisibles = seccionVisible('patrocinadores');
}
