import { Component, inject } from '@angular/core';
import { IDIOMAS } from '@core/idiomas';
import { IdiomaActual, traducir } from '@core/idioma';
import comun from '@contenido/comun.json';

/* ============ SELECTOR DE IDIOMA ============
   Cada idioma es otra copia prerenderizada de la misma página, así que cambiar = navegar
   (recarga completa). Los enlaces son relativos al <base href>, para que funcione igual en
   la raíz del dominio o en una subcarpeta, y conservan la página (actividades/ ↔ en/actividades/).
   Cada idioma se muestra con su bandera en SVG (los emojis de bandera no se ven en Windows);
   el nombre del idioma va en aria-label. Las banderas usan sus colores oficiales, no la paleta. */

@Component({
  selector: 'app-selector-idioma',
  host: { class: 'selector-idioma' },
  template: `
    <nav [attr.aria-label]="comun.idiomaAria">
      @for (i of idiomas; track i.codigo) {
        <a [href]="i.href" [attr.lang]="i.codigo" [attr.hreflang]="i.codigo" [attr.aria-label]="i.nombre"
           [attr.title]="i.nombre" [attr.aria-current]="i.actual ? 'true' : null">
          @switch (i.bandera) {
            @case ('co') {
              <svg viewBox="0 0 30 20" aria-hidden="true">
                <rect width="30" height="10" fill="#FCD116"/>
                <rect y="10" width="30" height="5" fill="#003893"/>
                <rect y="15" width="30" height="5" fill="#CE1126"/>
              </svg>
            }
            @case ('us') {
              <svg viewBox="0 0 38 20" aria-hidden="true">
                <rect width="38" height="20" fill="#B22234"/>
                <path d="M0 2.3h38M0 5.4h38M0 8.5h38M0 11.5h38M0 14.6h38M0 17.7h38" stroke="#fff" stroke-width="1.54"/>
                <rect width="15.2" height="10.8" fill="#3C3B6E"/>
                <g fill="#fff">
                  <circle cx="2.5" cy="2.2" r=".75"/><circle cx="6.3" cy="2.2" r=".75"/><circle cx="10.1" cy="2.2" r=".75"/><circle cx="13" cy="2.2" r=".75"/>
                  <circle cx="4.4" cy="5.4" r=".75"/><circle cx="8.2" cy="5.4" r=".75"/><circle cx="12" cy="5.4" r=".75"/>
                  <circle cx="2.5" cy="8.6" r=".75"/><circle cx="6.3" cy="8.6" r=".75"/><circle cx="10.1" cy="8.6" r=".75"/><circle cx="13" cy="8.6" r=".75"/>
                </g>
              </svg>
            }
          }
        </a>
      }
    </nav>
  `,
  styles: `
    nav{ display:flex; align-items:center; gap:10px; }
    a{
      display:block; padding:12px 6px 10px; border-bottom:2px solid transparent;
      opacity:.5; transition:opacity .2s, transform .2s var(--ease);
    }
    a:hover{ opacity:1; transform:translateY(-1px); }
    a[aria-current]{ opacity:1; border-bottom-color:var(--miel); }
    svg{ display:block; height:18px; width:auto; border-radius:2px; box-shadow:0 0 0 1px var(--tinta-30); }
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
