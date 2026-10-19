import { DOCUMENT, inject, LOCALE_ID } from '@angular/core';
import { Meta, Title } from '@angular/platform-browser';
import { IDIOMAS, SITIO_URL } from '../datos/idiomas';

/* ============ TÍTULO, DESCRIPCIÓN, SEO Y TARJETA AL COMPARTIR ============
   index.html no se traduce: cada página los fija al construirse y salen en el
   prerender. Incluye:
   · canonical + hreflang: cada idioma es una URL propia; así Google las entiende
     como traducciones de la misma página (no como contenido duplicado).
   · Open Graph (Facebook, WhatsApp, LinkedIn…) y Twitter/X, con la imagen del idioma.
   Todo con URLs absolutas sobre SITIO_URL; las páginas internas terminan en «/»
   (así las sirve Apache: /actividades → /actividades/).
   Uso (en el constructor): fijarMeta($localize`…`, $localize`…`, 'actividades'); */

export function fijarMeta(titulo: string, descripcion: string, pagina = ''): void {
  const idioma = IDIOMAS.find((i) => i.codigo === inject(LOCALE_ID)) ?? IDIOMAS[0];
  const meta = inject(Meta);
  inject(Title).setTitle(titulo);

  const ruta = pagina ? `${pagina}/` : '';
  const url = (i: typeof idioma): string => SITIO_URL + i.ruta + ruta;
  enlazar(inject(DOCUMENT), [
    { rel: 'canonical', href: url(idioma) },
    ...IDIOMAS.map((i) => ({ rel: 'alternate', hreflang: i.codigo, href: url(i) })),
    { rel: 'alternate', hreflang: 'x-default', href: url(IDIOMAS[0]) },
  ]);

  const etiquetas: [string, string][] = [
    ['description', descripcion],
    ['og:type', 'website'],
    ['og:site_name', 'Origami Bogotá 2027'],
    ['og:title', titulo],
    ['og:description', descripcion],
    ['og:url', url(idioma)],
    ['og:locale', idioma.localeOg],
    ['og:image', SITIO_URL + idioma.imagenOg],
    ['og:image:width', '1200'],
    ['og:image:height', '630'],
    ['og:image:alt', $localize`:@@meta.imagenAlt:Origami Bogotá 2027, del 4 al 6 de junio en el Liceo Francés Louis Pasteur, junto a un toro alado de origami`],
    ['twitter:card', 'summary_large_image'],
  ];
  // og:* va con property=…; el resto, con name=…
  for (const [clave, valor] of etiquetas) {
    const atributo = clave.startsWith('og:') ? 'property' : 'name';
    meta.updateTag({ [atributo]: clave, content: valor }, `${atributo}="${clave}"`);
  }
}

/** Reemplaza los <link rel="canonical|alternate"> del <head> por los de esta página. */
function enlazar(documento: Document, enlaces: Record<string, string>[]): void {
  documento.head.querySelectorAll('link[rel="canonical"], link[rel="alternate"][hreflang]').forEach((l) => l.remove());
  for (const atributos of enlaces) {
    const link = documento.createElement('link');
    for (const [clave, valor] of Object.entries(atributos)) link.setAttribute(clave, valor);
    documento.head.appendChild(link);
  }
}
