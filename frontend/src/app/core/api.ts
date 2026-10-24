import evento from '@contenido/evento.json';

/* ============ CONEXIÓN CON EL BACKEND ============
   Un solo cliente para todas las páginas con datos vivos. La URL del backend está en
   contenido/evento.json → apiUrl; con null no hay servidor y las páginas muestran el
   aviso de modo demostración (EstadoDatos) en lugar de fallar.
   Errores:
   · ErrorApi — el backend respondió {"error": {"code", "message", "fields"}} (más `logout`
     en el 403 de inscripción a talleres cerrada);
   · ErrorRed — no hubo respuesta (sin conexión, CORS, servidor caído);
   · SinApi — apiUrl es null.
   Solo en el navegador: llamar desde afterNextRender o desde un evento, nunca en el render
   del servidor (el sitio se prerenderiza sin backend).
   Uso: const talleres = await api<Taller[]>('GET', 'workshops'); */

export type Metodo = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

export class ErrorApi extends Error {
  constructor(
    readonly status: number,
    readonly code: string,
    message: string,
    /** Mensaje de cada campo rechazado: { email: '…' }. */
    readonly fields: Record<string, string> = {},
    /** true = el backend pide cerrar la sesión local (inscripción a talleres cerrada). */
    readonly logout = false,
  ) {
    super(message);
    this.name = 'ErrorApi';
  }
}

export class ErrorRed extends Error {
  constructor() {
    super('No pudimos conectar con el servidor.');
    this.name = 'ErrorRed';
  }
}

export class SinApi extends Error {
  constructor() {
    super('El sitio no tiene backend configurado (evento.json → apiUrl).');
    this.name = 'SinApi';
  }
}

let base: string | null = evento.apiUrl as string | null;

/** La URL del backend, sin «/» final; null = modo demostración. */
export function apiUrl(): string | null {
  return base ? base.replace(/\/$/, '') : null;
}

/** Solo para pruebas: cambia la URL del backend (null = sin backend). */
export function fijarApiUrl(url: string | null): void {
  base = url;
}

/** Pide `ruta` (relativa, sin «/» inicial) al backend. Un cuerpo FormData va como multipart (fotos);
 *  cualquier otro, como JSON. Devuelve el JSON de la respuesta (undefined en un 204). */
export async function api<T>(metodo: Metodo, ruta: string, cuerpo?: unknown): Promise<T> {
  const url = apiUrl();
  if (!url) throw new SinApi();

  const cabeceras: Record<string, string> = { Accept: 'application/json' };
  let body: BodyInit | undefined;
  if (cuerpo instanceof FormData) body = cuerpo;
  else if (cuerpo !== undefined) {
    cabeceras['Content-Type'] = 'application/json';
    body = JSON.stringify(cuerpo);
  }

  let respuesta: Response;
  try {
    respuesta = await fetch(`${url}/${ruta.replace(/^\//, '')}`, { method: metodo, headers: cabeceras, body });
  } catch {
    throw new ErrorRed();
  }

  const texto = await respuesta.text();
  let json: unknown;
  try {
    json = texto ? JSON.parse(texto) : undefined;
  } catch {
    json = undefined;
  }

  if (!respuesta.ok) {
    const cuerpoError = json as { error?: { code?: string; message?: string; fields?: Record<string, string> }; logout?: boolean } | undefined;
    const e = cuerpoError?.error;
    throw new ErrorApi(
      respuesta.status,
      e?.code ?? 'error',
      e?.message ?? `El servidor respondió ${respuesta.status}.`,
      e?.fields ?? {},
      cuerpoError?.logout === true,
    );
  }
  return json as T;
}
