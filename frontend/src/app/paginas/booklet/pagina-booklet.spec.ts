import booklet from '@contenido/booklet.json';
import evento from '@contenido/evento.json';
import { PlatformLocation } from '@angular/common';
import { TestBed } from '@angular/core/testing';
import { fijarApiUrl } from '@core/api';
import { TallerProgramaApi } from '@core/talleres';
import { esperar, montarPagina, simularApi } from '../../../pruebas';

const taller = (id: number, nombre: string, dia: 'Saturday' | 'Sunday', inicio: string, nivel: 'P' | 'I' | 'A', sala = 'Sala 1'): TallerProgramaApi => ({
  id, name: nombre, level: nivel, public: 'A', duration: 60, observations: null, image: null,
  instructor: { id: id + 100, name: `Tallerista ${id}` },
  event: { id: id + 1000, day: dia, start_time: `${inicio}:00`, end_time: '23:00:00', room: { id: 1, name: sala } },
  partners: [],
});

const PROGRAMA = [
  taller(1, 'Rana saltarina', 'Sunday', '10:00', 'P'),
  taller(2, 'Grulla', 'Saturday', '14:00', 'I', 'Sala 2'),
  taller(3, 'Dragón', 'Sunday', '09:00', 'A'),
  taller(4, 'Teselado', 'Saturday', '09:00', 'A'),
];

describe('página booklet', () => {
  beforeEach(() => fijarApiUrl('http://api.test'));
  afterEach(() => {
    vi.unstubAllGlobals();
    fijarApiUrl(evento.apiUrl);
  });

  async function abrir(url: string, programa: unknown = PROGRAMA): Promise<HTMLElement> {
    vi.stubGlobal('fetch', simularApi({ 'GET events/schedule': programa }));
    const el = await montarPagina(url);
    await esperar(); await esperar();
    return el;
  }
  const nombres = (el: HTMLElement) => [...el.querySelectorAll('summary .nombre')].map((n) => n.textContent);

  it('agrupa por día (sábado, domingo) y hora de inicio', async () => {
    const el = await abrir('/booklet');
    expect([...el.querySelectorAll('.nombre-dia')].map((h) => h.textContent)).toEqual(['Sábado', 'Domingo']);
    expect(nombres(el)).toEqual(['Teselado', 'Grulla', 'Dragón', 'Rana saltarina']);
    expect(el.querySelector('.dia[data-dia=Sunday] .hora')?.textContent).toBe('09:00');
  });

  it('?dia=domingo&nivel=A filtra y los chips lo reflejan', async () => {
    const el = await abrir('/booklet?dia=domingo&nivel=A');
    expect(nombres(el)).toEqual(['Dragón']);
    const activos = [...el.querySelectorAll('.filtros .chip[aria-pressed=true]')].map((c) => c.textContent);
    expect(activos).toEqual(['Domingo', 'Avanzado', 'Todos']);
  });

  it('elegir un filtro lo guarda en la URL y «Limpiar filtros» lo quita', async () => {
    const el = await abrir('/booklet');
    [...el.querySelectorAll<HTMLButtonElement>('.filtros .chip')].find((c) => c.textContent === 'Sábado')!.click();
    await esperar();
    expect(TestBed.inject(PlatformLocation).search).toBe('?dia=sabado');
    expect(nombres(el)).toEqual(['Teselado', 'Grulla']);
    const sala = el.querySelector<HTMLSelectElement>('#filtro-sala')!;
    sala.value = 'Sala 2';
    sala.dispatchEvent(new Event('change'));
    await esperar();
    expect(nombres(el)).toEqual(['Grulla']);
    expect(TestBed.inject(PlatformLocation).search).toBe('?dia=sabado&sala=Sala+2');
    el.querySelector<HTMLButtonElement>('.limpiar')!.click();
    await esperar();
    expect(TestBed.inject(PlatformLocation).search).toBe('');
    expect(nombres(el).length).toBe(4);
  });

  it('sin coincidencias muestra el mensaje', async () => {
    const el = await abrir('/booklet?dia=sabado&nivel=P');
    expect(nombres(el)).toEqual([]);
    expect(el.querySelector('.sin-coincidencias')?.textContent).toContain(booklet.sinCoincidencias.titulo.es);
  });

  it('programa vacío: estado vacío con su texto', async () => {
    const el = await abrir('/booklet', []);
    expect(el.querySelector('app-estado-datos')?.textContent).toContain(booklet.vacio.titulo.es);
  });

  it('no muestra participantes ni datos de contacto', async () => {
    const conDatos = PROGRAMA.map((t) => ({ ...t, attendees: [{ name: 'X', email: 'x@correo.co', phone: '3001234567' }] }));
    const el = await abrir('/booklet', conDatos);
    el.querySelectorAll('details').forEach((d) => (d.open = true));
    await esperar();
    const texto = el.querySelector('.programa')!.textContent!;
    expect(texto).not.toContain('@');
    expect(texto).not.toMatch(/\d{7,}/);
  });

  it('sin API: el HTML trae título y el aviso de demostración', async () => {
    fijarApiUrl(null);
    const el = await montarPagina('/booklet');
    expect(el.querySelector('.pagina-hero h2')?.textContent).toContain('Programa de');
    expect(el.querySelector('app-estado-datos')?.textContent).toContain(booklet.vacio.demo.es);
  });
});
