import noEncontrada from '@contenido/no-encontrada.json';
import { Meta } from '@angular/platform-browser';
import { TestBed } from '@angular/core/testing';
import { montarPagina } from '../../../pruebas';

describe('página no encontrada', () => {
  it('una ruta inexistente muestra la 404 sin redirigir, con sus enlaces', async () => {
    const el = await montarPagina('/no-existe');
    expect(el.querySelector('.no-encontrada h1')?.textContent).toContain('se perdió en un');
    expect([...el.querySelectorAll('.no-encontrada nav a')].map((a) => a.getAttribute('href')))
      .toEqual(noEncontrada.enlaces.map((e) => e.href));
    expect(el.querySelector('#acerca')).toBeNull(); // no es la home
    expect(TestBed.inject(Meta).getTag('name="robots"')?.content).toBe('noindex');
  });

  it('/en/no-existe sale en inglés y con enlaces en inglés', async () => {
    const el = await montarPagina('/en/no-existe/de/verdad');
    expect(el.querySelector('.no-encontrada h1')?.textContent).toContain('got lost in a');
    expect([...el.querySelectorAll('.no-encontrada nav a')].map((a) => a.getAttribute('href')))
      .toEqual(['en/', 'en/talleres', 'en/inscripciones', 'en/recursos']);
  });
});
