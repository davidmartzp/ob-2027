# Proposal

## Why

El sitio nuevo (`frontend/`, sistema «El Diagrama») ya cubre la home, `/actividades` e `/inscripciones`, pero el sitio antiguo (`../angular-ob`) tiene seis páginas públicas más que el congreso necesita: los talleristas inscriben sus talleres en `/talleres`, los asistentes eligen talleres el día del evento en `/inscribir-talleres`, la programación se consulta en `/booklet`, los documentos en `/recursos`, el grupo organizador se presenta en `/amigos-plegadores`, y no hay página 404. El backend nuevo (`backend/`) ya expone todos los servicios que estas páginas usan, así que es el momento de rediseñarlas y conectarlas.

## What Changes

- Seis páginas nuevas en `frontend/`, con el diseño y las reglas de contenido de la home (JSON por tema, español e inglés, prerender estático):
  - `/talleres` — talleres del congreso (galería pública), talleres internacionales destacados y el espacio del tallerista (buscar por correo, proponer, editar, retirar, foto y socios).
  - `/inscribir-talleres` — el día del congreso: verificar correo + número dentro del turno, ver los talleres del día con filtros, inscribirse y cancelar, y ver «mis talleres».
  - `/booklet` — programa de talleres por día con filtros (nivel, público, salón, tallerista), imprimible.
  - `/recursos` — documentos descargables y cronograma embebido.
  - `/amigos-plegadores` — quiénes organizan: historia, reuniones, evento anual, la chiva y galería «El parche».
  - Página 404 propia, en lugar de redirigir a la home.
- Cliente de la API compartido (`core/api.ts`) con la URL del backend en el contenido y manejo uniforme de errores `{"error": {"code", "message", "fields"}}`; las páginas con datos vivos los cargan en el navegador sobre el HTML prerenderizado, con estados de carga, vacío, cerrado y error.
- **BREAKING (frontend ↔ backend)** La inscripción (`core/registro.ts`) pasa de `POST {apiUrl}/assistants` a `POST {apiUrl}/attendees`.
- Menú y pie: `talleres`, `recursos`, `booklet` y `amigos-plegadores` apuntan a sus páginas (hoy `talleres` y `recursos` apuntan a anclas de la home).

## Capabilities

### New Capabilities
- `frontend/conexion-api`: cliente de la API del backend, su configuración y el comportamiento común de las páginas con datos vivos (carga, vacío, error, sin API configurada).
- `frontend/talleres`: página `/talleres`: galería pública, talleres internacionales y gestión de talleres por el tallerista.
- `frontend/inscripcion-talleres`: página `/inscribir-talleres`: acceso por turno, talleres del día e inscripción.
- `frontend/programa`: página `/booklet` con la programación pública y filtros.
- `frontend/recursos`: página `/recursos` con documentos y cronograma.
- `frontend/amigos-plegadores`: página `/amigos-plegadores`.
- `frontend/pagina-no-encontrada`: página 404.

### Modified Capabilities
- Ninguna (no hay specs archivadas en `openspec/specs/`).

## Impact

- Código: `frontend/src/app/paginas/*` (seis páginas), `components/` nuevos, `core/api.ts`, `core/registro.ts`, `app.routes.ts`, `src/contenido/*.json` (nuevos: `talleres.json`, `inscribir-talleres.json`, `booklet.json`, `recursos.json`, `amigos-plegadores.json`, `no-encontrada.json`; cambian `menu.json`, `meta.json`, `evento.json`), imágenes en `frontend/public/assets/` desde `originales/congreso/images`.
- Origen del contenido: build de `../angular-ob/dist/angular-ob/browser/main-*.js` (plantillas y traducciones compiladas; el `src` de `angular-ob` está vacío) e imágenes de `originales/congreso/images`.
- Backend: solo se consume; rutas públicas `GET /workshops`, `POST /workshops/by-email`, `POST|GET /attendees/{id}/workshops`, `PUT|DELETE /workshops/{id}`, `POST /workshops/{id}/image`, `GET|PUT /workshops/{id}/partners`, `GET /partners`, `POST /attendees/verify-code`, `POST /attendees/remind-code`, `GET /events/active`, `GET /events/schedule`, `GET|POST /attendees/{id}/events`, `DELETE /attendees/{id}/events/{event}`.
- Despliegue: el hosting debe servir la 404 prerenderizada (`ErrorDocument`).

### Fuera de alcance
- Proyecto `cms/` (incluidas las listas de inscritos por taller con datos de contacto, que en el sitio antiguo estaban en `/booklet` y `/congreso/listas`).
- Portugués y francés (el sitio antiguo los tenía; el nuevo trabaja en español e inglés).
- El «taller virtual» de 2026 (Oriol Esteve) y el contador hacia la apertura de inscripciones a talleres como reloj en vivo (se reemplaza por un interruptor y una fecha).
- Cambios en el backend.
