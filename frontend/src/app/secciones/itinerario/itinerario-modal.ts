import { Component, ElementRef, viewChild } from '@angular/core';
import { ITINERARIO, ITINERARIO_AUTOR } from '../../datos/itinerario';

/* ============ MODAL: ITINERARIO COMPLETO ============
   <dialog> nativo: Escape y foco atrapado vienen del navegador.
   Se cierra también con el botón × y con clic en el fondo.
   Uso: <app-itinerario-modal #modal/> … (click)="modal.abrir()" */

@Component({
  selector: 'app-itinerario-modal',
  templateUrl: './itinerario-modal.html',
  styleUrl: './itinerario-modal.css',
})
export class ItinerarioModal {
  protected readonly momentos = ITINERARIO;
  protected readonly autor = ITINERARIO_AUTOR;

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
