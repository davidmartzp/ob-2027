import { Component, computed, input, output } from '@angular/core';
import comun from '@contenido/comun.json';
import { traducir } from '@core/idioma';
import { EstadoCarga } from '@core/datos-vivos';

/* ============ ESTADO DE LOS DATOS VIVOS ============
   Lo que se ve mientras no hay datos que pintar: cargando, vacío, error (con «Reintentar»),
   modo demostración (sin apiUrl) y cerrado. Con estado «listo» no pinta nada.
   Textos genéricos en contenido/comun.json → estados; cada página puede cambiar el título
   o el texto de un estado con [titulo] / [texto], y el backend su mensaje con [mensaje].
   Uso: <app-estado-datos [estado]="talleres.estado()" [mensaje]="talleres.mensaje()" (reintentar)="talleres.cargar()"/> */

@Component({
  selector: 'app-estado-datos',
  templateUrl: './estado-datos.html',
  styleUrl: './estado-datos.css',
  host: { '[attr.data-estado]': 'estado()' },
})
export class EstadoDatos {
  readonly estado = input.required<EstadoCarga>();
  /** Mensaje del backend: reemplaza al texto de «error» o «cerrado». */
  readonly mensaje = input('');
  readonly titulo = input('');
  readonly texto = input('');
  readonly reintentar = output();

  protected readonly t = traducir(comun).estados;

  protected readonly tituloVisible = computed(() => this.titulo() || {
    cargando: '', listo: '', vacio: this.t.vacioTitulo, error: this.t.errorTitulo,
    demo: this.t.demoTitulo, cerrado: this.t.cerradoTitulo,
  }[this.estado()]);

  protected readonly textoVisible = computed(() => {
    const e = this.estado();
    if ((e === 'error' || e === 'cerrado') && this.mensaje()) return this.mensaje();
    return this.texto() || {
      cargando: this.t.cargando, listo: '', vacio: this.t.vacioTexto, error: this.t.errorTexto,
      demo: this.t.demoTexto, cerrado: this.t.cerradoTexto,
    }[e];
  });
}
