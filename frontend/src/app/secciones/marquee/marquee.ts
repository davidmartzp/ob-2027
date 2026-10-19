import { Component } from '@angular/core';
import { FRASES_MARQUEE } from '../../datos/evento';

/* ============ MARQUEE — faja de ruana tejida ============
   La pista repite las frases dos veces para que el bucle sea continuo. */

@Component({
  selector: 'app-marquee',
  styleUrl: './marquee.css',
  host: { 'aria-hidden': 'true' },
  template: `
    <div class="marquee">
      <div class="pista">
        @for (vuelta of [1, 2]; track vuelta) {
          @for (f of frases; track $index) {
            <span>{{ f.antes }}@if (f.enfasis) {<em>{{ f.enfasis }}</em>}{{ f.despues }}</span>
          }
        }
      </div>
    </div>
  `,
})
export class Marquee {
  protected readonly frases = FRASES_MARQUEE;
}
