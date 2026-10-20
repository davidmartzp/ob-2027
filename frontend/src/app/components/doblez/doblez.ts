import { booleanAttribute, Component, input } from '@angular/core';

/* ============ DOBLEZ ENTRE SECCIONES ============
   Toma el fondo y la tinta de la sección que viene DESPUÉS.
   Uso: <app-doblez fondo="var(--paramo)" tinta="var(--paramo-txt)" montana/> */

@Component({
  selector: 'app-doblez',
  host: {
    class: 'doblez',
    '[class.montana]': 'montana()',
    '[style.background]': 'fondo()',
    '[style.color]': 'tinta()',
  },
  template: `<span class="linea"></span>`,
})
export class Doblez {
  readonly fondo = input.required<string>();
  readonly tinta = input('var(--tinta)');
  /** Línea montaña en lugar de valle (alternar entre dobleces). */
  readonly montana = input(false, { transform: booleanAttribute });
}
