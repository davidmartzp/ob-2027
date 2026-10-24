import { PlatformLocation } from '@angular/common';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { App } from './app';
import { crearPaginas, routes } from './app.routes';
import acerca from '@contenido/acerca.json';
import amigosPlegadores from '@contenido/amigos-plegadores.json';
import booklet from '@contenido/booklet.json';
import inscribirTalleres from '@contenido/inscribir-talleres.json';
import noEncontrada from '@contenido/no-encontrada.json';
import recursos from '@contenido/recursos.json';
import talleres from '@contenido/talleres.json';
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
/** Interruptores que son secciones de la home (los demás apagan páginas propias). */
const PAGINAS_PROPIAS = crearPaginas(() => true).map((r) => r.path);
const SECCIONES_HOME = Object.fromEntries(Object.entries(SECCIONES_VISIBLES).filter(([id]) => !PAGINAS_PROPIAS.includes(id)));
const IDS_ACTIVIDADES = actividades.items.map((a) => a.id);
/** Todos los JSON de contenido, para revisar que cada texto traducido tenga sus dos idiomas. */
const CONTENIDO: Record<string, unknown> = {
  acerca, actividades, comun, comunidad, evento, hero, invitados, itinerario, leyenda, marquee, menu, meta, patrocinadores, pie, tallerista,
  'amigos-plegadores': amigosPlegadores, booklet, 'inscribir-talleres': inscribirTalleres, 'no-encontrada': noEncontrada, recursos, talleres,
};

describe('App — la hoja completa', () => {
  async function render(url = '/'): Promise<HTMLElement> {
    await TestBed.configureTestingModule({ imports: [App], providers: [provideRouter(routes)] }).compileComponents();
    // el idioma sale de la URL al arrancar la app (IdiomaActual): fijarla antes de montarla
    TestBed.inject(PlatformLocation).pushState(null, '', url);
    const fixture = TestBed.createComponent(App);
    // como en el navegador: Location quita la «/» final (/en/ → /en), si no el router ve un segmento vacío
    await TestBed.inject(Router).navigateByUrl(url.replace(/(.)\/$/, '$1'));
    await fixture.whenStable();
    return fixture.nativeElement as HTMLElement;
  }

  it('monta las secciones visibles y oculta las desactivadas', async () => {
    const el = await render();
    for (const id of ['inicio', 'acerca', 'invitados', 'inscribete']) {
      expect(el.querySelector(`#${id}`), `#${id}`).toBeTruthy();
    }
    for (const [id, visible] of Object.entries(SECCIONES_HOME)) {
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
    expect(el.querySelectorAll('.patros > :is(a, span)').length).toBe(SECCIONES_VISIBLES.patrocinadores ? patrocinadores.lista.length : 0);
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

  describe('navegación entre páginas', () => {
    const rutas = crearPaginas().map((r) => r.path);

    it('cada destino del menú lleva a una página o a una sección que existe', async () => {
      const el = await render();
      for (const [id, destino] of Object.entries(menu.destinos)) {
        const [pagina, ancla] = destino.href.split('#');
        if (pagina) expect(rutas, `${id} → ${destino.href}`).toContain(pagina);
        else expect(el.querySelector(`#${ancla}`), `${id} → ${destino.href}`).toBeTruthy();
      }
      for (const [lista, ids] of Object.entries(menu.listas)) {
        for (const d of ids) expect(menu.destinos, `${lista} → ${d}`).toHaveProperty(d);
      }
    });

    it('cada página tiene título, descripción y palabras clave en meta.json', () => {
      for (const ruta of rutas.filter((r) => r && r !== '404')) {
        expect(meta, ruta!).toHaveProperty(ruta!);
        const m = (meta as unknown as Record<string, { titulo: unknown; descripcion: unknown; palabrasClave: unknown }>)[ruta!];
        expect(m.titulo && m.descripcion && m.palabrasClave, ruta!).toBeTruthy();
      }
    });

    it('un interruptor apagado quita la ruta y sus enlaces (talleres: false)', async () => {
      expect(crearPaginas((id) => id !== 'talleres').map((r) => r.path)).not.toContain('talleres');
      expect(rutas).toContain('talleres');
      const secciones = evento.secciones as Record<string, boolean>;
      const antes = secciones['talleres'];
      secciones['talleres'] = false;
      try {
        const el = await render();
        expect(el.querySelector('a[href="talleres"]')).toBeNull();
        expect(el.querySelector('a[href="booklet"]')).toBeTruthy(); // las demás siguen
      } finally {
        secciones['talleres'] = antes;
      }
    });
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

  describe('página de inscripciones', () => {
    const esperar = () => new Promise((r) => setTimeout(r, 0));
    async function escribir(el: HTMLElement, id: string, valor: string): Promise<void> {
      const campo = el.querySelector<HTMLInputElement | HTMLSelectElement>(`#insc-${id}`)!;
      campo.value = valor;
      campo.dispatchEvent(new Event(campo instanceof HTMLSelectElement ? 'change' : 'input'));
      await esperar();
    }
    async function pulsar(el: HTMLElement, selector: string): Promise<void> {
      el.querySelector<HTMLElement>(selector)!.click();
      await esperar();
    }
    const siguiente = (el: HTMLElement) => pulsar(el, '.navegacion button[type=submit]');
    const titulo = (el: HTMLElement) => el.querySelector('.paso-titulo')?.textContent?.trim();

    it('se prerenderiza con los precios, los métodos de pago y las preguntas', async () => {
      const el = await render('/inscripciones');
      expect(el.querySelectorAll('app-precios-inscripcion .fase').length).toBeGreaterThan(0);
      expect(el.querySelectorAll('app-metodos-pago .metodo').length).toBe(4);
      expect(el.querySelectorAll('app-preguntas-frecuentes details').length).toBeGreaterThan(0);
      expect(el.querySelectorAll('.pasos li').length).toBe(5);
    });

    it('no avanza con el paso incompleto y muestra los errores', async () => {
      const el = await render('/inscripciones');
      const primero = titulo(el);
      await siguiente(el);
      expect(titulo(el)).toBe(primero);
      expect(el.querySelector('.resumen[role=alert]')).toBeTruthy();
      expect(el.querySelector('#insc-name')?.getAttribute('aria-invalid')).toBe('true');
    });

    it('completa los cinco pasos, muestra los campos condicionales y termina sin conexión', async () => {
      const el = await render('/inscripciones');
      const info = vi.spyOn(console, 'info').mockImplementation(() => undefined);
      // 1 · personal
      await escribir(el, 'name', 'Ana'); await escribir(el, 'lastname', 'Ruiz');
      await escribir(el, 'age', '3a0'); await escribir(el, 'idname', 'Ana');
      expect(el.querySelector<HTMLInputElement>('#insc-age')!.value).toBe('30'); // solo dígitos
      await siguiente(el);
      // 2 · contacto
      await escribir(el, 'city', 'Bogotá'); await escribir(el, 'email', 'ana@correo.co'); await escribir(el, 'phone', '3001234567');
      await siguiente(el);
      // 3 · pago: con beca no hace falta comprobante
      await escribir(el, 'paymethod', 'Becado(a)');
      await siguiente(el);
      // 4 · participación
      await pulsar(el, 'input[name=days][value=T]');
      await siguiente(el);
      // 5 · opciones: la chiva despliega sus preguntas
      await escribir(el, 'info', 'Redes Sociales');
      expect(el.querySelector('#insc-chiva_accompanied')).toBeNull();
      const chiva = [...el.querySelectorAll<HTMLInputElement>('.tarjeta input[type=checkbox]')].at(-1)!;
      chiva.click(); await esperar();
      expect(el.querySelector('#insc-chiva_accompanied')).toBeTruthy();
      chiva.click(); await esperar();
      await siguiente(el); // en el último paso, el botón envía
      await new Promise((r) => setTimeout(r, 700));
      expect(el.querySelector('.exito')?.textContent).toContain('ana@correo.co');
      expect(el.querySelector('.exito .pend-nota')).toBeTruthy(); // aviso de modo sin conexión
      info.mockRestore();
    });
  });
});
