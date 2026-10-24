import { DiaCongreso, NivelTaller, PublicoTaller, Taller } from '@core/modelos';

/* ============ FILTROS DEL PROGRAMA (sin vista) ============
   Los filtros de /booklet viven en la URL para poder compartir una vista:
   ?dia=domingo&nivel=A&publico=N&sala=Sala%201&tallerista=Talía%20Prueba
   Valores desconocidos se ignoran. */

export interface FiltrosPrograma {
  dia: DiaCongreso | null;
  nivel: NivelTaller | null;
  publico: PublicoTaller | null;
  sala: string | null;
  tallerista: string | null;
}

export const SIN_FILTROS: FiltrosPrograma = { dia: null, nivel: null, publico: null, sala: null, tallerista: null };

/** El día en la URL va en español y minúsculas: sabado / domingo. */
const DIA_EN_URL: Record<DiaCongreso, string> = { Saturday: 'sabado', Sunday: 'domingo' };
export const DIAS: DiaCongreso[] = ['Saturday', 'Sunday'];
export const NIVELES: NivelTaller[] = ['P', 'I', 'A'];
export const PUBLICOS: PublicoTaller[] = ['A', 'N'];

export function filtrosDesdeUrl(busqueda: string): FiltrosPrograma {
  const q = new URLSearchParams(busqueda);
  const de = <T extends string>(valor: string | null, validos: readonly T[]): T | null =>
    validos.includes(valor as T) ? (valor as T) : null;
  const dia = DIAS.find((d) => DIA_EN_URL[d] === q.get('dia')?.toLowerCase().normalize('NFD').replace(/\p{M}/gu, '')) ?? null;
  return {
    dia,
    nivel: de(q.get('nivel')?.toUpperCase() ?? null, NIVELES),
    publico: de(q.get('publico')?.toUpperCase() ?? null, PUBLICOS),
    sala: q.get('sala') || null,
    tallerista: q.get('tallerista') || null,
  };
}

/** La búsqueda de la URL para estos filtros ('' sin filtros). */
export function filtrosAUrl(f: FiltrosPrograma): string {
  const q = new URLSearchParams();
  if (f.dia) q.set('dia', DIA_EN_URL[f.dia]);
  if (f.nivel) q.set('nivel', f.nivel);
  if (f.publico) q.set('publico', f.publico);
  if (f.sala) q.set('sala', f.sala);
  if (f.tallerista) q.set('tallerista', f.tallerista);
  const texto = q.toString();
  return texto ? `?${texto}` : '';
}

export function hayFiltros(f: FiltrosPrograma): boolean {
  return Object.values(f).some((v) => v !== null);
}

export function filtrar(talleres: Taller[], f: FiltrosPrograma): Taller[] {
  return talleres.filter((t) =>
    (!f.dia || t.evento?.dia === f.dia) &&
    (!f.nivel || t.nivel === f.nivel) &&
    (!f.publico || t.publico === f.publico) &&
    (!f.sala || t.evento?.salon === f.sala) &&
    (!f.tallerista || t.tallerista === f.tallerista));
}

export interface FranjaPrograma {
  hora: string;
  talleres: Taller[];
}

export interface DiaPrograma {
  dia: DiaCongreso;
  franjas: FranjaPrograma[];
}

/** Por día (sábado, domingo) y hora de inicio; dentro de la hora, por salón. */
export function agrupar(talleres: Taller[]): DiaPrograma[] {
  const ordenados = [...talleres].sort((a, b) =>
    DIAS.indexOf(a.evento!.dia) - DIAS.indexOf(b.evento!.dia) ||
    a.evento!.inicio.localeCompare(b.evento!.inicio) ||
    (a.evento!.salon ?? '').localeCompare(b.evento!.salon ?? '', 'es', { numeric: true }));
  const dias: DiaPrograma[] = [];
  for (const t of ordenados) {
    const e = t.evento!;
    let dia = dias.at(-1);
    if (dia?.dia !== e.dia) dias.push((dia = { dia: e.dia, franjas: [] }));
    let franja = dia.franjas.at(-1);
    if (franja?.hora !== e.inicio) dia.franjas.push((franja = { hora: e.inicio, talleres: [] }));
    franja.talleres.push(t);
  }
  return dias;
}

/** Valores distintos y ordenados (para los selectores de salón y tallerista). */
export function opciones(valores: (string | null | undefined)[]): string[] {
  return [...new Set(valores.filter((v): v is string => !!v))].sort((a, b) => a.localeCompare(b, 'es', { numeric: true }));
}
