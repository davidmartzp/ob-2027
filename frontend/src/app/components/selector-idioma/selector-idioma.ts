import { Component, inject } from '@angular/core';
import { IDIOMAS } from '@core/idiomas';
import { IdiomaActual, traducir } from '@core/idioma';
import comun from '@contenido/comun.json';

/* ============ SELECTOR DE IDIOMA ============
   Cada idioma es otra copia prerenderizada de la misma página, así que cambiar = navegar
   (recarga completa). Los enlaces son relativos al <base href>, para que funcione igual en
   la raíz del dominio o en una subcarpeta, y conservan la página (actividades/ ↔ en/actividades/). */

@Component({
  selector: 'app-selector-idioma',
  host: { class: 'selector-idioma' },
  template: `
    <nav [attr.aria-label]="comun.idiomaAria">
      @for (i of idiomas; track i.codigo) {
        <a [href]="i.href" [attr.lang]="i.codigo" [attr.hreflang]="i.codigo" [attr.aria-label]="i.nombre"
           [attr.aria-current]="i.actual ? 'true' : null">{{ i.corto }}</a>
      }
    </nav>
  `,
  styles: `
    nav{ display:flex; align-items:center; gap:4px; }
    a{
      font-family:var(--mono); font-size:12px; letter-spacing:.14em; text-decoration:none;
      padding:4px 6px; color:var(--tinta-60); border-bottom:1px solid transparent;
    }
    a + a::before{ content:"/"; margin:0 8px 0 -2px; color:var(--tinta-30); }
    a:hover{ color:var(--tinta); }
    a[aria-current]{ color:var(--tinta); border-bottom-color:var(--miel); }
  `,
})
export class SelectorIdioma {
  protected readonly comun = traducir(comun);
  private readonly actual = inject(IdiomaActual);
  protected readonly idiomas = IDIOMAS.map((i) => ({
    ...i,
    actual: i === this.actual.idioma,
    href: i.ruta + this.actual.pagina || './',
  }));
}
