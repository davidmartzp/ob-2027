import { afterNextRender, DestroyRef, Directive, ElementRef, inject, Injectable, input } from '@angular/core';

/* ============ REVEALS AL HACER SCROLL (ENTRADA Y SALIDA) ============
   Un único IntersectionObserver compartido por toda la página. Los elementos
   entran al aparecer (.in) y salen al dejar la pantalla; .arriba indica que
   salieron por arriba, para que al volver bajen desde ese lado.
   Solo corre en el navegador (afterNextRender): el HTML prerenderizado
   sale con .reveal y aparece al hidratar, igual que el sitio original. */

@Injectable({ providedIn: 'root' })
export class ObservadorReveal {
  private io?: IntersectionObserver;

  observar(el: HTMLElement): void {
    if (typeof IntersectionObserver === 'undefined') {
      el.classList.add('in');
      return;
    }
    this.io ??= new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        // Visible: al menos un 12 % del elemento, o un 30 % de la pantalla si el elemento es muy alto.
        const alto = e.rootBounds?.height ?? innerHeight;
        const visible = e.isIntersecting && (e.intersectionRatio >= .12 || e.intersectionRect.height >= alto * .3);
        if (visible) {
          e.target.classList.remove('arriba');
          e.target.classList.add('in');
        } else if (!e.isIntersecting || e.intersectionRatio < .05) {
          // Salida: se desvanece y queda esperando del lado por el que se fue (arriba o abajo).
          e.target.classList.remove('in');
          e.target.classList.toggle('arriba', e.boundingClientRect.top < (e.rootBounds?.top ?? 0));
        }
      });
    }, { threshold: [0, .05, .12, .25, .5, .75, 1], rootMargin: '-6% 0px -6% 0px' });
    this.io.observe(el);
  }

  dejar(el: HTMLElement): void {
    this.io?.unobserve(el);
  }
}

/**
 * Aparición escalonada: `appReveal` sin valor, o `appReveal="1|2|3"` para el retraso.
 * Uso: `<p appReveal="2">…</p>`
 */
@Directive({
  selector: '[appReveal]',
  host: {
    class: 'reveal',
    '[attr.data-delay]': 'appReveal() || null',
  },
})
export class Reveal {
  readonly appReveal = input<string | number>('');

  constructor() {
    const el = inject<ElementRef<HTMLElement>>(ElementRef).nativeElement;
    const observador = inject(ObservadorReveal);
    afterNextRender(() => observador.observar(el));
    inject(DestroyRef).onDestroy(() => observador.dejar(el));
  }
}
