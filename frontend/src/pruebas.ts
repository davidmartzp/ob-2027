import { PlatformLocation } from '@angular/common';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { App } from './app/app';
import { routes } from './app/app.routes';

/* ============ AYUDAS PARA LAS PRUEBAS DE PÁGINAS ============
   Solo las importan los *.spec.ts. Sin zone.js: tras un cambio, `await esperar()`. */

/** Monta la app completa en `url` (el idioma sale de la URL) y devuelve su HTML. */
export async function montarPagina(url = '/'): Promise<HTMLElement> {
  await TestBed.configureTestingModule({ imports: [App], providers: [provideRouter(routes)] }).compileComponents();
  TestBed.inject(PlatformLocation).pushState(null, '', url);
  const fixture = TestBed.createComponent(App);
  // como en el navegador: Location quita la «/» final (/en/ → /en), si no el router ve un segmento vacío
  await TestBed.inject(Router).navigateByUrl(url.replace(/(.)\/$/, '$1'));
  await fixture.whenStable();
  return fixture.nativeElement as HTMLElement;
}

/** Deja correr las promesas y los timers pendientes (respuestas simuladas, repintado de signals). */
export const esperar = (ms = 0): Promise<void> => new Promise((r) => setTimeout(r, ms));

/** Un fetch simulado: cada ruta («GET workshops») responde con su JSON y su estado. */
export function simularApi(rutas: Record<string, unknown | ((cuerpo: unknown) => { status: number; json?: unknown })>) {
  return vi.fn(async (url: string, opciones: RequestInit = {}) => {
    const ruta = url.replace(/^https?:\/\/[^/]+\//, '');
    const clave = `${opciones.method ?? 'GET'} ${ruta}`;
    if (!(clave in rutas)) return new Response(JSON.stringify({ error: { code: 'not_found', message: `Sin simular: ${clave}` } }), { status: 404 });
    const valor = rutas[clave];
    const cuerpo = typeof opciones.body === 'string' ? JSON.parse(opciones.body) : opciones.body;
    const { status, json } = typeof valor === 'function' ? valor(cuerpo) : { status: 200, json: valor };
    return new Response(json === undefined ? null : JSON.stringify(json), { status });
  });
}
