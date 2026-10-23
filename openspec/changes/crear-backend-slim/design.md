# Design

## Context

- Motivación y alcance: ver `proposal.md`. Comportamiento exigido: ver `specs/backend/*`.
- La API en producción es `app-ob/` dentro de `../angular-ob` (Laravel 9, PHP 8.2, Sanctum). La copia recuperada trae `routes/api.php` (catálogo completo), `config/`, `docker-compose.yml`, `MODULO_EMAIL_CMS.md` (diseño del módulo de correo del CMS), las plantillas `plantilla_*.html` y el CMS compilado en `dist/angular-ob/browser/`. **No trae** `app-ob/app`, `app-ob/database/migrations` ni `app-ob/resources/views` (carpetas vacías) ni el `src` de Angular. Los contratos se toman de las rutas, del build (servicio `partners`: `name`, `whatsapp`, `message`, `logo`), de `MODULO_EMAIL_CMS.md` (filtros heredados) y de la lógica de `congreso-origami` rama `main`.
- No hay PHP ni Composer en la máquina: todo corre en Docker. El hosting de producción es Apache + PHP (sin Node).
- El frontend nuevo ya construye el JSON de inscripción (`frontend/src/app/core/registro.ts → EnvioInscripcion`), que este backend acepta tal cual en `POST /attendees`.

## Goals / Non-Goals

**Goals:**
- Código pequeño y explícito: un módulo por servicio, sin capas vacías.
- Paridad funcional con el catálogo de `angular-ob/app-ob/routes/api.php`, con los huecos de seguridad cerrados.
- Las pruebas funcionales corren contra la app real sobre SQLite en memoria, sin red ni MySQL.

**Non-Goals:**
- Compatibilidad hacia atrás con las rutas heredadas (el sitio y el CMS antiguos se retiran juntos).
- Colas de trabajo, caché distribuida o multi-inquilino.

## Decisions

### D1. Slim 4 + PHP-DI + Eloquent standalone
Slim 4 (PSR-7/15) da enrutado y middleware sin el resto del framework; PHP-DI con autowiring inyecta controladores y servicios; `illuminate/database` (Capsule) aporta el ORM Eloquent y el *schema builder* que ya conoce el equipo. *Alternativas:* Laravel 12 (es justo lo que se quiere aligerar), Symfony (más pesado), Mezzio (menos conocido por el equipo).

### D2. Estructura del proyecto
```
backend/
├── public/index.php, .htaccess        ← única entrada web
├── .htaccess                          ← si la raíz web no puede ser public/: reescribe a public/ y bloquea el resto
├── bin/consola                        ← migrar | revertir | sembrar | importar-legado | usuario:crear | usuario:clave | correo:procesar
├── config/arranque.php                ← .env → contenedor → Eloquent → Slim (lo comparten web, consola y pruebas)
├── config/{dependencias,middleware,rutas,ajustes}.php
├── src/Compartido/                    ← Errores de dominio, Validador, Respuesta JSON, middleware (Cors, Autenticacion, LimiteIntentos, ErrorJson)
├── src/Modulos/<Nombre>/              ← Rutas<Nombre> (implementa Modulo), Controlador<Nombre> (solo HTTP), Servicio<Nombre> (lógica), modelos Eloquent
│     Salud, Autenticacion, Attendees, Talleres, Partners, Agenda (Rooms, Activities, Events), Correo, Disparadores
├── plantillas/correos/*.html.twig     ← fuera de public/
├── database/migraciones/, database/semillas/
├── almacen/imagenes/                  ← imágenes de talleres (servidas por una ruta, no por URL directa al disco)
├── tests/                             ← CasoFuncional + una clase por módulo
└── docker/ (Dockerfile php:8.5-apache), compose.yaml (api, db mysql:8.4, correo mailpit)
```
Identificadores en español salvo los nombres de dominio del contrato heredado (`attendees`, `workshops`, `events`, `rooms`, `activities` y sus campos), que conservan el inglés porque son la API que consumen el sitio y el CMS.

### D3. Mapa de rutas heredadas → nuevas
| Antes (`app-ob/api`) | Ahora | Acceso |
|---|---|---|
| `POST /assistants` | `POST /attendees` | público |
| `GET /assistants` | `GET /attendees` | CMS |
| `PUT /assistants/{id}` (incl. `status: 5` para borrar) | `PUT /attendees/{id}` · `DELETE /attendees/{id}` | CMS |
| `POST /confirmManyAttendees` | `POST /attendees/confirmar` | CMS |
| `POST /assistants/verifyAttendeeCode` · `POST /searchCode` | `POST /attendees/verificar-codigo` | público |
| `POST /assistants/lookup-code` (devolvía el número) | `POST /attendees/recordar-codigo` (lo envía por correo) | público |
| `GET /generateCodes` · `/generateCodesAfter` · `/assignAssistantCodes` | `POST /attendees/codigos` | CMS |
| `GET /resetCodes` | `POST /attendees/codigos/reiniciar` | CMS |
| `GET /sendMails` · `/sendNumbers` · `/resendWelcomeEmail` · `/sendInstructions` · `/sendBook` · `/sendWhatsapp` · `/sendGeneralInfo` · `/sendCorrection` | `POST /correos/{tipo}` (`numero`, `bienvenida`, `instrucciones`, `libro`, `aviso-importante`, `informacion-general`, `correccion`) + `GET /correos/{tipo}/destinatarios` · `GET /correos/registro` | CMS |
| `/email-triggers` · `/email-trigger-events` | **eliminadas** (no se usan) | — |
| `GET /partners` · `GET /workshops/{id}/partners` | iguales | público |
| `POST|PUT|DELETE /partners` (id en el cuerpo) · `POST /partners/upload-logo` | `POST /partners` · `PUT|DELETE /partners/{id}` · `POST /partners/{id}/logo` | CMS |
| `POST /workshops/{id}/partners` (público) | `PUT /workshops/{id}/partners` | autor o CMS |
| `POST /workshops {email}` | `POST /auth/enlace-asistente` → `GET /attendees/{id}/workshops` | asistente |
| `POST /addworkshops` | `POST /attendees/{id}/workshops` | asistente |
| `PUT /workshops` · `DELETE /workshops` (id en el cuerpo) | `PUT /workshops/{id}` · `DELETE /workshops/{id}` | asistente autor o CMS |
| `POST /upload-image` | `POST /workshops/{id}/imagen` | asistente autor o CMS |
| `GET /getWorkshops` | `GET /workshops` | público |
| `GET /rooms` · `GET /activities` | iguales + CRUD en CMS | público / CMS |
| `GET/POST /events` · `DELETE /events/{id}` | iguales | CMS |
| `GET /events/get-active` | `GET /events/activos` | público |
| `POST /events/storeEventAttendees` | `POST /attendees/{id}/events` | asistente |
| `GET /events/getEventIdsByAttendee/{id}` | `GET /attendees/{id}/events` | asistente |
| `POST /events/removeEventAttendee` | `DELETE /attendees/{id}/events/{idEvento}` | asistente |
| `GET /events/getWorkshopsActive` · `/events/workshops` | `GET /events/talleres` | CMS |
| `GET /events/getAttendeesByEvent/{id}` · `POST /getAttendees` | `GET /events/{id}/attendees` | CMS |
| `POST /login` · `POST /logout` · `GET /user` | `POST /auth/login` · `POST /auth/logout` · `GET /auth/yo` | público / CMS |
| `POST /register` · `/clearConfigCache` · `/clearAllCache` · `/admin/*` | **eliminadas** (consola del servidor) | — |
| `storeMenu`, `deleteMenu`, `updateMenu` (modelo `workshops_menu`, 2024) | absorbidos por `events` + `event_attendees` | — |

### D4. Esquema
- `attendees` (antes `assistants`): columnas de `attendee.model.ts` (`code`, `name`, `lastname`, `age`, `country`, `city`, `email` indexado, `phone`, `companion`, `idname`, `paydate`, `paymethod`, `receipt`, `origamigroup`, `info`, `cards`, `cardsgroup`, `days`, `staff`, `special_code`, `go_to_chiva`, `printCertificate`, `chiva_companions`, `pay_amount` texto, `status`, timestamps). Estados: 0 pendiente, 1 confirmado, 5 eliminado (heredados del CMS).
- `workshops` (`id_attendee` FK, `name`, `public`, `level`, `time`, `observations`, `image`, `status`), `rooms` (`name`, `capacity`), `activities` (`name`, `public`, `level`, `time`, `observations`, `status`).
- `events` (`id_room`, `id_workshop` | `id_activity`, `day`, `start_time`, `end_time`, `capacity` nulo = capacidad del salón, `active`).
- `event_attendees` (`id_event`, `id_attendee`, único por par).
- `partners` (`name`, `whatsapp`, `message`, `logo`) y `partner_workshop` (`id_partner`, `id_workshop`, único por par).
- `email_envios` (cola + registro: `tipo`, `id_attendee`, `email`, `subject`, `body`, `estado`, `intentos`, `disponible_en`, `enviado_en`, `error`, `clave_unica` = tipo+asistente, nula en reenvíos). No se crean `email_triggers` ni las tablas `email_campaigns/filters/recipients/logs` de `MODULO_EMAIL_CMS.md`.
- `users` (heredada, hash bcrypt), `tokens_acceso` (`tipo` cms|asistente, `id_sujeto`, `hash` SHA-256 único, `caduca_en`, `revocado_en`), `enlaces_acceso` (hash, `id_attendee`, `caduca_en`, `usado_en`), `intentos` (clave, ventana) para los límites.

### D5. Autenticación con tokens opacos en base de datos
Tokens aleatorios de 32 bytes (`random_bytes`), entregados en claro una sola vez y guardados como SHA-256; un middleware los resuelve por tipo (`cms` o `asistente`) y adjunta el sujeto a la petición. Contraseñas con `password_verify`/`password_hash` (bcrypt actual; `password_needs_rehash` las sube a Argon2id al iniciar sesión). *Alternativa descartada:* JWT, porque exige gestionar revocación aparte y no aporta nada en un solo servidor.

### D6. Correo: Symfony Mailer + Twig
`symfony/mailer` standalone con `Transport::fromDsn(MAILER_DSN)`: soporta `smtps://` (TLS implícito, 465) y `smtp://` con STARTTLS automático, verifica el certificado por defecto y valida los encabezados (rechaza saltos de línea). Twig con `autoescape: 'html'` para todas las plantillas (versionadas en el repo, no editables desde el CMS) y una versión en texto plano. En producción el arranque rechaza DSN con `verify_peer=0` o sin cifrado. En Docker, Mailpit captura los correos (`smtp://correo:1025`, interfaz en `http://localhost:8025`). *Alternativa:* PHPMailer 7 — válido, pero mezcla transporte y mensaje en una clase, no tiene DSN ni plantillas, y Symfony Mailer es el que usa Laravel por debajo, así que el comportamiento de entrega no cambia. Se envía de forma síncrona tras guardar; los fallos se registran y no se propagan (spec `correo`).

### D6b. Cola de correo en base de datos + cron
Todo correo (automático o lanzado con `POST /correos/{tipo}`) se inserta en `email_envios` y lo despacha `bin/consola correo:procesar` (lote limitado por minuto, reintentos 1/5/30 min, bloqueo por fila con `FOR UPDATE SKIP LOCKED` en MySQL). En el hosting corre por cron cada minuto; en Docker, un servicio `cola` lo ejecuta en bucle. Los automáticos de acceso (enlace y recordatorio) se intentan enviar en el acto y, si fallan, quedan en la cola. *Alternativas:* worker permanente (no disponible en hosting compartido), envío síncrono masivo (bloquea la petición y no reintenta).

### D6c. Tipos de correo como código
Cada tipo es una clase pequeña en `src/Modulos/Correo/Tipos/` con su consulta de destinatarios (los filtros heredados de `MODULO_EMAIL_CMS.md` §1.2), su asunto y su plantilla Twig en `plantillas/correos/`. Añadir un correo = una clase + una plantilla + su alta en la lista de tipos. *Alternativa descartada:* disparadores configurables en el CMS (el módulo heredado no se usa: los envíos se lanzan a mano).

### D7. Concurrencia de cupos
La inscripción a eventos se hace en una transacción con `SELECT … FOR UPDATE` sobre la fila del evento, se cuenta `event_attendees` y se inserta; el índice único `(id_event, id_attendee)` impide duplicados. En SQLite (pruebas) la transacción serializa igual.

### D8. Validación sin dependencias pesadas
Un `Validador` propio pequeño (requerido, correo, entero en rango, en lista, longitud, hora `HH:MM`) que devuelve los mensajes por campo para el 422. *Alternativa:* `illuminate/validation`, que arrastra traductor y contenedor de Laravel.

### D9. Imágenes
Se valida el tipo real con `finfo` y `getimagesize`, máximo 5 MB, se guarda como `taller-<id>-<aleatorio>.<ext>` en `almacen/imagenes/` (fuera de `public/`) y se sirve con `GET /imagenes/{archivo}` con `Content-Type` fijo y `X-Content-Type-Options: nosniff`. La importación copia las imágenes y logos heredados de `app-ob/storage/app/public/`.

### D10. Turno de ingreso configurable
La regla de 2024 (grupos de 20 números cada 10 minutos desde una hora fija) pasa a `.env`: `TURNO_INICIO` (fecha y hora, vacío = sin turno), `TURNO_TAMANO_GRUPO`, `TURNO_MINUTOS`, zona `America/Bogota`.

## Risks / Trade-offs

- [El esquema real de `events`, `rooms`, `activities` y `event_attendees` en producción puede diferir del deducido] → `importar-legado` mapea columnas por nombre y falla con la lista de columnas que no reconoce; antes de migrar producción se revisa contra un volcado real.
- [PHP 8.5 en el hosting] → si el proveedor no lo ofrece, no se despliega; confirmar antes de la fase de despliegue. La imagen Docker fija `php:8.5-apache` y `config.platform.php = 8.5`.
- [Las rutas nuevas rompen el CMS y el sitio antiguos] → el corte es simultáneo: el backend nuevo se publica junto con `frontend/` y `cms/`; mientras tanto el antiguo sigue en `app-ob/` sin tocarlo.
- [Correo síncrono alarga la respuesta de inscripción] → aceptable para el volumen (cientos de inscripciones); el envío masivo de números es una acción explícita del CMS que reporta enviados/fallidos.
- [Faltan `app-ob/app` y las migraciones heredadas] → si aparecen, se revisa la spec contra la lógica real antes de la tarea 8.1; mientras tanto la importación falla de forma explícita ante columnas desconocidas.
- [`correccion` y `aviso-importante` eran correos puntuales de 2026] → se portan con texto genérico y sus destinatarios por defecto; se ajustan antes de usarlos.
- [El cron del hosting no corre] → `GET /salud` reporta la antigüedad del envío pendiente más viejo para detectarlo.
- [Enlace mágico depende de que el correo llegue] → el número de participante sigue sirviendo como segunda vía de acceso.

## Migration Plan

1. Desarrollo y pruebas en Docker con datos sembrados.
2. Obtener volcado de la base de producción (`mysqldump --default-character-set=utf8mb4`) y la carpeta de imágenes.
3. Cargar el volcado (incluidos `partners` y su tabla pivote; `email_triggers` y tablas de campañas se ignoran) en el MySQL de Docker y ejecutar `migrar` (la migración de transición renombra `assistants` → `attendees` y `id_assistant` → `id_attendee`) e `importar-legado` para lo que no sea renombrado directo; comparar conteos por tabla.
4. Desplegar en una subcarpeta nueva (p. ej. `/api`) con `APP_BASE_PATH`, sin tocar `app-ob/`.
5. Cambiar `apiUrl` en `frontend/` y `cms/` y publicar.
6. **Rollback:** volver a apuntar los frontends a `app-ob/` y restaurar el volcado previo; el backend nuevo no modifica la base antigua si se migra sobre una copia.

## Open Questions

- Lista definitiva de niveles (`level`) y públicos (`public`) de los talleres: hoy son letras (`P`, `A`…); se cargan como configuración y no cambian el diseño.
- Texto final y diseño de los correos con el sistema de diseño 2027 (base: `angular-ob/plantilla_*.html`).
