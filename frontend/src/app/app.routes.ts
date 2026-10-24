import { Routes } from '@angular/router';
import { seccionVisible } from '@core/navegacion';
import { IDIOMAS } from '@core/idiomas';
import { Inicio } from '@paginas/inicio/inicio';
import { PaginaActividades } from '@paginas/actividades/pagina-actividades';
import { PaginaInscripciones } from '@paginas/inscripciones/pagina-inscripciones';
import { PaginaRecursos } from '@paginas/recursos/pagina-recursos';
import { PaginaAmigosPlegadores } from '@paginas/amigos-plegadores/pagina-amigos-plegadores';
import { PaginaBooklet } from '@paginas/booklet/pagina-booklet';
import { PaginaTalleres } from '@paginas/talleres/pagina-talleres';
import { NoEncontrada } from '@paginas/no-encontrada/no-encontrada';

/* ============ RUTAS ============
   El sitio sigue siendo estático: cada ruta se prerenderiza como carpeta propia
   (dist/…/browser/actividades/index.html) y se repite bajo el prefijo de cada idioma
   (en/, en/actividades/). Un solo build: el idioma lo decide la URL (core/idioma.ts).
   Una ruta inexistente muestra NoEncontrada (sin redirigir); /404 existe para prerenderizarla
   y que el hosting la sirva con estado 404 (public/.htaccess).
   /inscripciones existe siempre (para poder revisarla); el sitio solo enlaza a ella
   cuando evento.inscripcionesAbiertas es true.
   Los enlaces entre páginas son <a href> relativos al <base href>: recarga completa,
   sin depender del router en el navegador. */

const PAGINAS: Routes = [
  { path: '', component: Inicio },
  ...(seccionVisible('actividades') ? [{ path: 'actividades', component: PaginaActividades }] : []),
  { path: 'inscripciones', component: PaginaInscripciones },
  ...(seccionVisible('recursos') ? [{ path: 'recursos', component: PaginaRecursos }] : []),
  ...(seccionVisible('talleres') ? [{ path: 'talleres', component: PaginaTalleres }] : []),
  ...(seccionVisible('booklet') ? [{ path: 'booklet', component: PaginaBooklet }] : []),
  ...(seccionVisible('amigos-plegadores') ? [{ path: 'amigos-plegadores', component: PaginaAmigosPlegadores }] : []),
  { path: '404', component: NoEncontrada },
];

/** Al final de cada idioma: así /en/no-existe sale en inglés. */
const NO_ENCONTRADA: Routes = [{ path: '**', component: NoEncontrada }];

export const routes: Routes = [
  ...IDIOMAS.filter((i) => i.ruta).map((i) => ({ path: i.ruta.replace(/\/$/, ''), children: [...PAGINAS, ...NO_ENCONTRADA] })),
  ...PAGINAS,
  ...NO_ENCONTRADA,
];
