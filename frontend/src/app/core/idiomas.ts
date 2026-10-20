/* ============ IDIOMAS ============
   Un solo build: cada idioma es un prefijo de ruta ('' = español en la raíz, 'en/' = inglés)
   y cada página se prerenderiza en todos. El contenido vive en src/contenido/*.json,
   con los textos como { "es": …, "en": … } (ver core/idioma.ts). La dirección pública
   del sitio (para SEO y Open Graph) está en contenido/evento.json → sitioUrl. */

export type CodigoIdioma = 'es' | 'en';

export interface Idioma {
  codigo: CodigoIdioma;
  /** Lo que se ve en el selector. */
  corto: string;
  /** Nombre en su propio idioma, para lectores de pantalla. */
  nombre: string;
  /** Prefijo de ruta relativo al <base href>: '' = raíz; si no, con «/» final. */
  ruta: string;
  /** Para Open Graph (og:locale). */
  localeOg: string;
  /** Imagen al compartir (1200×630, en public/assets/; se genera aparte, ver CLAUDE.md). */
  imagenOg: string;
}

/** El primero es el idioma por defecto (sin prefijo). */
export const IDIOMAS: Idioma[] = [
  { codigo: 'es', corto: 'ES', nombre: 'Español', ruta: '', localeOg: 'es_CO', imagenOg: 'assets/og-es.jpg' },
  { codigo: 'en', corto: 'EN', nombre: 'English', ruta: 'en/', localeOg: 'en_US', imagenOg: 'assets/og-en.jpg' },
];
