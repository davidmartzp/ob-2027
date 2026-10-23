import { DOCUMENT, inject } from '@angular/core';
import { Meta, Title } from '@angular/platform-browser';
import evento from '@contenido/evento.json';
import textosMeta from '@contenido/meta.json';
import { Idioma, IDIOMAS } from './idiomas';
import { IdiomaActual, traducir } from './idioma';

/* ============ TÍTULO, DESCRIPCIÓN, SEO Y TARJETA AL COMPARTIR ============
   index.html no se traduce: cada página los fija al construirse y salen en el
   prerender. Incluye:
   · canonical + hreflang: cada idioma es una URL propia; así Google las entiende
     como traducciones de la misma página (no como contenido duplicado).
   · Open Graph (Facebook, WhatsApp, LinkedIn…) y Twitter/X, con la imagen del idioma.
   Todo con URLs absolutas sobre evento.sitioUrl; las páginas internas terminan en «/»
   (así las sirve Apache: /actividades → /actividades/).
   Título, descripción y palabras clave de cada página: contenido/meta.json.
   Uso (en el constructor): fijarMeta('actividades'); */

export function fijarMeta(pagina: '' | 'actividades' | 'inscripciones' = ''): void {
  const textos = traducir(textosMeta);
  const { titulo, descripcion, palabrasClave } = pagina ? textos[pagina] : textos.inicio;
  const idioma = inject(IdiomaActual).idioma;
  const meta = inject(Meta);
  inject(Title).setTitle(titulo);

  const ruta = pagina ? `${pagina}/` : '';
  const url = (i: Idioma): string => evento.sitioUrl + i.ruta + ruta;
  enlazar(inject(DOCUMENT), [
    { rel: 'canonical', href: url(idioma) },
    ...IDIOMAS.map((i) => ({ rel: 'alternate', hreflang: i.codigo, href: url(i) })),
    { rel: 'alternate', hreflang: 'x-default', href: url(IDIOMAS[0]) },
  ]);

  const etiquetas: [string, string][] = [
    ['description', descripcion],
    ['keywords', palabrasClave],
    ['og:type', 'website'],
    ['og:site_name', `Origami Bogotá ${evento.edicion}`],
    ['og:title', titulo],
    ['og:description', descripcion],
    ['og:url', url(idioma)],
    ['og:locale', idioma.localeOg],
    ['og:image', evento.sitioUrl + idioma.imagenOg],
    ['og:image:width', '1200'],
    ['og:image:height', '630'],
    ['og:image:alt', textos.imagenAlt],
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
