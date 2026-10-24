import { NivelTaller, PublicoTaller } from './modelos';

/* ============ FORMULARIO DE TALLER (sin vista) ============
   Las mismas reglas que el backend (WorkshopInput): nombre obligatorio hasta 200
   caracteres, nivel P/I/A, público A/N, duración 15–480 minutos, observaciones
   hasta 500. La foto: JPEG, PNG o WebP de hasta 5 MB (LocalImageStorage).
   Lo usa EspacioTallerista; los mensajes vienen de talleres.json → tallerista.formulario.errores. */

export interface DatosTaller {
  name: string;
  level: NivelTaller | '';
  public: PublicoTaller | '';
  duration: string;
  observations: string;
}

/** Lo que espera el backend en POST /attendees/{id}/workshops y PUT /workshops/{id}. */
export interface EnvioTaller {
  name: string;
  level: NivelTaller;
  public: PublicoTaller;
  duration: number;
  observations: string;
}

export type CampoTaller = keyof DatosTaller;
export type ErroresTaller = Partial<Record<CampoTaller, string>>;

export interface MensajesTaller {
  nombre: string;
  nombreLargo: string;
  nivel: string;
  publico: string;
  duracion: string;
  observaciones: string;
}

export const DURACION_MIN = 15;
export const DURACION_MAX = 480;
export const IMAGEN_MAX_BYTES = 5 * 1024 * 1024;
export const TIPOS_IMAGEN = ['image/jpeg', 'image/png', 'image/webp'];

export function tallerVacio(): DatosTaller {
  return { name: '', level: '', public: '', duration: '60', observations: '' };
}

/** Errores por campo; vacío = se puede enviar. */
export function validarTaller(d: DatosTaller, m: MensajesTaller): ErroresTaller {
  const e: ErroresTaller = {};
  const nombre = d.name.trim();
  if (!nombre) e.name = m.nombre;
  else if (nombre.length > 200) e.name = m.nombreLargo;
  if (!['P', 'I', 'A'].includes(d.level)) e.level = m.nivel;
  if (!['A', 'N'].includes(d.public)) e.public = m.publico;
  const minutos = Number(d.duration);
  if (!/^\d+$/.test(d.duration.trim()) || minutos < DURACION_MIN || minutos > DURACION_MAX) e.duration = m.duracion;
  if (d.observations.trim().length > 500) e.observations = m.observaciones;
  return e;
}

export function construirTaller(d: DatosTaller): EnvioTaller {
  return {
    name: d.name.trim(),
    level: d.level as NivelTaller,
    public: d.public as PublicoTaller,
    duration: Number(d.duration),
    observations: d.observations.trim(),
  };
}

/** null = la foto se puede subir; si no, qué falla. */
export function validarImagen(archivo: { type: string; size: number }): 'tipo' | 'peso' | null {
  if (!TIPOS_IMAGEN.includes(archivo.type)) return 'tipo';
  if (archivo.size > IMAGEN_MAX_BYTES) return 'peso';
  return null;
}
