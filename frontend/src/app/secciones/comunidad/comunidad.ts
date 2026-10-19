import { afterNextRender, Component, DestroyRef, ElementRef, inject } from '@angular/core';
import { Separador } from '../../compartido/separador';
import { Reveal } from '../../compartido/reveal';

/* ============ LA COMUNIDAD (foto grupal a sangre) ============ */

/** Recorrido máximo del bloque de la foto hacia arriba o hacia abajo, en px. */
const RECORRIDO_PARALAJE = 30;

@Component({
  selector: 'app-comunidad',
  imports: [Separador, Reveal],
  templateUrl: './comunidad.html',
  styleUrl: './comunidad.css',
})
export class Comunidad {
  constructor() {
    const host = inject<ElementRef<HTMLElement>>(ElementRef);
    const destroyRef = inject(DestroyRef);

    // Paralaje: el bloque de la foto se desplaza más lento que la página. Solo en el navegador y sin movimiento reducido.
    afterNextRender(() => {
      if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      const marco = host.nativeElement.querySelector<HTMLElement>('.marco')!;
      const lienzo = marco.querySelector<HTMLElement>('.lienzo')!;
      let raf = 0;
      const pintar = (): void => {
        raf = 0;
        const caja = marco.getBoundingClientRect();
        if (caja.bottom < 0 || caja.top > innerHeight) return;
        // 0 cuando el marco asoma por abajo, 1 cuando sale por arriba.
        const progreso = (innerHeight - caja.top) / (innerHeight + caja.height);
        lienzo.style.setProperty('--paralaje', ((progreso - .5) * 2 * RECORRIDO_PARALAJE).toFixed(1) + 'px');
      };
      const alMover = (): void => { if (!raf) raf = requestAnimationFrame(pintar); };
      addEventListener('scroll', alMover, { passive: true });
      addEventListener('resize', alMover, { passive: true });
      pintar();
      destroyRef.onDestroy(() => {
        cancelAnimationFrame(raf);
        removeEventListener('scroll', alMover);
        removeEventListener('resize', alMover);
      });
    });
  }
}
