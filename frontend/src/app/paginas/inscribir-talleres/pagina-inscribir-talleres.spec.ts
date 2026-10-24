import evento from '@contenido/evento.json';
import { fijarApiUrl } from '@core/api';
import { EventoActivoApi } from '@core/talleres';
import { esperar, montarPagina, simularApi } from '../../../pruebas';

const activo = (evento: number, taller: number, nombre: string, inicio: string, fin: string, cupos = 10, sala = 'Sala 1'): EventoActivoApi => ({
  event: { id: evento, day: 'Saturday', start_time: `${inicio}:00`, end_time: `${fin}:00` },
  workshop: { id: taller, name: nombre, level: 'I', public: 'A', duration: 60, image: null, tickets: cupos, partners: [], instructor: { id: 50 + taller, name: `Tallerista ${taller}` } },
  room: { id: 1, name: sala },
});

const ACTIVOS = [
  activo(1, 1, 'Grulla', '09:00', '10:00'),
  activo(2, 2, 'Rana', '09:00', '10:00', 0, 'Sala 2'),
  activo(3, 3, 'Dragón (parte 1)', '10:00', '11:00'),
  activo(4, 4, 'Dragón (parte 2)', '11:00', '12:00'),
  activo(5, 5, 'Pez', '09:30', '10:30', 5, 'Sala 3'),
];
const VALIDO = { valid: true, id: 7, code: 141, name: 'Ana Ruiz', message: 'Tu turno está abierto.' };
const CLAVE = 'origami.inscribir-talleres';

describe('página inscribir talleres', () => {
  const abierta = evento.inscripcionTalleresAbierta;
  beforeEach(() => {
    fijarApiUrl('http://api.test');
    (evento as { inscripcionTalleresAbierta: boolean }).inscripcionTalleresAbierta = true;
    sessionStorage.clear();
  });
  afterEach(() => {
    vi.unstubAllGlobals();
    fijarApiUrl(evento.apiUrl);
    (evento as { inscripcionTalleresAbierta: boolean }).inscripcionTalleresAbierta = abierta;
    sessionStorage.clear();
  });

  async function abrir(rutas: Parameters<typeof simularApi>[0] = {}) {
    const fetch = simularApi({ 'GET events/active': ACTIVOS, 'GET attendees/7/events': [], ...rutas });
    vi.stubGlobal('fetch', fetch);
    const el = await montarPagina('/inscribir-talleres');
    await esperar(); await esperar();
    return { el, fetch };
  }
  async function escribir(el: HTMLElement, sel: string, valor: string): Promise<void> {
    const c = el.querySelector<HTMLInputElement>(sel)!;
    c.value = valor;
    c.dispatchEvent(new Event('input'));
    await esperar();
  }
  async function verificar(el: HTMLElement): Promise<void> {
    await escribir(el, '#acceso-correo', 'ana@correo.co');
    await escribir(el, '#acceso-numero', '141');
    el.querySelector<HTMLFormElement>('.formulario-acceso')!.dispatchEvent(new Event('submit'));
    await esperar(); await esperar(); await esperar();
  }
  async function pulsar(el: Element, sel: string): Promise<void> {
    el.querySelector<HTMLElement>(sel)!.click();
    await esperar(); await esperar(); await esperar();
  }
  const nombres = (el: HTMLElement) => [...el.querySelectorAll('.talleres h3')].map((h) => h.textContent);

  it('cerrada: explica cuándo abre y no muestra el formulario', async () => {
    (evento as { inscripcionTalleresAbierta: boolean }).inscripcionTalleresAbierta = false;
    const { el } = await abrir();
    expect(el.querySelector('app-estado-datos[data-estado=cerrado]')?.textContent).toContain('08:00');
    expect(el.querySelector('#acceso-correo')).toBeNull();
  });

  describe('Paso I · acceso', () => {
    it('turno aún cerrado: muestra el mensaje del backend y no entra', async () => {
      const { el } = await abrir({ 'POST attendees/verify-code': { valid: false, message: 'Tu grupo inicia a partir de las 08:20' } });
      await verificar(el);
      expect(el.querySelector('.mensaje-acceso')?.textContent).toBe('Tu grupo inicia a partir de las 08:20');
      expect(el.querySelector('.mensaje-acceso')?.getAttribute('aria-live')).toBe('polite');
      expect(el.querySelector('#paso-hoy')).toBeNull();
      expect(sessionStorage.getItem(CLAVE)).toBeNull();
    });

    it('sin datos no llama al backend', async () => {
      const { el, fetch } = await abrir();
      el.querySelector<HTMLFormElement>('.formulario-acceso')!.dispatchEvent(new Event('submit'));
      await esperar();
      expect(el.querySelector('.mensaje-acceso')?.textContent).toContain('Escribe tu correo');
      expect(fetch.mock.calls.some(([u]) => String(u).includes('verify-code'))).toBe(false);
    });

    it('verificado: saluda, recuerda la sesión y «Salir» la borra', async () => {
      const { el } = await abrir({ 'POST attendees/verify-code': VALIDO });
      await verificar(el);
      expect(el.querySelector('.hola')?.textContent).toContain('Ana');
      expect(el.querySelector('.numero')?.textContent).toContain('141');
      expect(JSON.parse(sessionStorage.getItem(CLAVE)!)).toEqual({ id: 7, nombre: 'Ana Ruiz', numero: '141' });
      await pulsar(el, '.salir');
      expect(sessionStorage.getItem(CLAVE)).toBeNull();
      expect(el.querySelector('#acceso-correo')).toBeTruthy();
    });

    it('al volver a la página sigue viendo sus talleres sin escribir nada', async () => {
      sessionStorage.setItem(CLAVE, JSON.stringify({ id: 7, nombre: 'Ana Ruiz', numero: '141' }));
      const { el, fetch } = await abrir({ 'GET attendees/7/events': [1] });
      expect(fetch.mock.calls.some(([u]) => String(u).includes('verify-code'))).toBe(false);
      expect(el.querySelector('.mios')?.textContent).toContain('Grulla');
    });

    it('¿olvidaste tu número? muestra siempre el mensaje del backend, sin el número', async () => {
      const mensaje = 'Si ese correo está inscrito, te enviamos tu número de participante.';
      const { el, fetch } = await abrir({ 'POST attendees/remind-code': () => ({ status: 202, json: { message: mensaje } }) });
      await escribir(el, '#acceso-correo', 'ana@correo.co');
      await pulsar(el, '.olvido .btn');
      expect(el.querySelector('.recordatorio')?.textContent).toBe(mensaje);
      const envio = fetch.mock.calls.find(([u]) => String(u).endsWith('remind-code'))!;
      expect(JSON.parse(envio[1]!.body as string)).toEqual({ email: 'ana@correo.co' });
    });
  });

  describe('Paso II · talleres de hoy', () => {
    it('agrupa por franja y marca cada estado (inscrito, sin cupos, choque, libre)', async () => {
      const { el } = await abrir({ 'POST attendees/verify-code': VALIDO, 'GET attendees/7/events': [1] });
      await verificar(el);
      expect([...el.querySelectorAll('.franja .hora')].map((h) => h.textContent)).toEqual(['09:00', '09:30', '10:00', '11:00']);
      const estado = (ev: number) => el.querySelector(`[data-evento="${ev}"]`)?.getAttribute('data-estado');
      expect([1, 2, 5, 3].map(estado)).toEqual(['inscrito', 'lleno', 'choca', 'libre']);
      expect(el.querySelector('[data-evento="5"] .sello')?.textContent).toContain('Choca con «Grulla»');
    });

    it('filtra por salón, hora y tallerista', async () => {
      const { el } = await abrir({ 'POST attendees/verify-code': VALIDO });
      await verificar(el);
      const elegir = async (id: string, valor: string) => {
        const s = el.querySelector<HTMLSelectElement>(id)!;
        s.value = valor;
        s.dispatchEvent(new Event('change'));
        await esperar();
      };
      await elegir('#filtro-salon', 'Sala 1');
      expect(nombres(el)).toEqual(['Grulla', 'Dragón (parte 1)', 'Dragón (parte 2)']);
      await elegir('#filtro-hora', '10:00');
      expect(nombres(el)).toEqual(['Dragón (parte 1)']);
      await elegir('#filtro-tallerista', 'Tallerista 1');
      expect(el.querySelector('.sin-coincidencias')).toBeTruthy();
    });

    it('403 logout: borra la sesión local y muestra cuándo vuelve a abrir', async () => {
      sessionStorage.setItem(CLAVE, JSON.stringify({ id: 7, nombre: 'Ana Ruiz', numero: '141' }));
      const { el } = await abrir({
        'GET events/active': () => ({ status: 403, json: { error: { code: 'enrolment_closed', message: 'La inscripción a talleres abre el domingo a las 08:00.' }, logout: true } }),
      });
      expect(sessionStorage.getItem(CLAVE)).toBeNull();
      expect(el.querySelector('#acceso-correo')).toBeTruthy();
      expect(el.querySelector('.mensaje-acceso')?.textContent).toContain('abre el domingo');
    });
  });

  describe('Paso III · inscribirse y cancelar', () => {
    it('inscribirse pide confirmación; un taller en dos partes aparece entero en «mis talleres»', async () => {
      let mios: number[] = [];
      const { el, fetch } = await abrir({
        'POST attendees/verify-code': VALIDO,
        'GET attendees/7/events': () => ({ status: 200, json: mios }),
        'POST attendees/7/events': () => { mios = [3, 4]; return { status: 201, json: { enrolled: [3, 4] } }; },
      });
      await verificar(el);
      await pulsar(el, '[data-evento="3"] .inscribirme');
      const dialogo = el.querySelector('dialog.confirmacion')!;
      expect(dialogo.hasAttribute('open')).toBe(true);
      expect(dialogo.textContent).toContain('Dragón (parte 1)');
      expect(fetch.mock.calls.some(([, o]) => o?.method === 'POST' && String(o.body).includes('id_event'))).toBe(false);
      await pulsar(dialogo, '.si');
      const envio = fetch.mock.calls.find(([u, o]) => String(u).endsWith('attendees/7/events') && o?.method === 'POST')!;
      expect(JSON.parse(envio[1]!.body as string)).toEqual({ id_event: 3 });
      expect([...el.querySelectorAll('.mios li b')].map((b) => b.textContent)).toEqual(['Dragón (parte 1)', 'Dragón (parte 2)']);
      expect(el.querySelector('.aviso')?.textContent).toContain('Quedaste inscrito');
    });

    it('choque 409: el mensaje sale en la tarjeta del taller y no cambia nada más', async () => {
      const { el } = await abrir({
        'POST attendees/verify-code': VALIDO,
        'POST attendees/7/events': () => ({ status: 409, json: { error: { code: 'conflict', message: 'Ya tienes «Grulla» a esa hora.' } } }),
      });
      await verificar(el);
      await pulsar(el, '[data-evento="1"] .inscribirme');
      await pulsar(el.querySelector('dialog.confirmacion')!, '.si');
      expect(el.querySelector('[data-evento="1"] .error')?.textContent).toBe('Ya tienes «Grulla» a esa hora.');
      expect(el.querySelector('.mios .ninguno')).toBeTruthy();
    });

    it('cancelar con confirmación libera el taller', async () => {
      let mios = [1];
      const { el, fetch } = await abrir({
        'POST attendees/verify-code': VALIDO,
        'GET attendees/7/events': () => ({ status: 200, json: mios }),
        'DELETE attendees/7/events/1': () => { mios = []; return { status: 204 }; },
      });
      await verificar(el);
      await pulsar(el, '.mios .cancelar');
      expect(el.querySelector('dialog.confirmacion')?.textContent).toContain('Liberas tu cupo');
      await pulsar(el.querySelector('dialog.confirmacion')!, '.si');
      expect(fetch.mock.calls.some(([u, o]) => String(u).endsWith('attendees/7/events/1') && o?.method === 'DELETE')).toBe(true);
      expect(el.querySelector('.mios .ninguno')).toBeTruthy();
      expect(el.querySelector('[data-evento="1"]')?.getAttribute('data-estado')).toBe('libre');
    });
  });
});
