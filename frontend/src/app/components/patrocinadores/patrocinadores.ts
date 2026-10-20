import { Component } from '@angular/core';
import { traducir } from '@core/idioma';
import { Separador } from '@components/separador/separador';
import { Reveal } from '@core/reveal';
import patrocinadores from '@contenido/patrocinadores.json';

/* ============ PATROCINADORES ============
   La lista está en contenido/patrocinadores.json: un nombre por patrocinador, o
   { "es": …, "en": … } si el nombre se traduce. */

@Component({
  selector: 'app-patrocinadores',
  imports: [Separador, Reveal],
  styleUrl: './patrocinadores.css',
  template: `
    <section class="patrocinadores" id="patrocinadores">
      <div class="centro">
        <app-separador class="centrado" appReveal/>
        <h2 class="titulo rico" appReveal="1" [innerHTML]="t.titulo"></h2>
      </div>
      <div class="patros" appReveal="2">
        @for (p of t.lista; track p) {
          <span>{{ p }}</span>
        }
      </div>
    </section>
  `,
})
export class Patrocinadores {
  protected readonly t = traducir(patrocinadores);
}
