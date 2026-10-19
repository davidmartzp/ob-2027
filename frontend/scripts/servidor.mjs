// ============ SERVIDOR ESTÁTICO LOCAL ============
// Sirve un build igual que el hosting (Apache): cada idioma y cada página es una
// carpeta con su index.html, así que el selector ES/EN y las rutas funcionan como en
// producción. Lo usan scripts/desarrollo.mjs (npm start) y scripts/vista-previa.mjs.

import { createReadStream, readFileSync, statSync } from 'node:fs';
import { createServer } from 'node:http';
import { extname, join, normalize } from 'node:path';

const TIPOS = {
  '.html': 'text/html; charset=utf-8', '.js': 'text/javascript', '.mjs': 'text/javascript', '.css': 'text/css',
  '.json': 'application/json', '.map': 'application/json', '.png': 'image/png', '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg', '.svg': 'image/svg+xml', '.ico': 'image/x-icon', '.webp': 'image/webp',
};
const SCRIPT_RECARGA = `<script>new EventSource('/__recarga').onmessage = () => location.reload();</script>`;

const esArchivo = (ruta) => { try { return statSync(ruta).isFile(); } catch { return false; } };
const esCarpeta = (ruta) => { try { return statSync(ruta).isDirectory(); } catch { return false; } };

/**
 * @param {{ raiz: string, puerto: number, recarga?: boolean }} opciones
 *   recarga: inyecta en el HTML un script que recarga la página cuando se llama a la función devuelta.
 * @returns {() => void} avisa a las páginas abiertas para que se recarguen.
 */
export function servir({ raiz, puerto, recarga = false }) {
  const clientes = new Set();

  createServer((pedido, respuesta) => {
    const camino = decodeURIComponent(new URL(pedido.url, 'http://x').pathname);

    if (recarga && camino === '/__recarga') {
      respuesta.writeHead(200, { 'Content-Type': 'text/event-stream', 'Cache-Control': 'no-cache', Connection: 'keep-alive' });
      clientes.add(respuesta);
      pedido.on('close', () => clientes.delete(respuesta));
      return;
    }

    const ruta = normalize(join(raiz, camino));
    if (!ruta.startsWith(raiz)) return respuesta.writeHead(403).end();

    // Como Apache (DirectorySlash): /actividades → /actividades/
    if (!camino.endsWith('/') && esCarpeta(ruta)) {
      return respuesta.writeHead(301, { Location: camino + '/' }).end();
    }
    const archivo = esCarpeta(ruta) ? join(ruta, 'index.html') : ruta;
    if (!esArchivo(archivo)) {
      return respuesta.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' }).end('404 — no existe en el build');
    }

    const tipo = TIPOS[extname(archivo)] ?? 'application/octet-stream';
    respuesta.writeHead(200, { 'Content-Type': tipo, 'Cache-Control': 'no-store' });
    if (recarga && extname(archivo) === '.html') {
      return respuesta.end(readFileSync(archivo, 'utf8').replace('</body>', `${SCRIPT_RECARGA}</body>`));
    }
    createReadStream(archivo).pipe(respuesta);
  }).listen(puerto, () => {
    console.log(`\n  Español: http://localhost:${puerto}/   ·   Inglés: http://localhost:${puerto}/en/\n`);
  });

  return () => clientes.forEach((c) => c.write('data: recarga\n\n'));
}
