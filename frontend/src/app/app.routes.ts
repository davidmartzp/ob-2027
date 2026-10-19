import { Routes } from '@angular/router';
import { SECCIONES_VISIBLES } from './datos/evento';
import { Inicio } from './paginas/inicio/inicio';
import { PaginaActividades } from './paginas/actividades/pagina-actividades';

/* ============ RUTAS ============
   El sitio sigue siendo estático: cada ruta se prerenderiza como carpeta propia
   (dist/…/browser/actividades/index.html) y en cada idioma (en/actividades/).
   Los enlaces entre páginas son <a href> relativos al <base href>: recarga completa,
   sin depender del router en el navegador. */

export const routes: Routes = [
  { path: '', component: Inicio },
  ...(SECCIONES_VISIBLES.actividades ? [{ path: 'actividades', component: PaginaActividades }] : []),
  { path: '**', redirectTo: '' },
];
