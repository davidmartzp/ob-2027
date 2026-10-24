import { DiaCongreso, NivelTaller, PublicoTaller, Socio, Taller } from './modelos';

/* ============ TALLERES DEL BACKEND → Taller ============
   El backend devuelve tres formas según la ruta (conserva los nombres de campo del sitio
   antiguo); aquí se convierten a la única que pinta FichaTaller. */

interface TallerBase {
  id: number;
  name: string;
  level: NivelTaller;
  public: PublicoTaller;
  duration: number;
  observations?: string | null;
  image: string | null;
  partners: Socio[];
}

/** GET /workshops */
export interface TallerGaleriaApi extends TallerBase {
  instructor: { id: number; name: string; lastname: string } | null;
}

/** GET /events/schedule */
export interface TallerProgramaApi extends TallerBase {
  instructor: { id: number; name: string } | null;
  event: { id: number; day: DiaCongreso; start_time: string; end_time: string; room: { id: number; name: string | null } | null };
}

/** GET /events/active */
export interface EventoActivoApi {
  event: { id: number; day: DiaCongreso; start_time: string; end_time: string };
  workshop: TallerBase & { instructor: { id: number; name: string } | null; tickets: number };
  room: { id: number; name: string | null } | null;
}

/** Talleres de quien los dicta (POST /workshops/by-email, /attendees/{id}/workshops). */
export interface TallerPropioApi extends TallerBase {
  /** 0 retirado · 1 activo · 2 oculto por el equipo */
  status: number;
}

/** '10:00:00' → '10:00' */
export const hora = (h: string): string => h.slice(0, 5);

function base(t: TallerBase): Omit<Taller, 'tallerista'> {
  return {
    id: t.id, nombre: t.name, nivel: t.level, publico: t.public, duracion: t.duration,
    observaciones: t.observations ?? null, foto: t.image, socios: t.partners ?? [],
  };
}

export function desdeGaleria(t: TallerGaleriaApi): Taller {
  return { ...base(t), tallerista: t.instructor ? `${t.instructor.name} ${t.instructor.lastname.split(/\s+/)[0] ?? ''}`.trim() : null };
}

export function desdePrograma(t: TallerProgramaApi): Taller {
  return {
    ...base(t),
    tallerista: t.instructor?.name ?? null,
    evento: { id: t.event.id, dia: t.event.day, inicio: hora(t.event.start_time), fin: hora(t.event.end_time), salon: t.event.room?.name ?? null },
  };
}

export function desdeActivo(r: EventoActivoApi): Taller {
  return {
    ...base(r.workshop),
    tallerista: r.workshop.instructor?.name ?? null,
    cupos: r.workshop.tickets,
    evento: { id: r.event.id, dia: r.event.day, inicio: hora(r.event.start_time), fin: hora(r.event.end_time), salon: r.room?.name ?? null },
  };
}

export function desdePropio(t: TallerPropioApi): Taller {
  return { ...base(t), tallerista: null };
}
