import { Component, input } from '@angular/core';
import { TipoSimbolo } from '../datos/modelos';

/* ============ SÍMBOLO DE DIAGRAMA ============
   Valle / montaña / flecha de doblar.
   Los estilos (.simb, .valle, .montana, .flecha) son globales.
   Si añades un tipo nuevo, regístralo también en la Leyenda. */

@Component({
  selector: 'svg[appSimbolo]',
  host: {
    class: 'simb',
    'aria-hidden': 'true',
    viewBox: '0 0 24 16',
  },
  template: `
    @switch (tipo()) {
      @case ('valle') { <svg:path class="valle" d="M1 8 H23"/> }
      @case ('montana') { <svg:path class="montana" d="M1 8 H23"/> }
      @case ('doblar') { <svg:path class="flecha" d="M3 3 C 9 0, 19 2, 17 11 M17 11 l-4.5 -1.8 M17 11 l1.2 -4.4"/> }
    }
  `,
})
export class Simbolo {
  readonly tipo = input.required<TipoSimbolo>({ alias: 'appSimbolo' });
}
