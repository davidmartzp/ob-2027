import { PlatformLocation } from '@angular/common';
import { DOCUMENT, inject, Injectable } from '@angular/core';
import evento from '@contenido/evento.json';
import { CodigoIdioma, Idioma, IDIOMAS } from './idiomas';

/* ============ IDIOMA DE LA PÁGINA Y CONTENIDO TRADUCIDO ============
   El idioma sale de la URL (/en/… = inglés; sin prefijo = español) y no cambia mientras
   la página está abierta: el selector de idioma navega a la otra copia prerenderizada.

   Todo el contenido vive en src/contenido/*.json. Regla única: cualquier objeto
   { "es": …, "en": … } es un valor traducido y se reemplaza por el del idioma actual,
   esté donde esté (un título, una lista de párrafos, el texto de un enlace…).
   Lo que no cambia entre idiomas (fotos, enlaces, ids) se escribe una sola vez.
   Uso (en un inicializador de campo o en el constructor):
     import contenido from '@contenido/acerca.json';
     protected readonly t = traducir(contenido); */

/** El tipo de un contenido ya traducido: cada { es, en } pasa a ser su valor. */
export type Traducido<T> = T extends { es: infer E; en: unknown }
  ? Traducido<E>
  : T extends readonly (infer U)[]
    ? Traducido<U>[]
    : T extends object
      ? { [K in keyof T]: Traducido<T[K]> }
      : T;

/** Marcadores que se rellenan solos en todos los textos: el año y la antigüedad del
 *  evento se cambian en un solo sitio (edicion y fundacion, contenido/evento.json). */
const VARIABLES: Record<string, string> = {
  edicion: String(evento.edicion),
  edicionAnterior: String(evento.edicion - 1),
  anios: String(evento.edicion - evento.fundacion),
};

@Injectable({ providedIn: 'root' })
export class IdiomaActual {
  readonly idioma: Idioma;
  /** Página actual relativa al <base href> y sin prefijo de idioma: '' en la home, 'actividades/'… */
  readonly pagina: string;

  constructor() {
    const ubicacion = inject(PlatformLocation);
    const base = ubicacion.getBaseHrefFromDOM() ?? '/';
    // sin la parte del <base href> y siempre sin «/» inicial (el base puede venir sin «/» final)
    const camino = (ubicacion.pathname.startsWith(base) ? ubicacion.pathname.slice(base.length) : ubicacion.pathname).replace(/^\//, '');
    this.idioma = IDIOMAS.find((i) => i.ruta && `${camino}/`.startsWith(i.ruta)) ?? IDIOMAS[0];
    this.pagina = camino.slice(this.idioma.ruta.length);
    inject(DOCUMENT).documentElement.lang = this.idioma.codigo;
  }

  /** Lleva un enlace interno al idioma actual: '#acerca' → 'en/#acerca', 'actividades' → 'en/actividades',
   *  './' → 'en/'. Externos, mailto: y tel: no cambian. Siempre relativos al <base href>. */
  enlace(href: string): string {
    if (!this.idioma.ruta || /^(https?:|mailto:|tel:)/.test(href)) return href;
    return this.idioma.ruta + (href === './' ? '' : href);
  }
}

/** El contenido en el idioma actual, con los marcadores ({edicion}, {anios}…) ya rellenos. */
export function traducir<T>(contenido: T): Traducido<T> {
  return localizar(contenido, inject(IdiomaActual).idioma.codigo) as Traducido<T>;
}

/** Sustituye {clave} por su valor: rellenar('{n} de {total}', { n: 3, total: 14 }). */
export function rellenar(texto: string, valores: Record<string, string | number>): string {
  return texto.replace(/\{(\w+)\}/g, (marca, clave: string) => (clave in valores ? String(valores[clave]) : marca));
}

/** El texto sin etiquetas (para buscar o para atributos): '¡el reto más <em>dulce</em>!' → '¡el reto más dulce!'. */
export const sinEtiquetas = (html: string): string => html.replace(/<[^>]*>/g, '');

function localizar(valor: unknown, codigo: CodigoIdioma): unknown {
  if (typeof valor === 'string') return rellenar(valor, VARIABLES);
  if (Array.isArray(valor)) return valor.map((v) => localizar(v, codigo));
  if (valor && typeof valor === 'object') {
    const objeto = valor as Record<string, unknown>;
    if (esTraduccion(objeto)) return localizar(objeto[codigo], codigo);
    return Object.fromEntries(Object.entries(objeto).map(([k, v]) => [k, localizar(v, codigo)]));
  }
  return valor;
}

/** { es, en } y nada más: así un objeto de datos que casualmente tenga otras claves no se confunde. */
function esTraduccion(objeto: Record<string, unknown>): boolean {
  const claves = Object.keys(objeto);
  return claves.length === IDIOMAS.length && IDIOMAS.every((i) => i.codigo in objeto);
}
