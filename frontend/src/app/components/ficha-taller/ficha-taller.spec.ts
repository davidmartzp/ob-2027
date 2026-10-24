import { Component, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { Taller } from '@core/modelos';
import { desdePrograma } from '@core/talleres';
import { FichaTaller, VarianteFicha } from './ficha-taller';

const SOCIO = { id: 3, name: 'Papeles Bogotá', logo: null, whatsapp: '573001112233', message: 'Hola', whatsapp_url: 'https://wa.me/573001112233?text=Hola' };

@Component({
  imports: [FichaTaller],
  template: `<app-ficha-taller [taller]="taller()" [variante]="variante()"/>`,
})
class Anfitrion {
  readonly taller = signal<Taller>(desdePrograma({
    id: 1, name: 'Grulla 2', level: 'I', public: 'A', duration: 60, observations: 'Trae tijeras', image: null,
    instructor: { id: 1, name: 'Talía Prueba' },
    event: { id: 9, day: 'Sunday', start_time: '10:00:00', end_time: '11:00:00', room: { id: 1, name: 'Sala 1' } },
    partners: [SOCIO],
  }));
  readonly variante = signal<VarianteFicha>('programa');
}

describe('FichaTaller', () => {
  async function montar(cambios: Partial<Taller> = {}, variante: VarianteFicha = 'programa'): Promise<HTMLElement> {
    const fixture = TestBed.createComponent(Anfitrion);
    fixture.componentInstance.taller.update((t) => ({ ...t, ...cambios }));
    fixture.componentInstance.variante.set(variante);
    await fixture.whenStable();
    return fixture.nativeElement as HTMLElement;
  }

  it('sin foto muestra el papel plegado; con foto, la imagen con su alt', async () => {
    let el = await montar();
    expect(el.querySelector('app-papel-plegado')).toBeTruthy();
    expect(el.querySelector('.foto img')).toBeNull();
    el = await montar({ foto: 'http://api.test/images/taller_1.jpg' });
    expect(el.querySelector('app-papel-plegado')).toBeNull();
    expect(el.querySelector('.foto img')?.getAttribute('alt')).toContain('Grulla 2');
  });

  it('programa: día, hora, salón, chips y observaciones', async () => {
    const el = await montar();
    expect(el.querySelector('.cuando')?.textContent).toContain('Domingo · 10:00–11:00 · Sala 1');
    expect([...el.querySelectorAll('.chips li')].map((li) => li.textContent?.trim())).toEqual(['Intermedio', 'Adultos', '60 min']);
    expect(el.querySelector('.observaciones')?.textContent).toContain('Trae tijeras');
  });

  it('el WhatsApp del socio abre en otra pestaña con rel=noopener', async () => {
    const a = (await montar()).querySelector<HTMLAnchorElement>('app-socios-taller a')!;
    expect(a.getAttribute('href')).toBe(SOCIO.whatsapp_url);
    expect(a.getAttribute('target')).toBe('_blank');
    expect(a.getAttribute('rel')).toBe('noopener');
  });

  it('inscripción: muestra cupos o «Sin cupos»', async () => {
    expect((await montar({ cupos: 4 }, 'inscripcion')).querySelector('.cupos')?.textContent).toContain('4 cupos');
    expect((await montar({ cupos: 0 }, 'inscripcion')).querySelector('.cupos')?.textContent).toContain('Sin cupos');
  });

  it('galería: sin horario ni observaciones', async () => {
    const el = await montar({}, 'galeria');
    expect(el.querySelector('.cuando')).toBeNull();
    expect(el.querySelector('.observaciones')).toBeNull();
  });
});
