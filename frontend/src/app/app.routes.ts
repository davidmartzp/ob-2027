import { Routes } from '@angular/router';
import { seccionVisible } from '@core/navegacion';
import { IDIOMAS } from '@core/idiomas';
import { Inicio } from '@paginas/inicio/inicio';
import { PaginaActividades } from '@paginas/actividades/pagina-actividades';
import { PaginaInscripciones } from '@paginas/inscripciones/pagina-inscripciones';

/* ============ RUTAS ============
   El sitio sigue siendo estático: cada ruta se prerenderiza como carpeta propia
   (dist/…/browser/actividades/index.html) y se repite bajo el prefijo de cada idioma
   (en/, en/actividades/). Un solo build: el idioma lo decide la URL (core/idioma.ts).
   /inscripciones existe siempre (para poder revisarla); el sitio solo enlaza a ella
   cuando evento.inscripcionesAbiertas es true.
   Los enlaces entre páginas son <a href> relativos al <base href>: recarga completa,
   sin depender del router en el navegador. */

const PAGINAS: Routes = [
  { path: '', component: Inicio },
  ...(seccionVisible('actividades') ? [{ path: 'actividades', component: PaginaActividades }] : []),
  { path: 'inscripciones', component: PaginaInscripciones },
];

export const routes: Routes = [
  ...PAGINAS,
  ...IDIOMAS.filter((i) => i.ruta).map((i) => ({ path: i.ruta.replace(/\/$/, ''), children: PAGINAS })),
  { path: '**', redirectTo: '' },
];
