import inscripciones from '@contenido/inscripciones.json';
import { TestBed } from '@angular/core/testing';
import { traducir } from './idioma';
import {
  capitalizar, construirEnvio, DatosInscripcion, datosVacios, enviarInscripcion,
  requiereComprobante, validarPaso,
} from './registro';

/** Un formulario completo y válido, para variar un campo cada vez. */
function completo(cambios: Partial<DatosInscripcion> = {}): DatosInscripcion {
  return {
    ...datosVacios(),
    name: 'maría josé', lastname: 'pérez gómez', age: '34', idname: 'majo',
    country: '57', city: 'bogotá', email: 'majo@unal.edu.co', phone: '3152459839',
    paymethod: 'Nequi', paydate: '2027-03-01', receipt: 'ABC123',
    days: 'T', info: 'Redes Sociales',
    ...cambios,
  };
}

describe('Inscripción — lógica del formulario', () => {
  const t = () => TestBed.runInInjectionContext(() => traducir(inscripciones.formulario));
  const campos = (paso: number, d: DatosInscripcion) => validarPaso(paso, d, t()).map((e) => e.campo);

  it('un formulario completo pasa los cinco pasos', () => {
    for (let paso = 1; paso <= 5; paso++) expect(campos(paso, completo()), `paso ${paso}`).toEqual([]);
  });

  it('paso 1 exige nombre, apellido, edad y nombre en la escarapela (en ese orden)', () => {
    expect(campos(1, datosVacios())).toEqual(['name', 'lastname', 'age', 'idname']);
    expect(validarPaso(1, datosVacios(), t())[0].mensaje).toBe(t().validaciones.name);
  });

  it('paso 2 valida correo (con subdominios) y teléfono solo con números', () => {
    expect(campos(2, completo({ email: 'sin-arroba' }))).toEqual(['email']);
    expect(validarPaso(2, completo({ email: 'sin-arroba' }), t())[0].mensaje).toBe(t().validaciones.emailInvalid);
    expect(campos(2, completo({ email: 'nombre@mail.unal.edu.co' }))).toEqual([]);
    expect(campos(2, completo({ phone: '315-245' }))).toEqual(['phone']);
  });

  it('paso 3: becas, patrocinio, grupo de apoyo e invitados no exigen fecha ni comprobante', () => {
    for (const exento of ['Becado(a)', 'Patrocinio', 'Grupo de apoyo', 'Invitado(a)']) {
      expect(requiereComprobante(exento), exento).toBe(false);
      expect(campos(3, completo({ paymethod: exento, paydate: '', receipt: '' })), exento).toEqual([]);
    }
    expect(campos(3, completo({ paymethod: 'Efectivo', paydate: '', receipt: '' }))).toEqual(['paydate', 'receipt']);
    expect(campos(3, completo({ paymethod: '' }))).toEqual(['paymethod']);
  });

  it('paso 4 exige elegir los días', () => {
    expect(campos(4, completo({ days: '' }))).toEqual(['days']);
  });

  it('paso 5: los campos que dependen de una casilla solo se exigen si está marcada', () => {
    expect(campos(5, completo({ info: 'Otro' }))).toEqual(['otherInfo']);
    expect(campos(5, completo({ group: true }))).toEqual(['origamigroup']);
    expect(campos(5, completo({ cards: true, cardGroup: '3' }))).toEqual(['cardGroup']);
    expect(campos(5, completo({ cards: true, cardGroup: '2' }))).toEqual([]);
  });

  it('chiva: hay que decir si va acompañado y, si sí, entre 2 y 3 acompañantes', () => {
    expect(campos(5, completo({ go_to_chiva: true }))).toEqual(['chiva_accompanied']);
    expect(campos(5, completo({ go_to_chiva: true, chiva_accompanied: false }))).toEqual([]);
    expect(campos(5, completo({ go_to_chiva: true, chiva_accompanied: true }))).toEqual(['chiva_companions']);
    expect(campos(5, completo({ go_to_chiva: true, chiva_accompanied: true, chiva_companions: '1' }))).toEqual(['chiva_companions']);
    expect(campos(5, completo({ go_to_chiva: true, chiva_accompanied: true, chiva_companions: '4' }))).toEqual(['chiva_companions']);
    expect(campos(5, completo({ go_to_chiva: true, chiva_accompanied: true, chiva_companions: '3' }))).toEqual([]);
  });

  it('arma el JSON del servicio como el sitio anterior', () => {
    const envio = construirEnvio(completo({ paydate: '', cards: true, cardGroup: '1' }), new Date('2027-03-05T12:00:00Z'));
    expect(envio).toMatchObject({
      name: 'María José', lastname: 'Pérez Gómez', city: 'Bogotá', idname: 'Majo',
      age: 34, country: 'Colombia (+57)', paydate: '2027-03-05',
      cards: 1, cardgroup: '1', group: 0, go_to_chiva: 0, chiva_companions: 0, days: 'T', medium: 'Redes Sociales',
    });
    expect(capitalizar('  ana   MARÍA ')).toBe('Ana María');
  });

  it('sin apiUrl, el envío se simula (no hay backend todavía)', async () => {
    expect(inscripciones.apiUrl).toBeNull();
    const info = vi.spyOn(console, 'info').mockImplementation(() => undefined);
    await expect(enviarInscripcion(construirEnvio(completo()))).resolves.toEqual({ simulado: true });
    expect(info).toHaveBeenCalled();
    info.mockRestore();
  });
});
