import { Component } from '@angular/core';
import { traducir } from '@core/idioma';
import { Simbolo } from '@components/simbolo/simbolo';
import { seccionVisible } from '@core/navegacion';
import { TipoSimbolo } from '@core/modelos';
import contenido from '@contenido/leyenda.json';

/* ============ LEYENDA DEL DIAGRAMA ============
   Documenta los símbolos que usa el sitio. Si se añade uno nuevo
   al componente Simbolo, registrarlo también aquí (y su nombre en contenido/leyenda.json). */

@Component({
  selector: 'app-leyenda',
  imports: [Simbolo],
  host: { class: 'leyenda', 'aria-hidden': 'true' },
  template: `
    <span class="clave">{{ t.clave }}</span>
    @for (s of simbolos; track s) {
      <span><svg [appSimbolo]="s"></svg> {{ t[s] }}</span>
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
  protected readonly t = traducir(contenido);
  protected readonly simbolos = (['valle', 'montana', 'doblar'] satisfies TipoSimbolo[])
    // la flecha de doblar solo vive en el itinerario
    .filter((s) => s !== 'doblar' || seccionVisible('itinerario'));
}
