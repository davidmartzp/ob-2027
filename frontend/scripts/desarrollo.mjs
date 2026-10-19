// ============ DESARROLLO CON LOS DOS IDIOMAS ============
// Uso: npm start
//
// `ng serve` compila un solo idioma, así que el selector ES/EN no puede funcionar con él.
// Aquí se compilan los dos en modo observación (configuración «local» de angular.json,
// salida en dist/local/) y se sirven como en el hosting; la página se recarga sola
// después de cada recompilación. Para HMR en un solo idioma: npm run start:es / start:en.

import { spawn } from 'node:child_process';
import { servir } from './servidor.mjs';

const RAIZ = new URL('../dist/local/browser/', import.meta.url).pathname;
const PUERTO = Number(process.env.PORT ?? 4200);

// La salida del build pasa a la terminal tal cual; cada «generation complete» = recargar.
const build = spawn('npx', ['ng', 'build', '--watch', '--configuration', 'local'], { stdio: ['inherit', 'pipe', 'inherit'] });
build.on('exit', (codigo) => process.exit(codigo ?? 0));
process.on('SIGINT', () => { build.kill('SIGINT'); process.exit(0); });

let recargar = null;
build.stdout.on('data', (trozo) => {
  process.stdout.write(trozo);
  if (!String(trozo).includes('generation complete')) return;
  if (!recargar) recargar = servir({ raiz: RAIZ, puerto: PUERTO, recarga: true });
  else setTimeout(recargar, 300); // margen para que termine de escribir el prerender
});
