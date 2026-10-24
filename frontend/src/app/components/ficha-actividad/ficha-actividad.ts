import { Component, ElementRef, input, viewChild } from '@angular/core';
import actividades from '@contenido/actividades.json';
import { Actividad } from '@core/modelos';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import { PapelPlegado } from '@components/papel-plegado/papel-plegado';

/* ============ FICHA DE ACTIVIDAD ============
   Una foto impresa pegada con cinta (como las destacadas de la home); al pulsarla, el
   detalle se abre en un <dialog> nativo (Escape, foco atrapado y devolución del foco
   vienen del navegador; se cierra también con × y con clic en el fondo), así el
   catálogo no se mueve. Cada ficha lleva su propio diálogo: todo el texto sale en el
   prerender. Sin foto, la tarjeta es una nota de papel con la esquina doblada (la
   página las deja al final) y el diálogo va a una sola columna.
   Se monta sobre el propio <li> (id = ancla, actividades#chocogami).
   Uso: <li appFicha [actividad]="a" [id]="a.id"></li> */

@Component({
  selector: 'li[appFicha]',
  imports: [Enlace, PapelPlegado],
  styleUrl: './ficha-actividad.css',
  template: `
    <button type="button" class="tarjeta" [class.nota]="!actividad().foto" aria-haspopup="dialog" (click)="abrir()">
      @if (actividad().foto; as foto) {
        <span class="foto">
          <img [src]="foto" alt="" loading="lazy" decoding="async">
          <span class="cinta" aria-hidden="true"></span>
        </span>
      } @else {
        <span class="icono" aria-hidden="true"><app-papel-plegado/></span>
      }
      <span class="cabeza">
        <span class="chip">{{ categorias[actividad().categoria] }}</span>
        <span class="nombre rico" [innerHTML]="actividad().titulo"></span>
        <span class="resumen">{{ actividad().resumen }}</span>
        <span class="accion">{{ t.verDetalles }}</span>
      </span>
    </button>

    <dialog #dialogo class="ficha" [attr.aria-labelledby]="'titulo-' + actividad().id" (click)="alHacerClic($event)">
      <!-- fuera de la hoja (que hace scroll): el × queda siempre a la vista -->
      <button type="button" class="cerrar" [attr.aria-label]="t.cerrar" (click)="cerrar()">×</button>
      <div class="hoja" [class.sin-foto]="!actividad().foto">
        @if (actividad().foto; as foto) {
          <div class="foto"><img [src]="foto" alt="" loading="lazy" decoding="async"></div>
        }
        <div class="detalle">
          <span class="chip">{{ categorias[actividad().categoria] }}</span>
          <h2 class="nombre rico" [id]="'titulo-' + actividad().id" [innerHTML]="actividad().titulo"></h2>
          <p class="resumen">{{ actividad().resumen }}</p>
          @for (p of actividad().parrafos; track $index) {
            <p>{{ p }}</p>
          }
          @if (actividad().nota) {
            <p class="pend-nota">{{ actividad().nota }}</p>
          }
          @if (actividad().enlaces?.length) {
            <ul class="enlaces">
              @for (e of actividad().enlaces; track e.href) {
                <li><a [appEnlace]="e.href">{{ e.texto }}</a></li>
              }
            </ul>
          }
        </div>
      </div>
    </dialog>
  `,
})
export class FichaActividad {
  readonly actividad = input.required<Actividad>();
  private readonly contenido = traducir(actividades);
  protected readonly t = this.contenido.fila;
  protected readonly categorias: Record<string, string> = this.contenido.categorias;

  private readonly dialogo = viewChild.required<ElementRef<HTMLDialogElement>>('dialogo');

  abrir(): void {
    const d = this.dialogo().nativeElement;
    if (d.open) return;
    // jsdom (pruebas) no implementa showModal()
    if (typeof d.showModal === 'function') d.showModal();
    else d.setAttribute('open', '');
  }

  cerrar(): void {
    const d = this.dialogo().nativeElement;
    if (typeof d.close === 'function') d.close();
    else d.removeAttribute('open');
  }

  /** Clic en el fondo oscuro (fuera de la hoja) cierra. */
  protected alHacerClic(e: MouseEvent): void {
    if (e.target === this.dialogo().nativeElement) this.cerrar();
  }
}
