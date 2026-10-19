import { Component, computed, inject, LOCALE_ID } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { NavigationEnd, Router } from '@angular/router';
import { filter, map } from 'rxjs';
import { IDIOMAS } from '../datos/idiomas';

/* ============ SELECTOR DE IDIOMA ============
   Cada idioma es otra copia estática del sitio, así que cambiar = navegar.
   Los enlaces son relativos al <base href> del idioma actual, para que
   funcione igual en la raíz del dominio o en una subcarpeta, y conservan
   la página actual (/actividades ↔ /en/actividades). */

@Component({
  selector: 'app-selector-idioma',
  host: { class: 'selector-idioma' },
  template: `
    <nav aria-label="Idioma" i18n-aria-label="@@comun.idiomaAria">
      @for (i of idiomas(); track i.codigo) {
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
  private readonly actual = IDIOMAS.find((i) => i.codigo === inject(LOCALE_ID)) ?? IDIOMAS[0];
  // Desde la carpeta del idioma actual: subir a la raíz y entrar en la del otro.
  private readonly raiz = this.actual.ruta ? '../'.repeat(this.actual.ruta.split('/').filter(Boolean).length) : '';
  private readonly router = inject(Router);
  /** Ruta de la página actual sin «/» inicial, ancla ni consulta: '' en la home, 'actividades'… */
  private readonly pagina = toSignal(
    this.router.events.pipe(filter((e) => e instanceof NavigationEnd), map(() => this.rutaActual())),
    { initialValue: this.rutaActual() },
  );
  protected readonly idiomas = computed(() => IDIOMAS.map((i) => ({
    ...i,
    actual: i === this.actual,
    href: this.raiz + i.ruta + this.pagina() || './',
  })));

  private rutaActual(): string {
    return this.router.url.split(/[?#]/)[0].replace(/^\//, '');
  }
}
