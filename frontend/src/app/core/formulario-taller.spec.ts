import { construirTaller, DatosTaller, tallerVacio, validarImagen, validarTaller } from './formulario-taller';

const M = { nombre: 'nombre', nombreLargo: 'largo', nivel: 'nivel', publico: 'publico', duracion: 'duracion', observaciones: 'obs' };
const valido = (c: Partial<DatosTaller> = {}): DatosTaller => ({ ...tallerVacio(), name: 'Grulla', level: 'P', public: 'A', ...c });

describe('formulario de taller', () => {
  it('un taller completo es válido y se envía con la forma del backend', () => {
    expect(validarTaller(valido(), M)).toEqual({});
    expect(construirTaller(valido({ name: '  Grulla ', duration: '90' }))).toEqual({ name: 'Grulla', level: 'P', public: 'A', duration: 90, observations: '' });
  });

  it('exige nombre, nivel y público', () => {
    expect(validarTaller(tallerVacio(), M)).toEqual({ name: 'nombre', level: 'nivel', public: 'publico' });
    expect(validarTaller(valido({ name: 'x'.repeat(201) }), M).name).toBe('largo');
  });

  it('la duración va de 15 a 480 minutos', () => {
    expect(validarTaller(valido({ duration: '5' }), M).duration).toBe('duracion');
    expect(validarTaller(valido({ duration: '481' }), M).duration).toBe('duracion');
    expect(validarTaller(valido({ duration: '1.5' }), M).duration).toBe('duracion');
    expect(validarTaller(valido({ duration: '15' }), M)).toEqual({});
    expect(validarTaller(valido({ duration: '480' }), M)).toEqual({});
  });

  it('observaciones hasta 500 caracteres', () => {
    expect(validarTaller(valido({ observations: 'x'.repeat(501) }), M).observations).toBe('obs');
  });

  it('la foto: JPEG, PNG o WebP de hasta 5 MB', () => {
    expect(validarImagen({ type: 'image/jpeg', size: 1000 })).toBeNull();
    expect(validarImagen({ type: 'image/gif', size: 1000 })).toBe('tipo');
    expect(validarImagen({ type: 'image/png', size: 5 * 1024 * 1024 + 1 })).toBe('peso');
  });
});
