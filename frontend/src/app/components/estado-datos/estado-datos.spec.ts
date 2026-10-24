import { Component, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import comun from '@contenido/comun.json';
import { EstadoCarga } from '@core/datos-vivos';
import { EstadoDatos } from './estado-datos';

@Component({
  imports: [EstadoDatos],
  template: `<app-estado-datos [estado]="estado()" [mensaje]="mensaje()" (reintentar)="reintentos = reintentos + 1"/>`,
})
class Anfitrion {
  readonly estado = signal<EstadoCarga>('cargando');
  readonly mensaje = signal('');
  reintentos = 0;
}

describe('EstadoDatos', () => {
  async function montar(estado: EstadoCarga, mensaje = '') {
    const fixture = TestBed.createComponent(Anfitrion);
    fixture.componentInstance.estado.set(estado);
    fixture.componentInstance.mensaje.set(mensaje);
    await fixture.whenStable();
    return { fixture, el: fixture.nativeElement as HTMLElement };
  }

  it('cargando: avisa con role=status', async () => {
    const { el } = await montar('cargando');
    expect(el.querySelector('[role=status]')?.textContent).toContain(comun.estados.cargando.es);
  });

  it('vacío, demostración y cerrado muestran su texto', async () => {
    expect((await montar('vacio')).el.textContent).toContain(comun.estados.vacioTitulo.es);
    expect((await montar('demo')).el.textContent).toContain(comun.estados.demoTitulo.es);
    expect((await montar('cerrado')).el.textContent).toContain(comun.estados.cerradoTexto.es);
  });

  it('cerrado con mensaje del backend lo muestra en lugar del genérico', async () => {
    const { el } = await montar('cerrado', 'La inscripción a talleres abre el sábado.');
    expect(el.textContent).toContain('abre el sábado');
    expect(el.textContent).not.toContain(comun.estados.cerradoTexto.es);
  });

  it('error: «No pudimos conectar» y el botón reintentar avisa', async () => {
    const { el, fixture } = await montar('error');
    expect(el.querySelector('[role=alert]')?.textContent).toContain(comun.estados.errorTitulo.es);
    el.querySelector<HTMLButtonElement>('button')!.click();
    expect(fixture.componentInstance.reintentos).toBe(1);
  });

  it('listo: no pinta nada', async () => {
    const { el } = await montar('listo');
    expect(el.querySelector('app-estado-datos')?.children.length).toBe(0);
  });
});
