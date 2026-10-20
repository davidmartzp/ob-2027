import { afterNextRender, Component, DestroyRef, ElementRef, inject, Injectable, signal, viewChild } from '@angular/core';

/* ============ VISTA FLOTANTE QUE SIGUE EL CURSOR ============
   Cualquier componente la activa a través del servicio (mostrarFoto / mostrarLogo); el componente
   vive una sola vez en la página y persigue al cursor con easing.
   El bucle de animación solo corre mientras la vista está activa o termina de llegar a su
   sitio, y nunca en pantallas sin cursor (móvil): ahí la vista está oculta. */

interface EstadoVista {
  activa: boolean;
  src: string | null;
  /** foto = recortada para llenar el marco · logo = a su tamaño, centrado. */
  modo: 'foto' | 'logo';
}

@Injectable({ providedIn: 'root' })
export class VistaFlotanteServicio {
  readonly estado = signal<EstadoVista>({ activa: false, src: null, modo: 'foto' });

  mostrarFoto(src: string): void {
    this.estado.set({ activa: true, src, modo: 'foto' });
  }

  mostrarLogo(src: string): void {
    this.estado.set({ activa: true, src, modo: 'logo' });
  }

  ocultar(): void {
    this.estado.update((e) => ({ ...e, activa: false }));
  }
}

/** Distancia (px) por debajo de la cual la vista ya llegó al cursor. */
const LLEGADA = .5;

@Component({
  selector: 'app-vista-flotante',
  host: { 'aria-hidden': 'true' },
  template: `
    <figure class="vista-flotante" #vista [class.activa]="estado().activa" [class.modo-logo]="estado().modo === 'logo'">
      <img [attr.src]="estado().src" alt="">
    </figure>
  `,
  styles: `
    .vista-flotante{
      position:fixed; z-index:90; width:min(300px, 30vw); aspect-ratio:4/5;
      top:0; left:0;
      pointer-events:none; opacity:0; transform:scale(.85) rotate(-4deg);
      transition:opacity .18s ease, transform .3s var(--ease);
      background:var(--papel-2); border:1.5px solid var(--tinta);
      display:grid; place-items:center; overflow:hidden;
      box-shadow:14px 14px 0 var(--ruana);
    }
    .vista-flotante.activa{ opacity:1; transform:scale(1) rotate(2deg); }
    .vista-flotante img{ width:100%; height:100%; object-fit:cover; }
    .vista-flotante.modo-logo img{ object-fit:none; }

    @media (max-width: 760px), (hover: none){
      .vista-flotante{ display:none; }
    }
  `,
})
export class VistaFlotante {
  protected readonly estado = inject(VistaFlotanteServicio).estado;
  private readonly vista = viewChild.required<ElementRef<HTMLElement>>('vista');

  constructor() {
    const destroyRef = inject(DestroyRef);

    // Todo (incluida la limpieza) solo en el navegador: en el prerender no existe requestAnimationFrame.
    afterNextRender(() => {
      if (matchMedia('(hover: none)').matches) return;
      const vista = this.vista().nativeElement;
      let vx = 0, vy = 0, cx = 0, cy = 0, raf = 0;

      // `translate` (no left/top): mover la vista no obliga a recalcular el layout, y no choca
      // con el `transform` de la animación de entrada.
      const seguir = (): void => {
        cx += (vx - cx) * .12; cy += (vy - cy) * .12;
        vista.style.translate = `${cx}px ${cy}px`;
        const llego = Math.abs(vx - cx) < LLEGADA && Math.abs(vy - cy) < LLEGADA;
        raf = this.estado().activa || !llego ? requestAnimationFrame(seguir) : 0;
      };
      const alMover = (e: MouseEvent): void => {
        vx = e.clientX + 26; vy = e.clientY - 120;
        if (raf) return;
        if (this.estado().activa) raf = requestAnimationFrame(seguir);
        else { cx = vx; cy = vy; } // oculta y quieta: espera ya junto al cursor para la próxima vez
      };
      addEventListener('mousemove', alMover, { passive: true });
      destroyRef.onDestroy(() => { cancelAnimationFrame(raf); removeEventListener('mousemove', alMover); });
    });
  }
}
