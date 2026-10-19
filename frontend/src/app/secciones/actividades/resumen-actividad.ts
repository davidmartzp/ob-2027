import { Component, input } from '@angular/core';
import { CATEGORIAS } from '../../datos/actividades';
import { Actividad } from '../../datos/modelos';
import { PapelPlegado } from '../../compartido/papel-plegado';

/* ============ RESUMEN DE ACTIVIDAD (la fila) ============
   Icono plegado + título + resumen + categoría + «Ver detalles».
   Lo comparten la destacada de la home (sobre un <a>) y la ficha de la página
   de actividades (sobre el <summary> de su <details>; ahí, abierta, dice «Cerrar»).
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
      <span class="nombre" role="heading" aria-level="3">{{ actividad().titulo.antes }}@if (actividad().titulo.enfasis) {<em>{{ actividad().titulo.enfasis }}</em>}{{ actividad().titulo.despues }}</span>
      <span class="resumen">{{ actividad().resumen }}</span>
    </span>
    <span class="meta">
      <span class="chip">{{ categorias[actividad().categoria] }}</span>
      <span class="accion abrir" i18n="@@actividades.verDetalles">Ver detalles</span>
      <span class="accion cerrar" i18n="@@paginaActividades.cerrar">Cerrar</span>
    </span>
  `,
})
export class ResumenActividad {
  readonly actividad = input.required<Actividad>({ alias: 'appResumenActividad' });
  protected readonly categorias = CATEGORIAS;
}
