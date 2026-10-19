import { afterNextRender, Component, DestroyRef, ElementRef, inject, Injectable, signal } from '@angular/core';

/* ============ VISTA FLOTANTE QUE SIGUE EL CURSOR ============
   Cualquier componente la activa a través del servicio (mostrarFoto / mostrarLogo); el componente
   vive una sola vez en la página y persigue al cursor con easing. */

interface EstadoVista {
  activa: boolean;
  foto: string | null;
  /** El logo se muestra a su tamaño, centrado (no recortado como una foto). */
  logo: boolean;
}

@Injectable({ providedIn: 'root' })
export class VistaFlotanteServicio {
  readonly estado = signal<EstadoVista>({ activa: false, foto: null, logo: false });

  mostrarFoto(src: string): void {
    this.estado.set({ activa: true, foto: src, logo: false });
  }

  mostrarLogo(src: string): void {
    this.estado.set({ activa: true, foto: src, logo: true });
  }

  ocultar(): void {
    this.estado.update((e) => ({ ...e, activa: false }));
  }
}

@Component({
  selector: 'app-vista-flotante',
  host: { 'aria-hidden': 'true' },
  template: `
    <figure class="vista-flotante" #vista [class.activa]="estado().activa" [class.modo-logo]="estado().logo">
      <img [attr.src]="estado().foto" alt="">
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

    @media (max-width: 760px){
      .vista-flotante{ display:none; }
    }
  `,
})
export class VistaFlotante {
  protected readonly estado = inject(VistaFlotanteServicio).estado;

  constructor() {
    const host = inject<ElementRef<HTMLElement>>(ElementRef);
    const destroyRef = inject(DestroyRef);

    // Todo (incluida la limpieza) solo en el navegador: en el prerender no existe requestAnimationFrame.
    afterNextRender(() => {
      const vista = host.nativeElement.querySelector<HTMLElement>('.vista-flotante')!;
      let vx = 0, vy = 0, cx = 0, cy = 0, raf = 0;
      const alMover = (e: MouseEvent): void => { vx = e.clientX + 26; vy = e.clientY - 120; };
      addEventListener('mousemove', alMover, { passive: true });
      const seguir = (): void => {
        cx += (vx - cx) * .12; cy += (vy - cy) * .12;
        vista.style.left = cx + 'px'; vista.style.top = cy + 'px';
        raf = requestAnimationFrame(seguir);
      };
      seguir();
      destroyRef.onDestroy(() => { cancelAnimationFrame(raf); removeEventListener('mousemove', alMover); });
    });
  }
}
