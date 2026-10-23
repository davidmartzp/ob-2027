# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@AGENTS.md

La guía del proyecto (concepto, sistema de diseño, microcopy, honestidad de contenido) está en `AGENTS.md`, importado arriba. Ojo: AGENTS.md describe el sitio original de un solo archivo (`app/`). El desarrollo activo está en **`frontend/`** (Angular); para `frontend/` mandan las reglas de abajo sobre las de AGENTS.md §2, §3, §5 y §6.

## Proyectos

- `backend/` — API en PHP 8.5 + Slim 4 + Eloquent (`illuminate/database`, sin Laravel) + MariaDB, correo con Symfony Mailer, arquitectura hexagonal y código en inglés. Reemplaza a `app-ob/` de `../angular-ob`. Ver sección «Backend».
- `cms/` — (pendiente) frontend del CMS extraído de `../angular-ob`.
- `frontend/` — **versión activa del sitio**. Angular 22, prerender estático (SSG). La home es una hoja de secciones que se navega por anclas (`#acerca`, `#itinerario`…); desde el 2026-10-19 hay además una página propia, `/actividades` (buscador y fichas). No crear más páginas salvo que se pida.
- `app/` — sitio original (HTML + CSS + JS inline). Queda como referencia visual 1:1 hasta archivarlo; no desarrollar ahí.

## Comandos (`frontend/`)

```sh
cd frontend
npm start                 # ng serve → http://localhost:4200 (español) y /en/ (inglés)
npm run build             # dist/frontend/browser/ — HTML prerenderizado, publicable en cualquier hosting estático
npm run preview           # build de producción servido como en el hosting (scripts/vista-previa.mjs)
npm test                  # Vitest + jsdom, una pasada
npx ng test --watch=false --include src/app/app.spec.ts   # un solo archivo
```

Sigue haciendo falta verificar en navegador en 1020px, 760px y con `prefers-reduced-motion: reduce`.

**Despliegue: el servidor de producción solo corre PHP, no Node.** Por eso `angular.json` usa `outputMode: "static"`: Node solo se usa al compilar. Se sube el contenido de `dist/frontend/browser/`: el español en la raíz y el inglés en `en/` (un solo `assets/` compartido). Si va en una subcarpeta, compila con `--base-href /ruta/` (el inglés queda en `/ruta/en/`). No añadir SSR con servidor, `server.ts` ni Express; cualquier backend (por ejemplo, inscripciones) tendría que ser PHP.

## Contenido: `frontend/src/contenido/*.json` (lo único que hay que tocar para cambiar textos y datos)

- **Un JSON por tema**: `evento` (fechas, sede, contacto, redes, `sitioUrl` e **interruptores**), `menu` (destinos y listas), `actividades`, `invitados`, `itinerario`, `patrocinadores`, `inscripciones` (precios, métodos de pago, formulario y `apiUrl`), `preguntas` (preguntas frecuentes), `meta` (título/descripción de cada página e imagen al compartir), `comun` y uno por sección (`hero`, `acerca`, `comunidad`, `tallerista`, `leyenda`, `marquee`, `pie`).
- **Regla única de traducción**: cualquier objeto `{ "es": …, "en": … }` es un valor traducido, esté donde esté (un título, una lista de párrafos, el texto de un enlace). Lo que no cambia entre idiomas (fotos, `href`, ids, categorías) se escribe una sola vez. Ej.: cada actividad es un objeto con `id`, `categoria`, `foto?`, `titulo`, `resumen`, `parrafos`, `nota?`, `enlaces?`. Una prueba falla si a algún texto le falta un idioma.
- **Nuevo invitado / actividad / patrocinador / entrada de menú = un objeto en su JSON**, sin tocar código. Menú: el destino en `menu.json → destinos` y su id en las listas que toque (`principal`, `congreso`, `suelto`, `movil`, `pie`).
- **Interruptores** (`evento.json`, ocultan sin borrar): `inscripcionesAbiertas` (botones «Inscríbete»), `mostrarMenu` (enlaces del menú y hamburguesa; el logo queda) y `secciones` (comunidad, itinerario, actividades, tallerista, patrocinadores: oculta la sección, su doblez y los enlaces hacia ella; `actividades: false` también quita la ruta `/actividades`). Hoy solo `inscripcionesAbiertas` está en `false`.
- **Marcadores**: `{edicion}`, `{edicionAnterior}` y `{anios}` se rellenan solos en todos los textos a partir de `edicion` / `fundacion` de `evento.json`: el año no se escribe a mano.
- **Texto con `<em>`, `<b>`, `<mark>`, `<br>`**: va como HTML en el JSON y se pinta con `[innerHTML]` sobre un elemento `class="rico"`. Ese HTML no lleva el atributo de encapsulado, así que sus estilos están en `styles.css` (`.rico em`…) y cada sección los ajusta con variables CSS, que sí atraviesan: `--color-enfasis`, `--peso-enfasis`, `--transformar-enfasis`.

## Arquitectura de `frontend/src/app` (solo código)

- `components/<nombre>/` — **un componente por carpeta** (`.ts`, `.html`, `.css`). Imports con alias: `@components/…`, `@paginas/…`, `@core/…`, `@contenido/…` (`tsconfig.json → paths`), nunca rutas relativas largas.
- `paginas/` — las páginas con ruta: `inicio/` (orden de secciones en `HOJA`; los dobleces y el ritmo claro/oscuro salen solos del `fondo` de cada bloque) y `actividades/` (buscador sin tildes ni mayúsculas y fichas `<details name="actividad">`; `actividades#<id>` abre esa ficha). Página nueva = componente aquí + ruta en `PAGINAS` (`app.routes.ts`, se repite sola bajo `en/`) + envolverla en `<app-pagina-interna fondo="…">` (hero, doblez, contenido y patrocinadores) + su título en `meta.json`.
- **Inscripciones** (`paginas/inscripciones/`, ruta `/inscripciones` y `/en/inscripciones`): réplica de la página de la rama `master` de `congreso-origami` con el diseño nuevo —cómo inscribirse → Paso I (precios por etapas y métodos de pago, con aviso antes de ir a la tienda) → Paso II (formulario en wizard de 5 pasos) → preguntas frecuentes—. La ruta existe siempre; los botones «Inscríbete» solo aparecen (y enlazan aquí) con `inscripcionesAbiertas: true`. La lógica del formulario está en `core/registro.ts` (validación por paso con los mensajes originales, campos dependientes, JSON del servicio `POST {apiUrl}/assistants` en `EnvioInscripcion`) y probada en `core/registro.spec.ts`. **Sin backend**: con `"apiUrl": null` el envío se simula y la pantalla de éxito lo avisa; al tener el servicio nuevo, basta con poner su URL. Precios con `porConfirmar: true` = se muestran todas las etapas con aviso ✳ (si no, se ocultan al pasar su `fechaLimite`).
- `core/` — lógica sin vista: `idioma.ts` (`IdiomaActual`, `traducir()`, `rellenar()`, `sinEtiquetas()`), `idiomas.ts`, `navegacion.ts` (`seccionVisible()`, `enlaceVisible()`, listas del menú), `meta.ts` (`fijarMeta(pagina)`: título, descripción, `canonical` + `hreflang` y Open Graph), directivas `enlace.ts` y `reveal.ts`, `modelos.ts` (forma de las listas del contenido).
- `app.html` — marco común: cabecera, `<router-outlet>`, leyenda y pie.
- **Componentes clave**: `Menu` (`<app-menu lista="congreso"/>`: pinta cualquier lista de `menu.json`; su aspecto depende de la lista —barra, panel, móvil, pie—; `(elegido)` para cerrar desplegables), `Separador` (línea de pliegue valle/montaña y, opcional, la frase a mano —máximo una por sección; color con `--color-anotacion`—; reemplaza a las etiquetas «Paso 0X», retiradas el 2026-10-13), `Doblez`, `Simbolo`, `PapelPlegado` (icono de las actividades), `RedesSociales`, `SelectorIdioma`, `ResumenActividad` (fila compartida por la home y la ficha), `VistaFlotante` (`mostrarFoto`/`mostrarLogo`). `InvitadoCard`, `ActividadItem` y `FichaActividad` se montan como selector de atributo (`article[appInvitadoCard]`, `li[appActividad]`, `li[appFicha]`) para que `:nth-child` y `ul:hover > li` sigan funcionando entre hermanos.
- **Enlaces**: escribe siempre el destino en español y sin «/» inicial (`#acerca`, `actividades`, `actividades#chocogami`, `./` = home) y ponlo con `[appEnlace]="…"`: añade el prefijo del idioma (`en/#acerca`) y, si es externo, `target="_blank" rel="noopener"`. Un `href` escrito a mano en una página inglesa te devolvería al español.
- Directiva `Reveal` (`appReveal="1|2|3"` = `data-delay`; entra con `.in` y sale al dejar la pantalla, con `.arriba` si salió por arriba).
- **Modales**: `<dialog>` nativo abierto con `showModal()` (Escape, foco atrapado y devolución del foco vienen del navegador); ejemplo en `components/itinerario-modal/`. `html:has(dialog[open])` en `styles.css` bloquea el scroll de fondo.
- Nuevo símbolo de diagrama: añadir el `@case` en `Simbolo`, el tipo en `TipoSimbolo` **y** la entrada en `Leyenda` (hoy: valle, montaña, doblar; la leyenda solo lista lo que se usa).

## Idiomas y SEO

- **Un solo build**; el idioma lo decide la URL: sin prefijo = español, `en/…` = inglés (`IdiomaActual`). Las rutas se repiten bajo cada prefijo y **todas se prerenderizan**, así que `/en/` sale en inglés en el HTML. El selector enlaza a la misma página en el otro idioma. Idiomas en `core/idiomas.ts`.
- **Analítica** (`src/index.html`): Google Analytics 4 `G-YWKKW0SLJJ` (el mismo ID del sitio anterior) y `fb:app_id`. Como cada página es un HTML propio con recarga completa, la visita se registra sola (el sitio anterior tenía `send_page_view: false` sin registrar nada a mano). Solo se carga en `origamibogota.com` (y subdominios): en `localhost` y en la vista previa no, para no ensuciar las estadísticas. Si cambia el dominio, cambia esa comprobación.
- `fijarMeta(pagina)` en el constructor de cada página pone `<title>`, descripción, `canonical` + `hreflang` (cada idioma es su propia URL: así Google los trata como traducciones, no como duplicados) y la tarjeta al compartir (Open Graph + Twitter/X), con URLs absolutas sobre `sitioUrl` de `evento.json`: **si cambia el dominio, cambia ahí**.
- Imagen al compartir: `public/assets/og-es.jpg` y `og-en.jpg` (1200×630; entre idiomas solo cambian la anotación a mano y «Congreso/Convention»). Se generan desde `originales/og/og-*.html`: abrir en Chrome y capturar a 1200×630 (`"…/Google Chrome" --headless=new --window-size=1200,630 --force-device-scale-factor=1 --virtual-time-budget=8000 --screenshot=og.png file://…/og-es.html`), luego pasar a JPG. Si cambian fecha o sede, actualizar ahí.

## Trampas no obvias

- **Legibilidad**: texto de lectura en `--tinta-80` (tinta al 80 %, añadida el 2026-10-13) y tamaño `--fs-texto` (18px en móvil → 20px en escritorio; también es el tamaño del `body`); `--tinta-60` solo para detalles secundarios. Ninguna etiqueta por debajo de 11px.
- **Hero = una pantalla**: la imagen principal (toro alado, `assets/toro-alado.jpg`, 2:3, recta y **sin recortar** —el original de alta resolución está en `originales/`, fuera de lo publicado—) tiene un ancho calculado con la altura de pantalla (`hero.css`, `.hero-lado .figura`) para no meterse bajo el menú. Si cambia la imagen o el contenido del hero, vuelve a medir en 1280×720, 1440×900 y 1920×1080.

- **Estilos encapsulados = más especificidad.** Una regla de componente (`.x[_ngcontent-…]`) le gana a las globales `.reveal` / `.reveal.in`. No pongas `transform` ni `opacity` en un elemento que lleva `appReveal`: anula la animación de entrada. Envuelve el elemento o aplica el efecto a un hijo.
- Los estilos de un padre no alcanzan el interior de un hijo ni el HTML de `[innerHTML]`: por eso el texto rico usa `.rico` global + variables CSS (ver «Contenido»).
- Dentro de un componente cuyo host es `<svg>`, los hijos se escriben `<svg:path>`, `<svg:circle>`…
- Todo lo que toca `window`, `IntersectionObserver` o `requestAnimationFrame` va en `afterNextRender`: el build y `ng serve` renderizan en Node. **Su limpieza también**: registra `destroyRef.onDestroy(...)` *dentro* del callback de `afterNextRender` (inyecta `DestroyRef` en el constructor). Si lo registras en el constructor, se ejecuta en el servidor y falla (`cancelAnimationFrame is not defined`).
- Sin zone.js: los cambios de signals se pintan de forma asíncrona. En pruebas, espera a `fixture.whenStable()` antes de leer el DOM.
- Assets en `frontend/public/assets/`, referenciados como `assets/...`.

## Backend (`backend/`)

**Production does not use Docker**: the hosting's Apache + PHP 8.5 and its SMTP server (`host51.latinoamericahosting.com:465`, SSL). Docker is the local environment only (PHP 8.5 + Apache, MariaDB 10.11 like the hosting). Local email goes to a Mailtrap sandbox inbox (`MAILER_DSN` in your local `.env` only). No PHP or Composer on this machine: run everything through Docker.

```sh
cd backend
cp .env.example .env                                   # first time only
docker compose up -d --wait                            # API → http://localhost:8080 · MariaDB → localhost:3307 (ports in .env)
docker compose exec api php bin/console                # lists commands: migrate, rollback, seed, user:create, user:password, mail:send, import:legacy
docker compose exec api vendor/bin/phpunit             # tests (in-memory SQLite, never touch MariaDB)
docker compose exec api vendor/bin/phpunit --filter LoginTest      # one class/test
docker compose exec api vendor/bin/phpunit --testsuite unit        # unit | integration | functional
docker compose exec api composer require vendor/package
```

**Code is in English** (classes, folders, new tables, env vars, routes, JSON keys, error codes); only user-facing messages are in Spanish. **Hexagonal architecture** — Domain ← Application ← Infrastructure, enforced by `tests/Unit/ArchitectureTest.php`:
- `src/Domain/<Area>/` — plain PHP: entities, enums, rules, repository interfaces. Exceptions in `Domain/Shared/Exception` carry no HTTP status.
- `src/Application/<Area>/` — one use case per action (`Login`, `RequestAccessLink`…), autowired; technical ports in `Application/Port` (`RateLimiter`, `PasswordHasher`, `MailQueue`); input validation with `Validator::validate($input, ['field' => 'required|int:1,120'])`.
- `src/Infrastructure/` — adapters: `Http/Controller/*Controller` (HTTP only), `Http/Middleware`, `Http/ErrorHandler` (exception → `{"error": {"code", "message", "fields"?}}` + status), `Persistence/Model/*Model` (Eloquent, only here; `toEntity()`), `Persistence/Repository/*`, `Mail`, `Console/*Command`.
- **Where things are**: `backend/README.md` has the full route table, commands, deploy and data-migration steps; every route and its guard in `config/routes.php`; which adapter implements each port in `config/container.php`; console commands in `config/console.php`.
- **New feature** = entity/repository interface in Domain → use case in Application → Eloquent model + repository + controller in Infrastructure → bind the port in `container.php` → route in `routes.php`.
- Migrations: `database/migrations/YYYY_MM_DD_HHMMSS_*.php` return `new class implements Migration { up/down(Builder $schema) }`. The first one (`migrate_from_angular_ob`) converts a copy of the production database; it was tested with the real dump (`../origamib_online.sql`, outside the repo: it has personal data). `rollback` is blocked with `APP_ENV=production` unless `--force`. Seeds: `database/seeds/NN_*.php` return an idempotent callable.
- Functional tests extend `Tests\Support\FunctionalTestCase` (real app, migrated SQLite, `FrozenClock` in `$this->clock`) and use `$this->request('GET', '/path')`.

Traps:
- Route closures and `group(...)` closures **must not be `static`**: Slim binds them to the container (*Cannot bind an instance to a static closure*).
- Outside Docker there is no `vendor/` nor PHP: use `docker compose exec api …` (or `docker compose run --rm --no-deps api …` when the stack is down).
- Deploy on the PHP hosting: upload the project **with `vendor/`** (`composer install --no-dev` first) and a real `.env`. If possible the web root goes to `public/`; otherwise the root `.htaccess` rewrites everything to `public/` and `.env`, `src/`, `vendor/` answer 403/404. If the API lives in a subfolder, set `APP_BASE_PATH` (e.g. `/api`). Use `APP_DEBUG=false` in production.
- The hosting MUST run PHP 8.5 (today it reports 8.4.27): dependencies are resolved for 8.5 (`config.platform.php`).
- Domain naming: the old `Assistant`/`assistants` is **`Attendee`/`attendees`** (entity, table, `id_attendee` columns, `/attendees` routes). Never reintroduce "assistant".
- Email: Symfony Mailer configured only through `MAILER_DSN` in `.env` (SMTP with TLS; never credentials in code; production refuses anything else). Every email type, its subject, template and recipients: `src/Application/Mail/EmailCatalog.php`. Templates in `resources/emails/` (`.html.twig` escaped; text part from `.txt.twig` or derived from the HTML). Existing emails are ported as production sends them today (design D6c), so some subjects still say 2025/2026. Everything goes through the `email_outbox` table; `php bin/console mail:send` (hosting cron, every minute) delivers it. Functional tests use `RecordingMailer` (`$this->mailer`; `->failing = true` simulates a down server).
- When touching MariaDB by hand: `mariadb --default-character-set=utf8mb4 …`, or accents get stored broken («FrancÃ©s»).
- SQLite (tests) does not see MariaDB's foreign keys nor its non-transactional DDL: any migration that touches the legacy database is also tested by loading the dump into Docker's MariaDB, then `docker compose down -v` so no personal data is left behind.
- Participant numbers are assigned under a MariaDB named lock (`GET_LOCK`) in `EloquentAttendeeRepository`. Do not go back to `SELECT MAX(code) … FOR UPDATE`: concurrent registrations deadlock (error 1213). Anything concurrent must also be tested with parallel requests against Docker's MariaDB, not only SQLite.
- Uploaded images (workshop photos, partner logos) go through the `ImageStorage` port: type checked from the content, files with PHP/script inside refused, stored in `storage/images/` (outside `public/`) under generated names and served only by `GET /images/{file}`. Legacy names (`taller_12.jpg`, `partner_3.png`) are still served. Never serve uploads as static files.
- Workshop enrolment (`Application/Schedule/WorkshopEnrolment`) runs in one transaction (`TransactionManager` port) with the workshop rows locked in ascending id order; a two-part workshop is enrolled/cancelled as a unit. Keep that order if you add more locks, and re-run the parallel-request checks against MariaDB (design D7/D11).
- Tests are hermetic: with `APP_ENV=testing` the bootstrap does not read `.env`, and `phpunit.xml` pins every congress setting, because Docker injects `.env` into the container's environment. A new setting must get a fixed value in `phpunit.xml` too.
