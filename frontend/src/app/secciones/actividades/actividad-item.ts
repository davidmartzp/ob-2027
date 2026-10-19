import { Component, inject, input } from '@angular/core';
import { LOGO_ACTIVIDAD } from '../../datos/actividades';
import { Actividad } from '../../datos/modelos';
import { VistaFlotanteServicio } from '../../efectos/vista-flotante';
import { ResumenActividad } from './resumen-actividad';

/* ============ ACTIVIDAD DESTACADA (home) ============
   El resumen de la actividad como enlace a su ficha (actividades#id), con la
   vista flotante (foto o logo) al pasar el cursor.
   Se monta sobre el propio <li> para que la lista atenúe a los hermanos
   en hover (.lista-actividades:hover > li:not(:hover)).
   Uso: <li appActividad [actividad]="a"></li> */

@Component({
  selector: 'li[appActividad]',
  imports: [ResumenActividad],
  template: `
    <a [appResumenActividad]="actividad()" [href]="'actividades#' + actividad().id"
       (mouseenter)="mostrarVista()" (mouseleave)="vista.ocultar()"></a>
  `,
})
export class ActividadItem {
  readonly actividad = input.required<Actividad>();
  protected readonly vista = inject(VistaFlotanteServicio);

  protected mostrarVista(): void {
    const foto = this.actividad().foto;
    if (foto) this.vista.mostrarFoto(foto);
    else this.vista.mostrarLogo(LOGO_ACTIVIDAD);
  }
}
