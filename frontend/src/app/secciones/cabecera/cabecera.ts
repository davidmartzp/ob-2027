import { Component, ElementRef, inject, signal, viewChild } from '@angular/core';
import { SelectorIdioma } from '../../compartido/selector-idioma';
import { ENLACES, enlaceVisible, INSCRIPCIONES_ABIERTAS, MOSTRAR_MENU, NAV_CONGRESO, NAV_MOVIL, NAV_PRINCIPAL } from '../../datos/evento';

/* ============ HEADER ============
   Sólido al bajar 40px, se oculta al bajar y reaparece al subir.
   Desplegable «El congreso»: cierra con clic fuera, con Escape y al elegir. */

@Component({
  selector: 'app-cabecera',
  imports: [SelectorIdioma],
  templateUrl: './cabecera.html',
  styleUrl: './cabecera.css',
  host: {
    '(window:scroll)': 'alHacerScroll()',
    '(document:click)': 'alHacerClic($event)',
    '(document:keydown.escape)': 'desplegableAbierto.set(false)',
  },
})
export class Cabecera {
  protected readonly principal = NAV_PRINCIPAL.filter((e) => enlaceVisible(e.href));
  protected readonly congreso = NAV_CONGRESO.filter((e) => enlaceVisible(e.href));
  protected readonly movil = NAV_MOVIL.filter((e) => enlaceVisible(e.href));
  protected readonly inscripcionesAbiertas = INSCRIPCIONES_ABIERTAS;
  protected readonly mostrarMenu = MOSTRAR_MENU;
  protected readonly tallerista = ENLACES.tallerista;
  protected readonly talleristaVisible = enlaceVisible(ENLACES.tallerista.href);

  protected readonly solido = signal(false);
  protected readonly oculto = signal(false);
  protected readonly desplegableAbierto = signal(false);
  protected readonly movilAbierto = signal(false);

  private readonly desplegable = viewChild<ElementRef<HTMLElement>>('desplegable');
  private ultimaY = 0;

  protected alHacerScroll(): void {
    const y = scrollY;
    this.solido.set(y > 40);
    this.oculto.set(y > 300 && y > this.ultimaY);
    this.ultimaY = y;
  }

  protected alHacerClic(e: MouseEvent): void {
    if (!this.desplegable()?.nativeElement.contains(e.target as Node)) this.desplegableAbierto.set(false);
  }
}
