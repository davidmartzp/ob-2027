import evento from '@contenido/evento.json';
import menu from '@contenido/menu.json';
import { traducir } from './idioma';
import { Enlace } from './modelos';

/* ============ NAVEGACIÓN E INTERRUPTORES ============
   Los destinos del menú y qué lista lleva cada uno están en contenido/menu.json; los
   interruptores (secciones visibles, menú, inscripciones), en contenido/evento.json.
   Destinos: «#seccion» = ancla de la home; «actividades» / «actividades#id» = página propia.
   Siempre en español y sin «/» inicial: la directiva appEnlace los lleva al idioma actual. */

export type Seccion = keyof typeof evento.secciones;
export type ListaMenu = keyof typeof menu.listas;
type Destino = keyof typeof menu.destinos;

/** ¿Se muestra esta sección? Las que no tienen interruptor, siempre. */
export function seccionVisible(id: string): boolean {
  return (evento.secciones as Record<string, boolean>)[id] ?? true;
}

/** ¿Apunta este enlace a una sección visible? Entiende anclas (#itinerario) y
 *  páginas (actividades, actividades#chocogami): la página comparte nombre con su sección. */
export function enlaceVisible(href: string): boolean {
  return seccionVisible(href.startsWith('#') ? href.slice(1) : href.split('#')[0]);
}

/** Los destinos del menú en el idioma actual (llamar en contexto de inyección). */
export function destinosMenu(): Record<Destino, Enlace> {
  return traducir(menu.destinos);
}

/** Los enlaces de una lista del menú, en su orden y sin los que llevan a secciones ocultas. */
export function enlacesDeLista(destinos: Record<Destino, Enlace>, lista: ListaMenu): Enlace[] {
  return (menu.listas[lista] as Destino[]).map((d) => destinos[d]).filter((e) => enlaceVisible(e.href));
}
