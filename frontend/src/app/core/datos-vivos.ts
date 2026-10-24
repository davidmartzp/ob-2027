import { signal } from '@angular/core';
import { apiUrl, ErrorApi, ErrorRed, SinApi } from './api';

/* ============ DATOS VIVOS ============
   Lo que una página carga del backend en el navegador, con su estado para EstadoDatos.
   Sin apiUrl arranca ya en «demo», así el HTML prerenderizado trae el aviso; con apiUrl
   arranca en «cargando» y la página llama a cargar() en afterNextRender.
   Uso:
     protected readonly talleres = new DatosVivos(() => api<Taller[]>('GET', 'workshops'));
     constructor() { afterNextRender(() => this.talleres.cargar()); }
     <app-estado-datos [estado]="talleres.estado()" [mensaje]="talleres.mensaje()" (reintentar)="talleres.cargar()"/> */

/** listo = hay datos para pintar; el resto lo pinta EstadoDatos. */
export type EstadoCarga = 'cargando' | 'listo' | 'vacio' | 'error' | 'demo' | 'cerrado';

/** El estado (y su mensaje) que corresponde a un error de core/api.ts. */
export function estadoDeError(e: unknown): { estado: EstadoCarga; mensaje: string } {
  if (e instanceof SinApi) return { estado: 'demo', mensaje: '' };
  if (e instanceof ErrorApi) return { estado: e.code === 'enrolment_closed' ? 'cerrado' : 'error', mensaje: e.message };
  if (e instanceof ErrorRed) return { estado: 'error', mensaje: '' };
  return { estado: 'error', mensaje: '' };
}

export class DatosVivos<T> {
  readonly estado = signal<EstadoCarga>(apiUrl() ? 'cargando' : 'demo');
  readonly datos = signal<T | null>(null);
  /** Mensaje del backend para «error» o «cerrado» (vacío = el texto genérico). */
  readonly mensaje = signal('');
  /** El último error, para que la página reaccione (p. ej. logout del 403). */
  readonly error = signal<unknown>(null);

  constructor(
    private readonly pedir: () => Promise<T>,
    private readonly estaVacio: (datos: T) => boolean = (d) => Array.isArray(d) && d.length === 0,
  ) {}

  async cargar(): Promise<void> {
    if (!apiUrl()) return this.estado.set('demo');
    this.estado.set('cargando');
    this.error.set(null);
    try {
      const datos = await this.pedir();
      this.datos.set(datos);
      this.mensaje.set('');
      this.estado.set(this.estaVacio(datos) ? 'vacio' : 'listo');
    } catch (e) {
      const { estado, mensaje } = estadoDeError(e);
      this.error.set(e);
      this.mensaje.set(mensaje);
      this.estado.set(estado);
    }
  }

  /** Cambia los datos ya cargados (tras inscribirse, editar…) sin volver a pedirlos. */
  actualizar(cambio: (datos: T) => T): void {
    const actuales = this.datos();
    if (actuales === null) return;
    const nuevos = cambio(actuales);
    this.datos.set(nuevos);
    this.estado.set(this.estaVacio(nuevos) ? 'vacio' : 'listo');
  }
}
