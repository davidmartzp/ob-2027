import { Directive, inject, input } from '@angular/core';
import { IdiomaActual } from './idioma';

/* ============ ENLACE ============
   Todo <a> del sitio pasa por aquí:
   · internos (#seccion, actividades, ./): se llevan al idioma actual (en/#seccion…);
   · externos (http…): abren en otra pestaña con rel="noopener";
   · mailto: y tel: quedan igual.
   Uso: <a appEnlace="#inscribete">…</a> · <a [appEnlace]="url">…</a> */

@Directive({
  selector: 'a[appEnlace]',
  host: {
    '[attr.href]': 'href()',
    '[attr.target]': 'externo() ? "_blank" : null',
    '[attr.rel]': 'externo() ? "noopener" : null',
  },
})
export class Enlace {
  readonly appEnlace = input.required<string>();
  private readonly idioma = inject(IdiomaActual);

  protected href(): string {
    return this.idioma.enlace(this.appEnlace());
  }

  protected externo(): boolean {
    return /^https?:/.test(this.appEnlace());
  }
}
