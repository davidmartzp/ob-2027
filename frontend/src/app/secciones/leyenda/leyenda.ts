import { Component } from '@angular/core';
import { SECCIONES_VISIBLES } from '../../datos/evento';
import { TipoSimbolo } from '../../datos/modelos';
import { Simbolo } from '../../compartido/simbolo';

/* ============ LEYENDA DEL DIAGRAMA ============
   Documenta los símbolos que usa el sitio. Si se añade uno nuevo
   al componente Simbolo, registrarlo también aquí. */

@Component({
  selector: 'app-leyenda',
  imports: [Simbolo],
  host: { class: 'leyenda', 'aria-hidden': 'true' },
  template: `
    <span class="clave" i18n="@@leyenda.clave">Clave del diagrama —</span>
    @for (s of simbolos; track s.tipo) {
      <span><svg [appSimbolo]="s.tipo"></svg> {{ s.nombre }}</span>
    }
  `,
  styles: `
    :host{
      display:flex; flex-wrap:wrap; gap:14px 34px; align-items:center; justify-content:center;
      padding:26px var(--gutter); border-top:1.5px solid var(--tinta);
      font-family:var(--mono); font-size:12px; letter-spacing:.14em; text-transform:uppercase;
      color:var(--tinta-80);
    }
    span{ display:inline-flex; align-items:center; gap:9px; }
    .clave{ color:var(--tinta); }
    .simb{ width:30px; }
  `,
})
export class Leyenda {
  protected readonly simbolos = ([
    { tipo: 'valle', nombre: $localize`:@@leyenda.valle:Valle` },
    { tipo: 'montana', nombre: $localize`:@@leyenda.montana:Montaña` },
    { tipo: 'doblar', nombre: $localize`:@@leyenda.doblar:Doblar` },
  ] satisfies { tipo: TipoSimbolo; nombre: string }[])
    // la flecha de doblar solo vive en el itinerario
    .filter((s) => s.tipo !== 'doblar' || SECCIONES_VISIBLES.itinerario);
}
