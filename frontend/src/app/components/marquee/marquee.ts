import { Component } from '@angular/core';
import { traducir } from '@core/idioma';
import contenido from '@contenido/marquee.json';

/* ============ MARQUEE — faja de ruana tejida ============
   La pista repite las frases dos veces para que el bucle sea continuo.
   Frases en contenido/marquee.json (la palabra de acento, en <em>). */

@Component({
  selector: 'app-marquee',
  styleUrl: './marquee.css',
  host: { 'aria-hidden': 'true' },
  template: `
    <div class="marquee">
      <div class="pista">
        @for (vuelta of [1, 2]; track vuelta) {
          @for (f of frases; track $index) {
            <span class="rico" [innerHTML]="f"></span>
          }
        }
      </div>
    </div>
  `,
})
export class Marquee {
  protected readonly frases = traducir(contenido).frases;
}
