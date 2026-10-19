import { Directive, input } from '@angular/core';

/* ============ ENLACE ============
   Pone el href y, si la URL es externa (http…), abre en otra pestaña con
   rel="noopener". Anclas, rutas internas, mailto: y tel: se quedan en la misma.
   Uso: <a [appEnlace]="url">…</a> */

@Directive({
  selector: 'a[appEnlace]',
  host: {
    '[attr.href]': 'appEnlace()',
    '[attr.target]': 'externo() ? "_blank" : null',
    '[attr.rel]': 'externo() ? "noopener" : null',
  },
})
export class Enlace {
  readonly appEnlace = input.required<string>();
  protected externo(): boolean {
    return /^https?:/.test(this.appEnlace());
  }
}
