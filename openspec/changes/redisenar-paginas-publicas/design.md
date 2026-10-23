# Design

## Context

- Motivación y alcance: `proposal.md`. Comportamiento: `specs/frontend/*`.
- `frontend/` es Angular 22 con prerender estático (SSG, sin Node en producción), contenido en `src/contenido/*.json` con la regla `{es, en}`, una página interna = componente en `paginas/` + ruta en `PAGINAS` + `<app-pagina-interna fondo="…">` + entrada en `meta.json` (ver `CLAUDE.md`). Ya existen tres páginas: `inicio`, `actividades` (buscador + fichas `<details>`) e `inscripciones` (wizard con `core/registro.ts`).
- El sitio antiguo `../angular-ob` solo conserva su build: el `src` está vacío y las carpetas prerenderizadas (`talleres/`, `booklet/`…) también. El contenido de cada página se extrajo de `dist/angular-ob/browser/main-*.js` (plantillas y `translations.ts` compilados); las imágenes están en `originales/congreso/images`.
- El backend nuevo ya expone todo; los asistentes no inician sesión (backend `design.md` D5): se identifican por correo o id.

## Goals / Non-Goals

**Goals:** cada página con el lenguaje de «El Diagrama» (pasos con separadores de pliegue, dobleces entre secciones, papel con grano, sombras duras), contenido editable en JSON, accesible (teclado, foco, `aria-live`), responsiva en 1020/760 px y con `prefers-reduced-motion`.

**Non-Goals:** CMS, portugués/francés, nuevas funciones que el sitio antiguo no tenía (salvo el filtro en URL del booklet y la galería con teclado).

## Decisions

### D1. Piezas compartidas antes que páginas
- `core/api.ts`: `api<T>(método, ruta, cuerpo?)` sobre `fetch`, base `evento.json → apiUrl` (se mueve desde `inscripciones.json`), devuelve el JSON o lanza `ErrorApi {status, code, message, fields}`; `ErrorRed` aparte.
- `components/estado-datos/`: un bloque para «cargando», «vacío», «error + reintentar», «sin API (modo demostración)» y «cerrado», con el tono rolo del sitio. Todas las páginas con datos vivos lo usan.
- `components/ficha-taller/`: tarjeta de taller reutilizada por `/talleres`, `/booklet` e `/inscribir-talleres` (foto o `PapelPlegado`, chips de nivel y público, duración, tallerista, socios). Tres variantes por entrada: `galeria`, `programa`, `inscripcion` (con cupos y botón).
- `components/socios-taller/`: logos y botón WhatsApp de los socios de un taller (`whatsapp_url`, `target="_blank" rel="noopener"` vía `appEnlace`).
- Datos vivos en `afterNextRender` (regla de `CLAUDE.md`: nada de `window`/`fetch` en el render del servidor); el HTML prerenderizado lleva todo el contenido fijo y la meta.
- Etiquetas de nivel y público en `comun.json` (`P` Principiante, `I` Intermedio, `A` Avanzado; `A` Adultos, `N` Niños), traducidas.

### D2. Plan por página

**`/talleres` (fondo de cabecera `--ruana`)** — hoy en angular-ob: banners, `list-workshops` («Nuestros Talleres», carrusel), `workshops-open-inscriptions` (talleres internacionales: Juan Landeta — Murciélago, sábado; Anicé Claudéon — Thunder Bird, domingo; nivel intermedio–avanzado, papel 30–35 / 35–45 cm, 90 min, biografías), `virtual-workshop` (Oriol Esteve, 2026) y `workshops-form` («Inscribe tus talleres»).
1. Cabecera: título «Talleres», intro tomada de `seo.talleres` y `workshopsInscriptions.description1`, separador con anotación a mano.
2. **Paso I · Talleres internacionales** (fondo `--paramo`): dos tarjetas «modelo a plegar» con foto del modelo y del tallerista, ficha técnica en tipografía mono (nivel, papel, duración, día) y biografía; contenido en `talleres.json → internacionales` con `porConfirmar`.
3. **Paso II · Nuestros talleres** (fondo papel): galería de `GET /workshops` con `ficha-taller` variante `galeria` y vista ampliada en `<dialog>`; filtro rápido por nivel y público. Reemplaza el carrusel por una cuadrícula (más legible y accesible).
4. **Paso III · ¿Vas a dictar un taller?** (fondo `--frailejon`): texto del `workshops-form` antiguo («Todos los participantes pueden dictar talleres…»), campo de correo → `POST /workshops/by-email`; por persona: lista de sus talleres con editar / retirar / foto / socios, y «Proponer taller» (formulario con validación de D1 y vista previa de la foto). Retirar pide confirmación en `<dialog>`.
5. Patrocinadores (marco de página interna).
- Se descarta el taller virtual de 2026 (fuera de alcance).

**`/inscribir-talleres` (fondo de cabecera `--paramo`, acentos `--pajizo`)** — hoy `public-attendees`: login con correo + número, animación de bienvenida, lista de eventos activos con filtros (salón, hora, tallerista), choques, modal de confirmación, «mis talleres», cancelar.
1. Cabecera con la explicación de los turnos (grupos de 25 cada 10 minutos desde las 08:00, solo los días elegidos) tomada de `preguntas.json` y del texto de turnos.
2. **Paso I · Tu número**: formulario correo + número → `verify-code`; mensaje del turno en `aria-live`; enlace «¿Olvidaste tu número?» → `remind-code`. Sesión local en `sessionStorage` (`id`, nombre, número).
3. **Paso II · Talleres de hoy**: `GET /events/active` agrupado por franja horaria (líneas de pliegue como divisores de hora), filtros salón/hora/tallerista, `ficha-taller` variante `inscripcion` con cupos y estado («Inscrito», «Sin cupos», «Choca con…»). Se calcula el choque en el navegador para avisar antes, pero manda la respuesta del backend.
4. **Paso III · Mis talleres**: resumen fijo (barra lateral en escritorio, panel inferior en móvil) con cancelar; aviso de que los talleres en dos partes se inscriben juntos.
5. 403 `logout` → limpiar sesión y mostrar el mensaje del backend en el Paso I.
- Sin animación de bienvenida en confeti; se reemplaza por una anotación a mano «¡Listo, sumercé!» (respeta `prefers-reduced-motion`).

**`/booklet` (fondo de cabecera `--paramo`)** — hoy `booklet`: portada (cover.png), filtros día/nivel/público/sala/instructor, tarjetas y, al abrir una, la lista de participantes con correo y teléfono.
1. Cabecera «Programa de talleres» con botón «Imprimir».
2. Filtros como chips (patrón de `/actividades`) sincronizados con la URL (`?dia=&nivel=&publico=&sala=&tallerista=`).
3. Programa por día y hora desde `GET /events/schedule` con `ficha-taller` variante `programa` dentro de `<details name="taller">`.
4. Sin participantes ni datos de contacto (spec `programa`); las listas viven en el CMS.
5. `@media print`: sin cabecera, filtros, dobleces ni grano; `break-inside: avoid` por taller.

**`/recursos` (fondo de cabecera `--frailejon`)** — hoy `resources-list` + `resources-embed`: «Recursos del Evento», cinco documentos (Guía para Participantes, Cronograma, Bases intercambio de tarjetas, Bases diagramas ES/EN) y el cronograma embebido con Google Docs Viewer.
1. Cabecera con título y subtítulo del sitio antiguo.
2. **Paso I · Documentos**: lista tipo «hoja de piezas» del diagrama (numerada, con ícono por tipo de archivo, botón «Descargar»/«Abrir»); `recursos.json → documentos`.
3. **Paso II · Cronograma** (fondo papel 2): `<iframe>` con el visor, `loading="lazy"`, alto fijo y enlace alternativo siempre visible.
4. La home: el destino `recursos` del menú pasa de `#itinerario` a `recursos`; el itinerario gana un enlace «Ver recursos».

**`/amigos-plegadores` (fondo de cabecera `--frailejon`)** — hoy: «¿Qué es Amigos Plegadores en Bogotá?», apartados 01 El Origen, 02 Las Reuniones (AseisManos), 04 El Evento Anual, 05 La Chiva (el 03 no existe en el original), galería «El parche» (11 fotos) y foto de la chiva.
1. Cabecera con el título y la bajada («Un parche de amigos con una obsesión compartida por el origami, la cerveza y el caos creativo»).
2. Cuatro pasos del diagrama, numerados de nuevo del I al IV (se corrige el salto 02→04), alternando fondo papel / `--papel-2`, cada uno con su foto (origen3, organizadores, convención, chiva).
3. **El parche**: galería en cuadrícula con fotos `loading="lazy"` y vista ampliada en `<dialog>` con flechas y Escape.
4. Textos `es` del original y traducción `en` nueva, en `amigos-plegadores.json`.

**404** — hoy `not-found`: «404 · ¡Ups! Esta página se perdió en un pliegue de origami…» con enlaces a Inicio, Talleres, Inscripciones y Recursos.
1. Página en `paginas/no-encontrada/` sin hero (pantalla sola): un papel «desdoblado» con el símbolo de valle, el mensaje y los cuatro enlaces.
2. Ruta `**` (y `en/**`) → `NoEncontrada` en lugar de `redirectTo: ''`; prerender de `/404` y `public/.htaccess` con `ErrorDocument 404 /404/index.html` (y equivalente para `/en/`).

### D3. Menú y meta
`menu.json`: destinos nuevos `talleres` → `talleres`, `recursos` → `recursos`, `booklet` → `booklet`, `inscribirTalleres` → `inscribir-talleres`, `amigos` → `amigos-plegadores`; la lista `congreso` y el pie los incluyen. `meta.json`: una entrada por página con el título y la descripción de `seo.*` del sitio antiguo, adaptados a 2027 y a las fechas de `evento.json` (04–06 de junio).

### D4. Interruptores
`evento.json` gana `inscripcionTalleresAbierta`: con `false`, `/inscribir-talleres` no muestra el formulario y explica cuándo abre (la fecha del congreso). El espacio del tallerista en `/talleres` sigue el interruptor existente `inscripcionesAbiertas`. Las páginas nuevas respetan `secciones` (p. ej. `talleres: false` oculta la página y sus enlaces), como ya hace `actividades`.

## Risks / Trade-offs

- [Textos extraídos de un bundle minificado] → se revisan uno por uno al pasarlos a JSON; las traducciones al inglés de `amigos-plegadores` son nuevas y requieren revisión del equipo.
- [Sin sesión de asistente, la sesión local de `/inscribir-talleres` es solo comodidad] → no da permisos; coherente con la decisión del backend (D5 del backend).
- [El visor de Google para el cronograma depende de un tercero] → enlace directo al PDF siempre visible.
- [Fotos heredadas pesadas] → se exportan a WebP y tamaños razonables al copiarlas a `public/assets/`.

## Migration Plan

1. Piezas compartidas (D1) y el cambio de `core/registro.ts` a `/attendees`.
2. Páginas en este orden: `/recursos` y 404 (estáticas), `/amigos-plegadores`, `/booklet`, `/talleres`, `/inscribir-talleres`.
3. Menú, meta y `.htaccess` al final, con todas las rutas existentes.
4. Antes de publicar: `apiUrl` apuntando al backend desplegado y prueba en 1020/760 px y `prefers-reduced-motion`.

## Open Questions

- Fotos y redes de los talleres internacionales 2027 (el build solo trae los nombres de Instagram): el contenido queda con ✳ hasta tenerlas.
- Textos finales de la página de turnos (hora de apertura por grupo) para 2027.
