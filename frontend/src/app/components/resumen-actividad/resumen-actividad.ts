import { Component, input } from '@angular/core';
import { traducir } from '@core/idioma';
import { PapelPlegado } from '@components/papel-plegado/papel-plegado';
import { Actividad } from '@core/modelos';
import actividades from '@contenido/actividades.json';

/* ============ RESUMEN DE ACTIVIDAD (la fila) ============
   Icono plegado + título + resumen + categoría + «Ver detalles».
   Va sobre el <summary> de cada ficha de la página de actividades (abierta, dice «Cerrar»);
   también admite un <a>.
   El título es un span con role=heading: <summary> no admite un <h3> junto a otros elementos.
   Color del énfasis: variable CSS --color-enfasis (por defecto, miel).
   Uso: <a appResumenActividad [actividad]="a" href="…"></a> · <summary appResumenActividad [actividad]="a"></summary> */

@Component({
  selector: 'a[appResumenActividad], summary[appResumenActividad]',
  imports: [PapelPlegado],
  host: { class: 'resumen-actividad' },
  styleUrl: './resumen-actividad.css',
  template: `
    <span class="icono"><app-papel-plegado/></span>
    <span class="texto">
      <span class="nombre rico" role="heading" aria-level="3" [innerHTML]="actividad().titulo"></span>
      <span class="resumen">{{ actividad().resumen }}</span>
    </span>
    <span class="meta">
      <span class="chip">{{ categorias[actividad().categoria] }}</span>
      <span class="accion abrir">{{ t.verDetalles }}</span>
      <span class="accion cerrar">{{ t.cerrar }}</span>
    </span>
  `,
})
export class ResumenActividad {
  readonly actividad = input.required<Actividad>({ alias: 'appResumenActividad' });
  private readonly contenido = traducir(actividades);
  protected readonly t = this.contenido.fila;
  protected readonly categorias: Record<string, string> = this.contenido.categorias;
}
