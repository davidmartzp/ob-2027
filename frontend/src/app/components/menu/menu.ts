import { Component, computed, input, output } from '@angular/core';
import { Enlace } from '@core/enlace';
import { destinosMenu, enlacesDeLista, ListaMenu } from '@core/navegacion';

/* ============ MENÚ ============
   Pinta una lista de enlaces de contenido/menu.json (destinos, textos y orden).
   Cada lista tiene su aspecto, según dónde vive:
     principal, suelto → barra superior · congreso → panel desplegable
     movil → menú a pantalla completa · pie → enlaces rápidos del footer
   Los enlaces a secciones ocultas no aparecen. `elegido` avisa al hacer clic
   (para cerrar el desplegable o el menú móvil).
   Uso: <app-menu lista="congreso" (elegido)="cerrar()"/> */

const VARIANTE: Record<ListaMenu, string> = {
  principal: 'barra',
  suelto: 'barra',
  congreso: 'panel',
  movil: 'movil',
  pie: 'pie',
};

@Component({
  selector: 'app-menu',
  imports: [Enlace],
  host: { '[class]': 'variante()' },
  styleUrl: './menu.css',
  template: `
    @for (e of enlaces(); track e.href + e.texto) {
      <a [appEnlace]="e.href" (click)="elegido.emit()">{{ e.texto }}</a>
    }
  `,
})
export class Menu {
  readonly lista = input.required<ListaMenu>();
  readonly elegido = output<void>();

  private readonly destinos = destinosMenu();
  protected readonly enlaces = computed(() => enlacesDeLista(this.destinos, this.lista()));
  protected readonly variante = computed(() => VARIANTE[this.lista()]);
}
