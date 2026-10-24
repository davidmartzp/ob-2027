import { Component, input } from '@angular/core';
import comun from '@contenido/comun.json';
import { Enlace } from '@core/enlace';
import { rellenar, traducir } from '@core/idioma';
import { Socio } from '@core/modelos';

/* ============ SOCIOS DE UN TALLER ============
   Logo (o nombre) de cada socio y su botón de WhatsApp (whatsapp_url del backend, abre en
   otra pestaña vía appEnlace). Sin socios no pinta nada.
   Uso: <app-socios-taller [socios]="taller.socios"/> */

@Component({
  selector: 'app-socios-taller',
  imports: [Enlace],
  styleUrl: './socios-taller.css',
  template: `
    @if (socios().length) {
      <p class="etiqueta">{{ t.socios }}</p>
      <ul>
        @for (s of socios(); track s.id) {
          <li>
            @if (s.logo) {
              <img [src]="s.logo" [alt]="s.name" loading="lazy" width="96" height="48">
            } @else {
              <span class="nombre">{{ s.name }}</span>
            }
            <a class="whatsapp" [appEnlace]="s.whatsapp_url" [attr.aria-label]="aria(s)">WhatsApp ↗</a>
          </li>
        }
      </ul>
    }
  `,
})
export class SociosTaller {
  readonly socios = input.required<Socio[]>();
  protected readonly t = traducir(comun).taller;

  protected aria(s: Socio): string {
    return rellenar(this.t.whatsapp, { nombre: s.name });
  }
}
