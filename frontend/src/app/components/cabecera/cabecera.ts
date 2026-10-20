import { Component, ElementRef, signal, viewChild } from '@angular/core';
import { Menu } from '@components/menu/menu';
import { SelectorIdioma } from '@components/selector-idioma/selector-idioma';
import comun from '@contenido/comun.json';
import evento from '@contenido/evento.json';
import menu from '@contenido/menu.json';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';

/* ============ HEADER ============
   Sólido al bajar 40px, se oculta al bajar y reaparece al subir.
   Los enlaces salen de <app-menu> (contenido/menu.json).
   Desplegable «El congreso»: cierra con clic fuera, con Escape y al elegir. */

@Component({
  selector: 'app-cabecera',
  imports: [Enlace, Menu, SelectorIdioma],
  templateUrl: './cabecera.html',
  styleUrl: './cabecera.css',
  host: {
    '(window:scroll)': 'alHacerScroll()',
    '(document:click)': 'alHacerClic($event)',
    '(document:keydown.escape)': 'desplegableAbierto.set(false)',
  },
})
export class Cabecera {
  protected readonly t = traducir(menu.textos);
  protected readonly comun = traducir(comun);
  protected readonly inscripcionesAbiertas = evento.inscripcionesAbiertas;
  protected readonly mostrarMenu = evento.mostrarMenu;

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
