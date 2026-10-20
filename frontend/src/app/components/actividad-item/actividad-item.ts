import { Component, inject, input } from '@angular/core';
import { Enlace } from '@core/enlace';
import actividades from '@contenido/actividades.json';
import { Actividad } from '@core/modelos';
import { VistaFlotanteServicio } from '@components/vista-flotante/vista-flotante';
import { ResumenActividad } from '@components/resumen-actividad/resumen-actividad';

/* ============ ACTIVIDAD DESTACADA (home) ============
   El resumen de la actividad como enlace a su ficha (actividades#id), con la
   vista flotante (foto o logo) al pasar el cursor.
   Se monta sobre el propio <li> para que la lista atenúe a los hermanos
   en hover (.lista-actividades:hover > li:not(:hover)).
   Uso: <li appActividad [actividad]="a"></li> */

@Component({
  selector: 'li[appActividad]',
  imports: [Enlace, ResumenActividad],
  template: `
    <a [appResumenActividad]="actividad()" [appEnlace]="'actividades#' + actividad().id"
       (mouseenter)="mostrarVista()" (mouseleave)="vista.ocultar()"></a>
  `,
})
export class ActividadItem {
  readonly actividad = input.required<Actividad>();
  protected readonly vista = inject(VistaFlotanteServicio);

  protected mostrarVista(): void {
    const foto = this.actividad().foto;
    if (foto) this.vista.mostrarFoto(foto);
    else this.vista.mostrarLogo(actividades.logoSinFoto);
  }
}
