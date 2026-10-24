import evento from '@contenido/evento.json';
import talleres from '@contenido/talleres.json';
import { fijarApiUrl } from '@core/api';
import { TallerGaleriaApi, TallerPropioApi } from '@core/talleres';
import { esperar, montarPagina, simularApi } from '../../../pruebas';

const SOCIO = { id: 5, name: 'Papelería Ruana', logo: null, whatsapp: '573001112233', message: 'Hola', whatsapp_url: 'https://wa.me/573001112233?text=Hola' };
const GALERIA: TallerGaleriaApi[] = [
  { id: 1, name: 'Grulla', level: 'P', public: 'A', duration: 60, observations: null, image: null, instructor: { id: 1, name: 'Talía', lastname: 'Prueba Gómez' }, partners: [SOCIO] },
  { id: 2, name: 'Dragón', level: 'A', public: 'A', duration: 120, observations: 'Papel 60 cm', image: 'http://api.test/images/w-2.jpg', instructor: { id: 2, name: 'Ana', lastname: 'Ruiz' }, partners: [] },
];
const propio = (id: number, nombre: string, cambios: Partial<TallerPropioApi> = {}): TallerPropioApi =>
  ({ id, name: nombre, level: 'I', public: 'A', duration: 60, observations: null, image: null, status: 1, partners: [], ...cambios });

describe('página de talleres', () => {
  const abiertas = evento.inscripcionesAbiertas;
  const secciones = evento.secciones as Record<string, boolean>;
  const internacionales = secciones['talleres-internacionales'];
  beforeEach(() => {
    fijarApiUrl('http://api.test');
    (evento as { inscripcionesAbiertas: boolean }).inscripcionesAbiertas = true;
    secciones['talleres-internacionales'] = true;
  });
  afterEach(() => {
    vi.unstubAllGlobals();
    fijarApiUrl(evento.apiUrl);
    (evento as { inscripcionesAbiertas: boolean }).inscripcionesAbiertas = abiertas;
    secciones['talleres-internacionales'] = internacionales;
  });

  async function abrir(rutas: Parameters<typeof simularApi>[0] = {}): Promise<{ el: HTMLElement; fetch: ReturnType<typeof simularApi> }> {
    const fetch = simularApi({ 'GET workshops': GALERIA, ...rutas });
    vi.stubGlobal('fetch', fetch);
    const el = await montarPagina('/talleres');
    await esperar(); await esperar();
    return { el, fetch };
  }
  async function escribir(el: HTMLElement, selector: string, valor: string): Promise<void> {
    const campo = el.querySelector<HTMLInputElement>(selector)!;
    campo.value = valor;
    campo.dispatchEvent(new Event('input'));
    await esperar();
  }
  async function pulsar(el: HTMLElement | Element, selector: string): Promise<void> {
    el.querySelector<HTMLElement>(selector)!.click();
    await esperar(); await esperar();
  }
  async function buscarCorreo(el: HTMLElement, correo = 'familia@correo.co'): Promise<void> {
    await escribir(el, '#tallerista-correo', correo);
    el.querySelector<HTMLFormElement>('.busqueda')!.dispatchEvent(new Event('submit'));
    await esperar(); await esperar();
  }

  describe('Paso I y II', () => {
    it('los internacionales por confirmar muestran el aviso ✳', async () => {
      const { el } = await abrir();
      const tarjetas = [...el.querySelectorAll('.internacional')];
      expect(tarjetas.length).toBe(talleres.internacionales.lista.length);
      for (const [i, t] of talleres.internacionales.lista.entries()) {
        expect(!!tarjetas[i].querySelector('.pend-nota'), t.id).toBe(!!t.porConfirmar);
      }
      expect(el.querySelector('#thunder-bird h3')?.textContent).toBe('Thunder Bird');
    });

    it('con el interruptor apagado no hay sección de internacionales', async () => {
      secciones['talleres-internacionales'] = false;
      const { el } = await abrir();
      expect(el.querySelector('#internacionales')).toBeNull();
      expect(el.querySelector('#galeria')).toBeTruthy();
    });

    it('galería: con foto, sin foto (papel plegado) y socios con WhatsApp', async () => {
      const { el } = await abrir();
      const fichas = [...el.querySelectorAll('.cuadricula app-ficha-taller')];
      expect(fichas.length).toBe(2);
      expect(fichas[0].querySelector('app-papel-plegado')).toBeTruthy();
      expect(fichas[1].querySelector('img')?.getAttribute('src')).toBe('http://api.test/images/w-2.jpg');
      expect(fichas[0].querySelector('.tallerista')?.textContent).toContain('Talía Prueba');
      expect(fichas[0].querySelector<HTMLAnchorElement>('app-socios-taller a')?.getAttribute('rel')).toBe('noopener');
    });

    it('filtra por nivel y abre la ficha ampliada', async () => {
      const { el } = await abrir();
      [...el.querySelectorAll<HTMLButtonElement>('.galeria .chip')].find((c) => c.textContent === 'Avanzado')!.click();
      await esperar();
      expect([...el.querySelectorAll('.cuadricula h3')].map((h) => h.textContent)).toEqual(['Dragón']);
      await pulsar(el, '.cuadricula .ver');
      const dialogo = el.querySelector('dialog.ampliada')!;
      expect(dialogo.hasAttribute('open')).toBe(true);
      expect(dialogo.querySelector('.observaciones')?.textContent).toContain('Papel 60 cm');
    });

    it('sin API: aviso de demostración en la galería y en el espacio del tallerista', async () => {
      fijarApiUrl(null);
      const el = await montarPagina('/talleres');
      expect(el.querySelectorAll('app-estado-datos[data-estado=demo]').length).toBe(2);
      expect(el.querySelector('.internacional')).toBeTruthy();
    });
  });

  describe('Paso III · espacio del tallerista', () => {
    it('cerrado mientras no haya inscripciones', async () => {
      (evento as { inscripcionesAbiertas: boolean }).inscripcionesAbiertas = false;
      const { el } = await abrir();
      expect(el.querySelector('app-espacio-tallerista app-estado-datos[data-estado=cerrado]')).toBeTruthy();
      expect(el.querySelector('#tallerista-correo')).toBeNull();
    });

    it('familia con un correo: un bloque por persona con sus talleres', async () => {
      const { el } = await abrir({
        'POST workshops/by-email': [
          { id: 7, name: 'Ana Ruiz', workshops: [propio(10, 'Grulla'), propio(11, 'Retirado', { status: 0 }), propio(12, 'Oculto', { status: 2 })] },
          { id: 8, name: 'Tomás Ruiz', workshops: [] },
        ],
      });
      await buscarCorreo(el);
      const personas = [...el.querySelectorAll('.persona')];
      expect(personas.map((p) => p.querySelector('h3')?.textContent)).toEqual(['Talleres de Ana Ruiz', 'Talleres de Tomás Ruiz']);
      expect([...personas[0].querySelectorAll('.taller h4')].map((h) => h.textContent)).toEqual(['Grulla', 'Oculto']);
      expect(personas[0].querySelector('[data-taller="12"] .pend-nota')).toBeTruthy();
      expect(personas[1].querySelector('.sin-talleres')).toBeTruthy();
    });

    it('correo no inscrito: lo dice y enlaza a inscripciones', async () => {
      const { el } = await abrir({ 'POST workshops/by-email': [] });
      await buscarCorreo(el, 'nadie@correo.co');
      expect(el.querySelector('.sin-inscripcion')?.textContent).toContain('No encontramos');
      expect(el.querySelector('.sin-inscripcion a')?.getAttribute('href')).toBe('inscripciones');
    });

    it('correo mal escrito no se envía', async () => {
      const { el, fetch } = await abrir();
      await buscarCorreo(el, 'no-es-correo');
      expect(el.querySelector('#tallerista-correo-error')).toBeTruthy();
      expect(fetch.mock.calls.some(([u]) => String(u).includes('by-email'))).toBe(false);
    });

    it('proponer: duración fuera de rango no se envía; corregida, se guarda', async () => {
      const creado = propio(20, 'Rana', { level: 'P' });
      const { el, fetch } = await abrir({
        'POST workshops/by-email': [{ id: 7, name: 'Ana Ruiz', workshops: [] }],
        'POST attendees/7/workshops': () => ({ status: 201, json: [creado] }),
      });
      await buscarCorreo(el);
      await pulsar(el, '.proponer');
      await escribir(el, '#taller-name', 'Rana');
      await pulsar(el, 'input[name=taller-nivel][value=P]');
      await pulsar(el, 'input[name=taller-publico][value=A]');
      await escribir(el, '#taller-duration', '5');
      el.querySelector<HTMLFormElement>('form.formulario')!.dispatchEvent(new Event('submit'));
      await esperar();
      expect(el.querySelector('#taller-duration-error')?.textContent).toContain('entre 15 y 480');
      expect(fetch.mock.calls.some(([u, o]) => String(u).endsWith('attendees/7/workshops') && o?.method === 'POST')).toBe(false);

      await escribir(el, '#taller-duration', '45');
      el.querySelector<HTMLFormElement>('form.formulario')!.dispatchEvent(new Event('submit'));
      await esperar(); await esperar();
      const envio = fetch.mock.calls.find(([u]) => String(u).endsWith('attendees/7/workshops'))!;
      expect(JSON.parse(envio[1]!.body as string)).toEqual({ workshops: [{ name: 'Rana', level: 'P', public: 'A', duration: 45, observations: '' }] });
      expect([...el.querySelectorAll('.taller h4')].map((h) => h.textContent)).toEqual(['Rana']);
      expect(el.querySelector('form.formulario')).toBeNull();
    });

    it('un 422 del backend sale junto a su campo y conserva lo escrito', async () => {
      const { el } = await abrir({
        'POST workshops/by-email': [{ id: 7, name: 'Ana Ruiz', workshops: [propio(10, 'Grulla')] }],
        'PUT workshops/10': () => ({ status: 422, json: { error: { code: 'validation_failed', message: 'Revisa', fields: { name: 'Ese nombre no sirve.' } } } }),
      });
      await buscarCorreo(el);
      await pulsar(el, '[data-taller="10"] .botones button');
      await escribir(el, '#taller-name', 'Grulla nueva');
      el.querySelector<HTMLFormElement>('form.formulario')!.dispatchEvent(new Event('submit'));
      await esperar(); await esperar();
      expect(el.querySelector('#taller-name-error')?.textContent).toBe('Ese nombre no sirve.');
      expect(el.querySelector<HTMLInputElement>('#taller-name')!.value).toBe('Grulla nueva');
    });

    it('retirar pide confirmación y quita el taller', async () => {
      const { el, fetch } = await abrir({
        'POST workshops/by-email': [{ id: 7, name: 'Ana Ruiz', workshops: [propio(10, 'Grulla')] }],
        'DELETE workshops/10': () => ({ status: 204 }),
      });
      await buscarCorreo(el);
      await pulsar(el, '.retirar');
      const dialogo = el.querySelector('dialog.confirmacion')!;
      expect(dialogo.hasAttribute('open')).toBe(true);
      expect(dialogo.textContent).toContain('«Grulla»');
      expect(fetch.mock.calls.some(([, o]) => o?.method === 'DELETE')).toBe(false);
      await pulsar(dialogo, '.si');
      expect(el.querySelector('[data-taller="10"]')).toBeNull();
      expect(dialogo.hasAttribute('open')).toBe(false);
    });

    describe('foto del taller', () => {
      async function elegir(el: HTMLElement, archivo: File): Promise<void> {
        const entrada = el.querySelector<HTMLInputElement>('.panel.foto input[type=file]')!;
        Object.defineProperty(entrada, 'files', { value: [archivo], configurable: true });
        entrada.dispatchEvent(new Event('change'));
        await esperar();
      }
      const archivo = (tipo: string, bytes: number) => new File([new Uint8Array(bytes)], 'foto', { type: tipo });

      async function abrirFoto(rutas: Parameters<typeof simularApi>[0] = {}) {
        const r = await abrir({ 'POST workshops/by-email': [{ id: 7, name: 'Ana Ruiz', workshops: [propio(10, 'Grulla', { image: 'http://api.test/images/vieja.jpg' })] }], ...rutas });
        await buscarCorreo(r.el);
        await pulsar(r.el, '[data-taller="10"] .botones button:nth-child(2)');
        return r;
      }

      it('rechaza en el navegador otro tipo o más de 5 MB, sin subir nada', async () => {
        const { el, fetch } = await abrirFoto();
        await elegir(el, archivo('image/gif', 10));
        expect(el.querySelector('#foto-error')?.textContent).toContain('JPEG, PNG o WebP');
        await elegir(el, archivo('image/png', 5 * 1024 * 1024 + 1));
        expect(el.querySelector('#foto-error')?.textContent).toContain('5 MB');
        expect(el.querySelector<HTMLButtonElement>('.panel.foto .btn')!.disabled).toBe(true);
        expect(fetch.mock.calls.some(([u]) => String(u).includes('/image'))).toBe(false);
      });

      it('un 422 del backend se muestra y la foto anterior se conserva', async () => {
        const { el } = await abrirFoto({
          'POST workshops/10/image': () => ({ status: 422, json: { error: { code: 'validation_failed', message: 'Revisa', fields: { image: 'Solo se aceptan imágenes JPEG, PNG o WebP.' } } } }),
        });
        await elegir(el, archivo('image/jpeg', 100));
        await pulsar(el, '.panel.foto .btn');
        expect(el.querySelector('#foto-error')?.textContent).toBe('Solo se aceptan imágenes JPEG, PNG o WebP.');
        expect(el.querySelector('[data-taller="10"] .miniatura img')?.getAttribute('src')).toBe('http://api.test/images/vieja.jpg');
      });

      it('subida correcta: envía multipart y cambia la miniatura', async () => {
        const { el, fetch } = await abrirFoto({ 'POST workshops/10/image': () => ({ status: 200, json: { image: 'http://api.test/images/nueva.jpg' } }) });
        await elegir(el, archivo('image/webp', 100));
        await pulsar(el, '.panel.foto .btn');
        const subida = fetch.mock.calls.find(([u]) => String(u).endsWith('/image'))!;
        expect(subida[1]!.body).toBeInstanceOf(FormData);
        expect(el.querySelector('[data-taller="10"] .miniatura img')?.getAttribute('src')).toBe('http://api.test/images/nueva.jpg');
      });
    });

    it('socios: marca los de la lista pública y guarda con PUT', async () => {
      const otro = { ...SOCIO, id: 6, name: 'Tienda Páramo' };
      const { el, fetch } = await abrir({
        'POST workshops/by-email': [{ id: 7, name: 'Ana Ruiz', workshops: [propio(10, 'Grulla', { partners: [SOCIO] })] }],
        'GET partners': [SOCIO, otro],
        'PUT workshops/10/partners': (cuerpo: unknown) => ({ status: 200, json: [SOCIO, otro].filter((s) => (cuerpo as { partner_ids: number[] }).partner_ids.includes(s.id)) }),
      });
      await buscarCorreo(el);
      await pulsar(el, '[data-taller="10"] .botones button:nth-child(3)');
      await esperar();
      const casillas = [...el.querySelectorAll<HTMLInputElement>('.panel.socios input[type=checkbox]')];
      expect(casillas.map((c) => c.checked)).toEqual([true, false]);
      casillas[1].click();
      await esperar();
      await pulsar(el, '.panel.socios .btn');
      const envio = fetch.mock.calls.find(([u]) => String(u).endsWith('workshops/10/partners'))!;
      expect(JSON.parse(envio[1]!.body as string)).toEqual({ partner_ids: [5, 6] });
      expect(el.querySelector('[data-taller="10"] .socios-lista')?.textContent).toContain('Tienda Páramo');
    });
  });
});
