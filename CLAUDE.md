# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@AGENTS.md

La guía del proyecto (concepto, sistema de diseño, microcopy, honestidad de contenido) está en `AGENTS.md`, importado arriba. Ojo: AGENTS.md describe el sitio original de un solo archivo (`app/`). El desarrollo activo está en **`frontend/`** (Angular); para `frontend/` mandan las reglas de abajo sobre las de AGENTS.md §2, §3, §5 y §6.

## Proyectos

- `backend/` — API en Slim 4 + Eloquent (`illuminate/database`, sin Laravel) + MySQL. Ver sección «Backend».
- `frontend/` — **versión activa del sitio**. Angular 22, prerender estático (SSG). La home es una hoja de secciones que se navega por anclas (`#acerca`, `#itinerario`…); desde el 2026-10-19 hay además una página propia, `/actividades` (buscador y fichas). No crear más páginas salvo que se pida.
- `app/` — sitio original (HTML + CSS + JS inline). Queda como referencia visual 1:1 hasta archivarlo; no desarrollar ahí.

## Comandos (`frontend/`)

```sh
cd frontend
npm start                 # http://localhost:4200 — compila ES y EN en modo observación (dist/local/) y los sirve como el hosting, con recarga automática; el selector ES/EN funciona
npm run start:es          # ng serve solo en español (más rápido, con HMR; /en/ redirige al español)
npm run start:en          # ng serve solo en inglés
npm run build             # dist/frontend/browser/ — HTML prerenderizado, publicable en cualquier hosting estático
npm run preview           # build de producción + el mismo servidor estático (scripts/servidor.mjs)
npm run i18n              # extrae textos y reordena las traducciones por sección (ver «Traducción»)
npm test                  # Vitest + jsdom, una pasada
npx ng test --watch=false --include src/app/app.spec.ts   # un solo archivo
```

Sigue haciendo falta verificar en navegador en 1020px, 760px y con `prefers-reduced-motion: reduce`.

**Despliegue: el servidor de producción solo corre PHP, no Node.** Por eso `angular.json` usa `outputMode: "static"`: Node solo se usa al compilar. Se sube el contenido de `dist/frontend/browser/`: el español en la raíz y el inglés en `en/` (cada uno con su copia de `assets/`). Si va en una subcarpeta, compila con `--base-href /ruta/` (el inglés queda en `/ruta/en/`). No añadir SSR con servidor, `server.ts` ni Express; cualquier backend (por ejemplo, inscripciones) tendría que ser PHP.

## Arquitectura de `frontend/src/app`

- `app.html` — marco común: cabecera, `<router-outlet>`, leyenda y pie. Rutas en `app.routes.ts`; cada página en `paginas/`.
- `paginas/inicio/inicio.ts` — la home: orden de secciones y `<app-doblez>` entre ellas (el ritmo claro/oscuro se decide aquí). Cada página fija su `<title>` y meta descripción en el constructor.
- **Páginas internas**: arriba el hero del evento (`<app-hero/>`: toro alado y título), luego la cabecera propia de la página y `<app-patrocinadores/>` al final (si `SECCIONES_VISIBLES.patrocinadores`); cabecera, leyenda y pie vienen del marco.
- `paginas/actividades/` — página con buscador (filtra en el navegador, sin tildes ni mayúsculas) y fichas `<details name="actividad">` (acordeón nativo, todo el texto sale en el prerender). `actividades#<id>` abre esa ficha. La sección de la home solo destaca `DESTACADAS` y enlaza aquí.
- **Enlaces**: `#seccion` lleva a la home desde cualquier página (resuelven contra `<base href>`); las páginas se enlazan relativas y sin «/» inicial (`actividades`, `actividades#chocogami`, `./` = home). Son `<a href>` normales: recarga completa, cada página es su propio HTML prerenderizado.
- `datos/` — todo el contenido en arrays tipados (`modelos.ts`): invitados, actividades (con `CATEGORIAS` y `DESTACADAS`; fuente original: `originales/actividades_master_es_en.json` del cliente), navegación, patrocinadores, datos del evento. **Nuevo invitado/actividad/entrada de menú = añadir un objeto aquí**, no tocar plantillas (los textos visibles, con `$localize`; ver «Traducción»). El frontend **no consume el backend** (decisión del 2026-10-13: volver a componentes sin base de datos).
- `compartido/` — primitivas del diagrama: `Simbolo` (`<svg appSimbolo="valle">`), `Separador` (`<app-separador/>` valle · `<app-separador montana frase="…"/>`: línea de pliegue y, opcional, la frase a mano debajo —máximo una por sección; color con `--color-anotacion`—. Reemplaza a las etiquetas «Paso 0X», retiradas el 2026-10-13; alternar valle/montaña por la página), `Doblez`, directiva `Reveal` (`appReveal="1|2|3"` = `data-delay`; entra con `.in` y sale al dejar la pantalla, con `.arriba` si salió por arriba), `IconoRed`, `Figura`.
- `secciones/` — un componente por sección; `InvitadoCard` y `ActividadItem` se montan como selector de atributo (`article[appInvitadoCard]`, `li[appActividad]`) para que `:nth-child` y `ul:hover > li` sigan funcionando entre hermanos.
- `efectos/` — `VistaFlotante` + `VistaFlotanteServicio` (cualquier componente la activa con `mostrarFoto`/`mostrarFigura`).
- **Interruptores** en `datos/evento.ts` (ocultan sin borrar): `INSCRIPCIONES_ABIERTAS` (botones/enlaces «Inscríbete» de menú, menú móvil, hero y footer) y `MOSTRAR_MENU` (enlaces del menú superior y hamburguesa móvil; el logo queda) y `SECCIONES_VISIBLES` (comunidad, itinerario, actividades, tallerista, patrocinadores: oculta la sección, su doblez y los enlaces `#id` hacia ella, vía `enlaceVisible()`; `actividades: false` también quita la ruta `/actividades` y los enlaces `actividades#…`; con el itinerario oculto también se ocultan el botón «Ver itinerario» del hero y la flecha «Doblar» de la leyenda). Desde el 2026-10-19 solo `INSCRIPCIONES_ABIERTAS` sigue en `false`; menú y secciones, visibles.
- **Modales**: `<dialog>` nativo abierto con `showModal()` (Escape, foco atrapado y devolución del foco vienen del navegador); ejemplo en `secciones/itinerario/itinerario-modal.ts` (texto en `datos/itinerario.ts`). `html:has(dialog[open])` en `styles.css` bloquea el scroll de fondo.
- Nuevo símbolo de diagrama: añadir el `@case` en `Simbolo`, el tipo en `TipoSimbolo` **y** la entrada en `Leyenda` (hoy: valle, montaña, doblar; la leyenda solo lista lo que se usa).

## Traducción (i18n de Angular, `@angular/localize`)

- Se traduce **al compilar**: un sitio estático por idioma, sin cambio en vivo. El selector (`compartido/selector-idioma.ts`, en la cabecera) enlaza a la otra copia con rutas relativas al `<base href>`. Idiomas en `datos/idiomas.ts`; su `ruta` tiene que coincidir con el `subPath` de `angular.json` (proyecto → `i18n`). Español = idioma fuente, en la raíz.
- **Todo texto visible nuevo se marca con un ID cuyo prefijo es la sección**: en plantillas `i18n="@@seccion.clave"` (atributos: `i18n-alt`, `i18n-aria-label`, `i18n-frase`…); en TS `` $localize`:@@seccion.clave:Texto` ``. Sin ID explícito, Angular inventa uno y el texto no cae en el archivo de su sección. Mismo texto en varios sitios = mismo ID (p. ej. `@@nav.itinerario`). No se traducen nombres propios ni marcas.
- Después: `npm run i18n`. Reordena `src/locale/en/<seccion>.json` (aquí se traduce: conserva los marcadores `{$START_EMPHASISED_TEXT}`, `{$INTERPOLATION}`…), borra lo obsoleto, lista lo que falta y actualiza la lista de archivos en `angular.json`. Sale con error si falta alguna traducción; el build solo avisa y deja el texto en español.
- `<title>`, meta descripción, `canonical` + `hreflang` (cada idioma es su propia URL: así Google los trata como traducciones, no como duplicados) y tarjeta al compartir (Open Graph + Twitter/X): `fijarMeta(titulo, descripcion, pagina)` (`compartido/meta.ts`) en el constructor de cada página (index.html no se traduce). Las URLs son absolutas sobre `SITIO_URL` (`datos/idiomas.ts`): **si cambia el dominio, cambia ahí**.
- Imagen al compartir: `public/assets/og-es.jpg` y `og-en.jpg` (1200×630; entre idiomas solo cambian la anotación a mano y «Congreso/Convention»). Se generan desde `originales/og/og-*.html`: abrir en Chrome y capturar a 1200×630 (`"…/Google Chrome" --headless=new --window-size=1200,630 --force-device-scale-factor=1 --virtual-time-budget=8000 --screenshot=og.png file://…/og-es.html`), luego pasar a JPG. Si cambian fecha o sede, actualizar ahí.

## Trampas no obvias

- **Legibilidad**: texto de lectura en `--tinta-80` (tinta al 80 %, añadida el 2026-10-13) y tamaño `--fs-texto` (18px en móvil → 20px en escritorio; también es el tamaño del `body`); `--tinta-60` solo para detalles secundarios. Ninguna etiqueta por debajo de 11px.
- **Hero = una pantalla**: la imagen principal (toro alado, `assets/toro-alado.jpg`, 2:3, recta y **sin recortar** —el original de alta resolución está en `originales/`, fuera de lo publicado—) tiene un ancho calculado con la altura de pantalla (`hero.css`, `.hero-lado .figura`) para no meterse bajo el menú. Si cambia la imagen o el contenido del hero, vuelve a medir en 1280×720, 1440×900 y 1920×1080.

- **Estilos encapsulados = más especificidad.** Una regla de componente (`.x[_ngcontent-…]`) le gana a las globales `.reveal` / `.reveal.in`. No pongas `transform` ni `opacity` en un elemento que lleva `appReveal`: anula la animación de entrada. Envuelve el elemento o aplica el efecto a un hijo.
- Los estilos de un padre no alcanzan el interior de un hijo. Por eso el texto con `<em>` (`TextoConEnfasis`) se pinta en línea en cada plantilla y no con un componente.
- Dentro de un componente cuyo host es `<svg>`, los hijos se escriben `<svg:path>`, `<svg:circle>`…
- Todo lo que toca `window`, `IntersectionObserver` o `requestAnimationFrame` va en `afterNextRender`: el build y `ng serve` renderizan en Node. **Su limpieza también**: registra `destroyRef.onDestroy(...)` *dentro* del callback de `afterNextRender` (inyecta `DestroyRef` en el constructor). Si lo registras en el constructor, se ejecuta en el servidor y falla (`cancelAnimationFrame is not defined`).
- Sin zone.js: los cambios de signals se pintan de forma asíncrona. En pruebas, espera a `fixture.whenStable()` antes de leer el DOM.
- Assets en `frontend/public/assets/`, referenciados como `assets/...`.

## Backend (`backend/`)

No hay PHP ni Composer en la máquina: **todo corre en Docker** (PHP 8.3 + Apache con `.htaccess`, como el hosting; MySQL 8.4).

```sh
cd backend
cp .env.example .env                                   # solo la primera vez
docker compose up -d --wait                            # API → http://localhost:8080 · MySQL → localhost:3307
docker compose exec api php bin/consola migrar         # también: revertir (último lote), sembrar (idempotente)
docker compose exec api vendor/bin/phpunit             # pruebas (SQLite en memoria, no tocan MySQL)
docker compose exec api vendor/bin/phpunit --filter ActividadesTest   # una sola clase/prueba
docker compose exec api composer require paquete/x     # dependencias
```

Arquitectura orientada a servicios, ligera:
- `config/arranque.php` arma todo: `.env` → contenedor PHP-DI (`dependencias.php`) → Eloquent → Slim (`middleware.php`, `rutas.php`). Lo comparten `public/index.php`, `bin/consola` y `tests/CasoFuncional.php`.
- **Un módulo por servicio** en `src/Modulos/<Nombre>/`: `Rutas<Nombre>` (implementa `Modulo`), `Controlador<Nombre>` (solo HTTP), `Servicio<Nombre>` (lógica, devuelve arrays con la forma de la API) y modelos Eloquent. **Módulo nuevo = carpeta + alta en `config/rutas.php`**. Controladores y servicios se inyectan solos (autowiring).
- Los servicios lanzan errores de dominio (`App\Compartido\Errores\*`) y el controlador los traduce a `HttpNotFoundException`, etc. Todas las respuestas de error salen en JSON.
- Migraciones: `database/migraciones/AAAA_MM_DD_HHMMSS_*.php` devuelven `new class implements Migracion { subir/bajar(Builder $esquema) }`. Semillas: `database/semillas/NN_*.php` devuelven un callable idempotente (`updateOrCreate`). Usa en las migraciones solo lo que soporten MySQL **y** SQLite, porque las pruebas migran sobre SQLite.
- Pruebas funcionales: extienden `CasoFuncional`, que arranca la app real migrada y sembrada, y usan `$this->pedir('GET', '/ruta')`.

Trampas:
- Los closures de `$rutas->group(...)` **no pueden ser `static`**: Slim los enlaza al contenedor (*Cannot bind an instance to a static closure*).
- Fuera de Docker no hay `vendor/` ni PHP: corre los comandos con `docker compose exec api …` (o `docker compose run --rm --no-deps api …` si el stack está apagado).
- Despliegue en el hosting PHP: subir el proyecto **con `vendor/`** (`composer install --no-dev` antes de subir) y un `.env` real. Si se puede, la raíz web va a `public/`; si no, el `.htaccess` de la raíz reescribe todo hacia `public/` y `.env`, `src/` y `vendor/` responden 404. Si la API vive en una subcarpeta, define `APP_BASE_PATH` (p. ej. `/api`). Pon `APP_DEBUG=false` en producción: con `true`, los errores muestran trazas y rutas internas.
- Al tocar MySQL a mano: `mysql --default-character-set=utf8mb4 …`. Sin esa opción, las tildes se guardan mal codificadas («FrancÃ©s»).
- Las semillas buscan por clave natural (`slug`, `nombre`, `edicion`). Si renombras un registro en la base y vuelves a sembrar, se crea uno nuevo.
- Hay que confirmar la versión de PHP del hosting: las dependencias están resueltas para PHP 8.3 (`config.platform.php` en `composer.json`).
