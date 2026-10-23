import { Component } from '@angular/core';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import { Patrocinador } from '@core/modelos';
import { Separador } from '@components/separador/separador';
import { Reveal } from '@core/reveal';
import patrocinadores from '@contenido/patrocinadores.json';

/* ============ PATROCINADORES ============
   La lista está en contenido/patrocinadores.json: { "nombre": …, "url": … } por
   patrocinador (url opcional: con ella, el nombre enlaza a su web o red, en otra pestaña;
   el nombre puede ser { "es": …, "en": … } si se traduce). */

@Component({
  selector: 'app-patrocinadores',
  imports: [Enlace, Separador, Reveal],
  styleUrl: './patrocinadores.css',
  template: `
    <section class="patrocinadores" id="patrocinadores">
      <div class="centro">
        <app-separador class="centrado" appReveal/>
        <h2 class="titulo rico" appReveal="1" [innerHTML]="t.titulo"></h2>
      </div>
      <div class="patros" appReveal="2">
        @for (p of lista; track p.nombre) {
          @if (p.url) {
            <a [appEnlace]="p.url">{{ p.nombre }}</a>
          } @else {
            <span>{{ p.nombre }}</span>
          }
        }
      </div>
    </section>
  `,
})
export class Patrocinadores {
  protected readonly t = traducir(patrocinadores);
  protected readonly lista = this.t.lista as Patrocinador[];
}
