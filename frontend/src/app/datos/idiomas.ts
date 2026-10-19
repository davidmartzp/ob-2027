/* ============ IDIOMAS ============
   Un sitio estático por idioma (i18n de Angular al compilar). La ruta de cada
   idioma debe coincidir con su `subPath` en angular.json (proyecto → i18n). */

export interface Idioma {
  codigo: string;
  /** Lo que se ve en el selector. */
  corto: string;
  /** Nombre en su propio idioma, para lectores de pantalla. */
  nombre: string;
  /** Carpeta relativa a la raíz del sitio: '' = raíz. */
  ruta: string;
  /** Para Open Graph (og:locale). */
  localeOg: string;
  /** Imagen al compartir (1200×630, en public/assets/; se genera aparte, ver CLAUDE.md). */
  imagenOg: string;
}

/** Dirección pública del sitio, con «/» final: Open Graph exige URLs absolutas. */
export const SITIO_URL = 'https://origamibogota.com/';

export const IDIOMAS: Idioma[] = [
  { codigo: 'es', corto: 'ES', nombre: 'Español', ruta: '', localeOg: 'es_CO', imagenOg: 'assets/og-es.jpg' },
  { codigo: 'en', corto: 'EN', nombre: 'English', ruta: 'en/', localeOg: 'en_US', imagenOg: 'assets/og-en.jpg' },
];
