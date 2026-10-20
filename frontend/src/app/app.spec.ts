import { PlatformLocation } from '@angular/common';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { App } from './app';
import { routes } from './app.routes';
import acerca from '@contenido/acerca.json';
import actividades from '@contenido/actividades.json';
import comun from '@contenido/comun.json';
import comunidad from '@contenido/comunidad.json';
import evento from '@contenido/evento.json';
import hero from '@contenido/hero.json';
import invitados from '@contenido/invitados.json';
import itinerario from '@contenido/itinerario.json';
import leyenda from '@contenido/leyenda.json';
import marquee from '@contenido/marquee.json';
import menu from '@contenido/menu.json';
import meta from '@contenido/meta.json';
import patrocinadores from '@contenido/patrocinadores.json';
import pie from '@contenido/pie.json';
import tallerista from '@contenido/tallerista.json';
import { sinEtiquetas } from '@core/idioma';
import { IDIOMAS } from '@core/idiomas';

const SECCIONES_VISIBLES = evento.secciones;
const IDS_ACTIVIDADES = actividades.items.map((a) => a.id);
/** Todos los JSON de contenido, para revisar que cada texto traducido tenga sus dos idiomas. */
const CONTENIDO: Record<string, unknown> = { acerca, actividades, comun, comunidad, evento, hero, invitados, itinerario, leyenda, marquee, menu, meta, patrocinadores, pie, tallerista };

describe('App — la hoja completa', () => {
  async function render(url = '/'): Promise<HTMLElement> {
    await TestBed.configureTestingModule({ imports: [App], providers: [provideRouter(routes)] }).compileComponents();
    // el idioma sale de la URL al arrancar la app (IdiomaActual): fijarla antes de montarla
    TestBed.inject(PlatformLocation).pushState(null, '', url);
    const fixture = TestBed.createComponent(App);
    await TestBed.inject(Router).navigateByUrl(url);
    await fixture.whenStable();
    return fixture.nativeElement as HTMLElement;
  }

  it('monta las secciones visibles y oculta las desactivadas', async () => {
    const el = await render();
    for (const id of ['inicio', 'acerca', 'invitados', 'inscribete']) {
      expect(el.querySelector(`#${id}`), `#${id}`).toBeTruthy();
    }
    for (const [id, visible] of Object.entries(SECCIONES_VISIBLES)) {
      expect(!!el.querySelector(`#${id}`), `#${id}`).toBe(visible);
    }
  });

  it('no deja enlaces hacia secciones ocultas', async () => {
    const el = await render();
    for (const [id, visible] of Object.entries(SECCIONES_VISIBLES)) {
      if (!visible) expect(el.querySelector(`a[href="#${id}"]`), `enlace a #${id}`).toBeNull();
    }
  });

  it('pinta una tarjeta por invitado y solo las actividades destacadas en la home', async () => {
    const el = await render();
    expect(el.querySelectorAll('.invitado-card').length).toBe(invitados.lista.length);
    const filas = [...el.querySelectorAll<HTMLAnchorElement>('.lista-actividades > li a')];
    expect(filas.length).toBe(SECCIONES_VISIBLES.actividades ? actividades.destacadas.length : 0);
    for (const a of filas) expect(a.getAttribute('href')).toMatch(/^actividades#/);
  });

  it('marca como provisional el contenido no confirmado', async () => {
    const el = await render();
    const provisionales = invitados.lista.filter((i) => i.provisional).length;
    expect(el.querySelectorAll('.invitado-card .pend-nota').length).toBe(provisionales);
    expect(!!el.querySelector('.invitados .aviso')).toBe(provisionales > 0);
  });

  it('abre el itinerario completo en un modal y lo cierra', async () => {
    if (!SECCIONES_VISIBLES.itinerario) return; // sección oculta: el modal no se monta
    const el = await render();
    const dialogo = el.querySelector<HTMLDialogElement>('app-itinerario-modal dialog')!;
    expect(dialogo.hasAttribute('open')).toBe(false);

    el.querySelector<HTMLButtonElement>('.itinerario .acciones button')!.click();
    expect(dialogo.hasAttribute('open')).toBe(true);
    expect(dialogo.querySelectorAll('.momentos > li').length).toBe(itinerario.momentos.length);
    expect(dialogo.querySelector('.credito')?.textContent).toContain('Camilo Torres');

    dialogo.querySelector<HTMLButtonElement>('.cerrar')!.click();
    expect(dialogo.hasAttribute('open')).toBe(false);
  });

  it('ofrece cambiar de idioma sin salir del sitio estático', async () => {
    const el = await render();
    const enlaces = [...el.querySelectorAll<HTMLAnchorElement>('app-selector-idioma a')];
    expect(enlaces.map((a) => a.getAttribute('hreflang'))).toEqual(IDIOMAS.map((i) => i.codigo));
    expect(enlaces.filter((a) => a.getAttribute('aria-current')).length).toBe(1);
    // relativos al <base href>: nunca absolutos, para que funcione en una subcarpeta
    for (const a of enlaces) expect(a.getAttribute('href')).not.toMatch(/^\//);
  });

  it('bajo /en/ sale en inglés, con enlaces que se quedan en inglés', async () => {
    const el = await render('/en/');
    expect(el.querySelector('#acerca h2')?.textContent).toContain('An event made for');
    const internos = [...el.querySelectorAll<HTMLAnchorElement>('a[href^="#"], a[href^="actividades"]')];
    expect(internos.map((a) => a.getAttribute('href')), 'enlaces internos sin prefijo en/').toEqual([]);
  });

  it('pinta todos los patrocinadores del contenido', async () => {
    const el = await render();
    expect(el.querySelectorAll('.patros span').length).toBe(SECCIONES_VISIBLES.patrocinadores ? patrocinadores.lista.length : 0);
  });

  it('cada texto traducido del contenido tiene sus dos idiomas', () => {
    const incompletos: string[] = [];
    const revisar = (valor: unknown, ruta: string): void => {
      if (Array.isArray(valor)) return valor.forEach((v, i) => revisar(v, `${ruta}[${i}]`));
      if (!valor || typeof valor !== 'object') return;
      const objeto = valor as Record<string, unknown>;
      const idiomas = IDIOMAS.filter((i) => i.codigo in objeto);
      if (idiomas.length && idiomas.length < IDIOMAS.length) incompletos.push(ruta);
      for (const [clave, v] of Object.entries(objeto)) revisar(v, `${ruta}.${clave}`);
    };
    for (const [archivo, contenido] of Object.entries(CONTENIDO)) revisar(contenido, `${archivo}.json`);
    expect(incompletos, 'textos con un solo idioma').toEqual([]);
  });

  it('la leyenda documenta los símbolos en uso', async () => {
    const el = await render();
    expect(el.querySelectorAll('app-leyenda svg.simb').length).toBe(SECCIONES_VISIBLES.itinerario ? 3 : 2);
  });

  describe('página de actividades', () => {
    if (!SECCIONES_VISIBLES.actividades) return;

    async function buscar(el: HTMLElement, texto: string): Promise<void> {
      const campo = el.querySelector<HTMLInputElement>('#buscar-actividad')!;
      campo.value = texto;
      campo.dispatchEvent(new Event('input'));
      await new Promise((r) => setTimeout(r)); // sin zone.js: deja que se pinten los signals
    }
    const fichas = (el: HTMLElement): string[] => [...el.querySelectorAll('.fichas > li')].map((li) => li.id);

    it('muestra todas las fichas completas, con su texto en el HTML', async () => {
      const el = await render('/actividades');
      expect(fichas(el)).toEqual(IDS_ACTIVIDADES);
      expect(el.querySelectorAll('.fichas details').length).toBe(IDS_ACTIVIDADES.length);
      const chocogami = actividades.items.find((a) => a.id === 'chocogami')!;
      expect(el.querySelector('#chocogami .prosa')?.textContent).toContain(sinEtiquetas(chocogami.parrafos.es[0]));
    });

    it('busca sin importar tildes ni mayúsculas', async () => {
      const el = await render('/actividades');
      await buscar(el, 'BOGOTA');
      expect(fichas(el)).toContain('explora-bogota');
      expect(fichas(el).length).toBeLessThan(IDS_ACTIVIDADES.length);
      expect(el.querySelector('#contador-actividades')?.textContent).toMatch(/de/);
    });

    it('filtra por categoría y combina con la búsqueda', async () => {
      const el = await render('/actividades');
      const chip = [...el.querySelectorAll<HTMLButtonElement>('.filtros .chip')].find((b) => b.textContent?.includes('Convocatorias'))!;
      chip.click();
      await new Promise((r) => setTimeout(r));
      expect(chip.getAttribute('aria-pressed')).toBe('true');
      expect(fichas(el)).toEqual(actividades.items.filter((a) => a.categoria === 'convocatorias').map((a) => a.id));
    });

    it('sin resultados muestra el estado vacío y lo limpia', async () => {
      const el = await render('/actividades');
      await buscar(el, 'zzzz-no-existe');
      expect(fichas(el)).toEqual([]);
      expect(el.querySelector('.vacio')).toBeTruthy();
      el.querySelector<HTMLButtonElement>('.vacio button')!.click();
      await new Promise((r) => setTimeout(r));
      expect(fichas(el).length).toBe(IDS_ACTIVIDADES.length);
    });
  });
});
