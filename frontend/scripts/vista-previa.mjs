// ============ VISTA PREVIA DEL BUILD DE PRODUCCIÓN ============
// Uso: npm run preview  (compila con `ng build` y sirve dist/frontend/browser en http://localhost:4200)
//
// Sirve el build igual que el hosting (Apache): cada idioma y cada página es una carpeta
// con su index.html, /actividades redirige a /actividades/ y una ruta inexistente responde
// 404 con la página «no encontrada» de su idioma (como el ErrorDocument de public/.htaccess).

import { createReadStream, statSync } from 'node:fs';
import { createServer } from 'node:http';
import { extname, join, normalize } from 'node:path';

const RAIZ = new URL('../dist/frontend/browser/', import.meta.url).pathname;
const PUERTO = Number(process.env.PORT ?? 4200);
const TIPOS = {
  '.html': 'text/html; charset=utf-8', '.js': 'text/javascript', '.css': 'text/css',
  '.json': 'application/json', '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg',
  '.svg': 'image/svg+xml', '.ico': 'image/x-icon', '.webp': 'image/webp',
};

const esArchivo = (ruta) => { try { return statSync(ruta).isFile(); } catch { return false; } };
const esCarpeta = (ruta) => { try { return statSync(ruta).isDirectory(); } catch { return false; } };

createServer((pedido, respuesta) => {
  const camino = decodeURIComponent(new URL(pedido.url, 'http://x').pathname);
  const ruta = normalize(join(RAIZ, camino));
  if (!ruta.startsWith(RAIZ)) return respuesta.writeHead(403).end();

  // Como Apache (DirectorySlash): /actividades → /actividades/
  if (!camino.endsWith('/') && esCarpeta(ruta)) {
    return respuesta.writeHead(301, { Location: camino + '/' }).end();
  }
  const archivo = esCarpeta(ruta) ? join(ruta, 'index.html') : ruta;
  if (!esArchivo(archivo)) {
    const pagina404 = join(RAIZ, /^\/en(\/|$)/.test(camino) ? 'en/404/index.html' : '404/index.html');
    if (!esArchivo(pagina404)) return respuesta.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' }).end('404 — no existe en el build');
    respuesta.writeHead(404, { 'Content-Type': TIPOS['.html'] });
    return createReadStream(pagina404).pipe(respuesta);
  }
  respuesta.writeHead(200, { 'Content-Type': TIPOS[extname(archivo)] ?? 'application/octet-stream' });
  createReadStream(archivo).pipe(respuesta);
}).listen(PUERTO, () => {
  console.log(`\n  Español: http://localhost:${PUERTO}/   ·   Inglés: http://localhost:${PUERTO}/en/\n`);
});
