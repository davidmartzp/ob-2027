// ============ TRADUCCIONES POR SECCIÓN ============
// Uso: npm run i18n
//
// 1. Extrae los textos marcados (i18n="@@seccion.clave" en plantillas, $localize`:@@seccion.clave:…` en TS).
// 2. Reordena las traducciones de cada idioma en src/locale/<idioma>/<seccion>.json, conservando
//    lo ya traducido, quitando lo que ya no existe e informando de lo que falta por traducir.
// 3. Actualiza la lista de archivos de cada idioma en angular.json.
//
// La sección es lo que va antes del primer punto del ID (@@comunidad.titulo → comunidad.json).

import { execFileSync } from 'node:child_process';
import { mkdtempSync, mkdirSync, readdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const RAIZ = new URL('..', import.meta.url).pathname;
const LOCALES = join(RAIZ, 'src/locale');
const ANGULAR_JSON = join(RAIZ, 'angular.json');

const leerJson = (ruta) => JSON.parse(readFileSync(ruta, 'utf8'));
const escribirJson = (ruta, datos) => writeFileSync(ruta, JSON.stringify(datos, null, 2) + '\n');
const seccionDe = (id) => id.split('.')[0];

// 1. Extraer
const temporal = mkdtempSync(join(tmpdir(), 'i18n-'));
execFileSync('npx', ['ng', 'extract-i18n', '--format=json', `--output-path=${temporal}`], { cwd: RAIZ, stdio: 'inherit' });
const fuente = leerJson(join(temporal, 'messages.json'));
rmSync(temporal, { recursive: true, force: true });

const porSeccion = {};
for (const [id, texto] of Object.entries(fuente.translations)) {
  (porSeccion[seccionDe(id)] ??= {})[id] = texto;
}
const secciones = Object.keys(porSeccion).sort();

const angular = leerJson(ANGULAR_JSON);
const proyecto = Object.values(angular.projects)[0];
const fuenteLocale = proyecto.i18n.sourceLocale.code ?? proyecto.i18n.sourceLocale;
const reescribirCarpeta = (locale, contenido) => {
  const carpeta = join(LOCALES, locale);
  mkdirSync(carpeta, { recursive: true });
  for (const f of readdirSync(carpeta)) if (f.endsWith('.json')) rmSync(join(carpeta, f));
  for (const [seccion, translations] of Object.entries(contenido)) {
    escribirJson(join(carpeta, `${seccion}.json`), { locale, translations });
  }
};
// Cada idioma de destino
let faltanEnTotal = 0;
for (const [locale, config] of Object.entries(proyecto.i18n.locales)) {
  const carpeta = join(LOCALES, locale);
  mkdirSync(carpeta, { recursive: true });

  // Todo lo traducido hasta ahora, venga del archivo que venga
  const existentes = {};
  for (const f of readdirSync(carpeta).filter((f) => f.endsWith('.json'))) {
    Object.assign(existentes, leerJson(join(carpeta, f)).translations);
  }

  const contenido = {};
  const faltan = [];
  for (const seccion of secciones) {
    contenido[seccion] = {};
    for (const id of Object.keys(porSeccion[seccion])) {
      if (id in existentes) contenido[seccion][id] = existentes[id];
      else faltan.push(id);
    }
  }
  const sobran = Object.keys(existentes).filter((id) => !(id in fuente.translations));

  reescribirCarpeta(locale, contenido);
  const archivos = secciones.map((s) => `src/locale/${locale}/${s}.json`);
  if (typeof config === 'string') proyecto.i18n.locales[locale] = archivos;
  else config.translation = archivos;

  console.log(`\n[${locale}] ${secciones.length} archivos · ${Object.keys(fuente.translations).length - faltan.length} traducidos`);
  if (sobran.length) console.log(`  Eliminados (ya no existen): ${sobran.join(', ')}`);
  if (faltan.length) {
    console.log(`  FALTAN ${faltan.length} por traducir (texto en ${fuenteLocale}):`);
    for (const id of faltan) console.log(`    ${id}: ${JSON.stringify(fuente.translations[id])}`);
  }
  faltanEnTotal += faltan.length;
}

escribirJson(ANGULAR_JSON, angular);
process.exitCode = faltanEnTotal ? 1 : 0;
