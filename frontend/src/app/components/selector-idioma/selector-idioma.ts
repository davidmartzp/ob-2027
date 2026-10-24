import { Component, inject } from '@angular/core';
import { IDIOMAS } from '@core/idiomas';
import { IdiomaActual, traducir } from '@core/idioma';
import comun from '@contenido/comun.json';

/* ============ SELECTOR DE IDIOMA ============
   Cada idioma es otra copia prerenderizada de la misma página, así que cambiar = navegar
   (recarga completa). Los enlaces son relativos al <base href>, para que funcione igual en
   la raíz del dominio o en una subcarpeta, y conservan la página (actividades/ ↔ en/actividades/).
   Cada idioma se muestra con su código en texto, ES | EN (mono, como las etiquetas del
   diagrama); el nombre completo del idioma va en aria-label y en title. */

@Component({
  selector: 'app-selector-idioma',
  host: { class: 'selector-idioma' },
  template: `
    <nav [attr.aria-label]="comun.idiomaAria">
      @for (i of idiomas; track i.codigo) {
        @if (!$first) { <span class="barra" aria-hidden="true">|</span> }
        <a [href]="i.href" [attr.lang]="i.codigo" [attr.hreflang]="i.codigo" [attr.aria-label]="i.nombre"
           [attr.title]="i.nombre" [attr.aria-current]="i.actual ? 'true' : null">
          {{ i.codigo.toUpperCase() }}
        </a>
      }
    </nav>
  `,
  styles: `
    nav{ display:flex; align-items:center; gap:2px; font-family:var(--mono); font-size:13px; letter-spacing:.14em; }
    a{
      display:inline-flex; align-items:center; min-height:44px; padding:0 6px;
      color:var(--tinta-60); text-decoration:none; transition:color .2s;
    }
    a:hover{ color:var(--tinta); }
    /* el idioma actual: en tinta y subrayado en miel */
    a[aria-current]{ color:var(--tinta); font-weight:600; text-decoration:underline 2px var(--miel); text-underline-offset:6px; }
    a:focus-visible{ outline:2px dashed var(--miel); outline-offset:2px; }
    .barra{ color:var(--tinta-30); }
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
