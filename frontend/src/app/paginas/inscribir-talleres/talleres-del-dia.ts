import { Taller } from '@core/modelos';

/* ============ TALLERES DEL DÍA (sin vista) ============
   Sesión local del asistente, franjas horarias, filtros y choques de /inscribir-talleres.
   El backend manda (cupos, choques, edad, turnos): el choque se calcula aquí solo para
   avisar antes de pedir la inscripción. */

/** Lo que se recuerda en el navegador tras verificar (solo comodidad: no da permisos). */
export interface SesionAsistente {
  id: number;
  nombre: string;
  numero: string;
}

const CLAVE_SESION = 'origami.inscribir-talleres';

export function leerSesion(almacen: Storage | undefined): SesionAsistente | null {
  try {
    const s = JSON.parse(almacen?.getItem(CLAVE_SESION) ?? 'null') as SesionAsistente | null;
    return s && typeof s.id === 'number' ? s : null;
  } catch {
    return null;
  }
}

export function guardarSesion(almacen: Storage | undefined, s: SesionAsistente | null): void {
  try {
    if (s) almacen?.setItem(CLAVE_SESION, JSON.stringify(s));
    else almacen?.removeItem(CLAVE_SESION);
  } catch {
    // modo privado o almacenamiento bloqueado: la sesión dura lo que la página
  }
}

export interface FiltrosDia {
  salon: string | null;
  hora: string | null;
  tallerista: string | null;
}

export function filtrarDia(talleres: Taller[], f: FiltrosDia): Taller[] {
  return talleres.filter((t) =>
    (!f.salon || t.evento?.salon === f.salon) &&
    (!f.hora || t.evento?.inicio === f.hora) &&
    (!f.tallerista || t.tallerista === f.tallerista));
}

export interface Franja {
  hora: string;
  talleres: Taller[];
}

/** Por día, hora de inicio y salón (el backend ya los ordena; aquí no se da por hecho). */
export function porFranja(talleres: Taller[]): Franja[] {
  const ordenados = [...talleres].sort((a, b) =>
    (a.evento!.dia === 'Sunday' ? 1 : 0) - (b.evento!.dia === 'Sunday' ? 1 : 0) ||
    a.evento!.inicio.localeCompare(b.evento!.inicio) ||
    (a.evento!.salon ?? '').localeCompare(b.evento!.salon ?? '', 'es', { numeric: true }));
  const franjas: Franja[] = [];
  for (const t of ordenados) {
    const hora = t.evento!.inicio;
    const ultima = franjas.at(-1);
    if (ultima?.hora === hora) ultima.talleres.push(t);
    else franjas.push({ hora, talleres: [t] });
  }
  return franjas;
}

/** El taller de `mios` que se cruza en horario con `t` (mismo día), si hay. */
export function chocaCon(t: Taller, mios: Taller[]): Taller | null {
  const e = t.evento!;
  return mios.find((m) => m.evento!.id !== e.id && m.evento!.dia === e.dia && m.evento!.inicio < e.fin && e.inicio < m.evento!.fin) ?? null;
}
