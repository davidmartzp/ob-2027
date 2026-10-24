import evento from '@contenido/evento.json';
import { api, apiUrl, ErrorApi, ErrorRed, fijarApiUrl, SinApi } from './api';

/** Una respuesta de fetch con este estado y este JSON. */
function respuesta(status: number, json?: unknown): Response {
  return new Response(json === undefined ? null : JSON.stringify(json), { status, headers: { 'Content-Type': 'application/json' } });
}

describe('core/api — cliente del backend', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
    fijarApiUrl(evento.apiUrl);
  });

  it('pide a {apiUrl}/ruta y devuelve el JSON de un 2xx', async () => {
    fijarApiUrl('http://localhost:8082/');
    const fetch = vi.fn().mockResolvedValue(respuesta(201, { id: 7, code: 141 }));
    vi.stubGlobal('fetch', fetch);

    await expect(api('POST', 'attendees', { name: 'Ana' })).resolves.toEqual({ id: 7, code: 141 });
    const [url, opciones] = fetch.mock.calls[0];
    expect(url).toBe('http://localhost:8082/attendees');
    expect(opciones).toMatchObject({ method: 'POST', body: '{"name":"Ana"}' });
    expect(opciones.headers['Content-Type']).toBe('application/json');
  });

  it('un 204 devuelve undefined y un FormData va sin Content-Type (multipart)', async () => {
    fijarApiUrl('http://api.test');
    const fetch = vi.fn().mockResolvedValue(respuesta(204));
    vi.stubGlobal('fetch', fetch);

    await expect(api('DELETE', 'workshops/3')).resolves.toBeUndefined();
    await api('POST', 'workshops/3/image', new FormData());
    expect(fetch.mock.calls[1][1].headers['Content-Type']).toBeUndefined();
  });

  it('un 422 lanza ErrorApi con el mensaje de cada campo', async () => {
    fijarApiUrl('http://api.test');
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta(422, {
      error: { code: 'validation_failed', message: 'Revisa los datos.', fields: { email: 'El correo no es válido.' } },
    })));

    const error = await api('POST', 'attendees', {}).catch((e: unknown) => e);
    expect(error).toBeInstanceOf(ErrorApi);
    expect(error).toMatchObject({ status: 422, code: 'validation_failed', message: 'Revisa los datos.', fields: { email: 'El correo no es válido.' }, logout: false });
  });

  it('el 403 de inscripción cerrada trae logout', async () => {
    fijarApiUrl('http://api.test');
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta(403, {
      error: { code: 'enrolment_closed', message: 'La inscripción a talleres abre el sábado.' }, logout: true,
    })));
    await expect(api('GET', 'events/active')).rejects.toMatchObject({ code: 'enrolment_closed', logout: true });
  });

  it('un fallo de red lanza ErrorRed', async () => {
    fijarApiUrl('http://api.test');
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')));
    await expect(api('GET', 'workshops')).rejects.toBeInstanceOf(ErrorRed);
  });

  it('con apiUrl null no pide nada y lanza SinApi', async () => {
    fijarApiUrl(null);
    const fetch = vi.fn();
    vi.stubGlobal('fetch', fetch);
    expect(apiUrl()).toBeNull();
    await expect(api('GET', 'workshops')).rejects.toBeInstanceOf(SinApi);
    expect(fetch).not.toHaveBeenCalled();
  });
});
