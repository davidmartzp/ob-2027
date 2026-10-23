import { Component, effect, ElementRef, inject, Injectable, signal, viewChild } from '@angular/core';
import inscripciones from '@contenido/inscripciones.json';
import { traducir } from '@core/idioma';

/* ============ AVISO ANTES DE PAGAR ============
   Antes de ir a la tienda (Origamística) recuerda que hay que volver a registrarse.
   Cualquier componente lo pide con AvisoPagoServicio.pedir(url); el <dialog> vive
   una sola vez en la página de inscripciones. «Continuar» abre la tienda en otra pestaña. */

@Injectable({ providedIn: 'root' })
export class AvisoPagoServicio {
  readonly url = signal<string | null>(null);
  pedir(url: string): void {
    this.url.set(url);
  }
}

@Component({
  selector: 'app-aviso-pago',
  template: `
    <dialog #dialogo class="aviso" aria-labelledby="aviso-pago-titulo" (close)="servicio.url.set(null)" (click)="alHacerClic($event)">
      <div class="hoja">
        <h2 id="aviso-pago-titulo">{{ t.titulo }}</h2>
        <div class="rico" [innerHTML]="t.html"></div>
        <div class="acciones">
          <button type="button" class="btn btn-linea" (click)="cerrar()">{{ t.cancelar }}</button>
          <button type="button" class="btn btn-naranja" (click)="continuar()">{{ t.continuar }} ↗</button>
        </div>
      </div>
    </dialog>
  `,
  styles: `
    .aviso{ border:0; padding:0; background:transparent; max-width:min(520px, calc(100vw - 32px)); margin:auto; }
    .aviso::backdrop{ background:color-mix(in srgb, var(--tinta) 55%, transparent); }
    .hoja{ background:var(--papel); border:1.5px solid var(--tinta); box-shadow:10px 10px 0 var(--pajizo); padding:clamp(22px,4vw,36px); display:grid; gap:14px; }
    h2{ font-family:var(--serif); font-weight:520; font-size:clamp(24px,3vw,32px); }
    .rico{ color:var(--tinta-80); display:grid; gap:10px; }
    .acciones{ display:flex; justify-content:flex-end; gap:12px; flex-wrap:wrap; margin-top:6px; }
  `,
})
export class AvisoPago {
  protected readonly t = traducir(inscripciones.avisoPago);
  protected readonly servicio = inject(AvisoPagoServicio);
  private readonly dialogo = viewChild.required<ElementRef<HTMLDialogElement>>('dialogo');

  constructor() {
    effect(() => {
      const d = this.dialogo().nativeElement;
      if (this.servicio.url() && !d.open) {
        // jsdom (pruebas) no implementa showModal()
        if (typeof d.showModal === 'function') d.showModal();
        else d.setAttribute('open', '');
      }
    });
  }

  protected continuar(): void {
    const url = this.servicio.url();
    if (url) open(url, '_blank', 'noopener');
    this.cerrar();
  }

  protected cerrar(): void {
    const d = this.dialogo().nativeElement;
    if (typeof d.close === 'function') d.close();
    else d.removeAttribute('open');
    this.servicio.url.set(null);
  }

  /** Clic en el fondo (fuera de la hoja) cierra. */
  protected alHacerClic(e: MouseEvent): void {
    if (e.target === this.dialogo().nativeElement) this.cerrar();
  }
}
