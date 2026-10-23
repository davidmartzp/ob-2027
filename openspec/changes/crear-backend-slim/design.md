# Design

## Context

- Motivación y alcance: ver `proposal.md`. Comportamiento exigido: ver `specs/backend/*`.
- La API en producción es `app-ob/` dentro de `../angular-ob` (Laravel 9, PHP 8.2, Sanctum), con el código completo: controladores `app/Http/Controllers/Api/V1/*`, modelos `app/Models/*`, `app/Services/EmailTriggerService.php`, correos `app/Mail/*` + `resources/views/mails/*`, y migraciones solo de `users`, tokens, cola y correo, `partners` y `workshop_partner`. Las tablas `assistants`, `workshops`, `rooms`, `activities`, `events` y `events_attendees` no tienen migración; su forma se confirmó con el volcado de producción `../origamib_online.sql` (2026-10-23: MariaDB 10.11.19, PHP 8.4.27 en el hosting; 169 asistentes, 69 talleres, 137 eventos, 978 inscripciones a eventos, 8 salones, 11 actividades, 2 usuarios con bcrypt). El volcado incluye además `obusers` (usuarios de un WordPress antiguo), que se ignora.
- Reglas heredadas que se conservan: número al inscribirse para elegibles; turnos de 25 números cada 10 min de 08:00 a 19:00 según `days`; `special_code`; ventana de eventos activos (sábado 08–19, domingo desde 08) con solo los del día; cupo en `workshops.tickets`; menores de 13 fuera de talleres `public = 'A'`; talleres en dos partes (`part`/`relation`); socios con baja lógica (`status`).
- Huecos heredados que se cierran: `POST /upload-image` público que guarda con la extensión del cliente en una carpeta servida por Apache (permite subir y ejecutar PHP); `/register` público + `/admin/migrate-fresh`; código maestro `99099` y cabecera `X-Test-Mode`; `lookup-code` devuelve números por correo; listas de correos personales escritas en el código (`sendCorrection`).
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

### D2. Arquitectura hexagonal y estructura (decisión del 2026-10-23)
Todo el código, los nombres de archivos, tablas nuevas, variables de entorno, rutas, claves JSON y códigos de error van **en inglés**; solo los mensajes que lee el usuario final (asistentes y equipo) van en español. Tres capas con la regla de dependencia Domain ← Application ← Infrastructure, vigilada por `tests/Unit/ArchitectureTest.php`:

```
backend/
├── public/index.php, .htaccess          ← única entrada web
├── .htaccess                            ← si la raíz web no puede ser public/: reescribe a public/ y bloquea el resto
├── bin/console                          ← migrate | rollback | seed | user:create | user:password | mail:send | import:legacy
├── config/
│   ├── bootstrap.php                    ← .env → Settings → contenedor → Eloquent → Slim (web, consola y pruebas)
│   ├── container.php                    ← qué adaptador implementa cada puerto
│   ├── routes.php                       ← TODAS las rutas, con su guardia (cms / attendee / cmsOrAttendee)
│   ├── middleware.php, console.php
├── src/
│   ├── Domain/                          ← PHP puro: entidades, enums, reglas, interfaces de repositorio, excepciones
│   │   ├── Attendee/   Attendee, AttendeeStatus, AttendeeRepository, (RegistrationTurns…)
│   │   ├── Auth/       User, Session, TokenType, UserRepository, AccessTokenRepository, AccessLinkRepository
│   │   ├── Workshop/  Schedule/  Partner/        (grupos 6–8)
│   │   └── Shared/Exception/   NotFound, Validation, Conflict, Unauthorized, Forbidden, TooManyAttempts
│   ├── Application/                     ← un caso de uso por acción + puertos técnicos
│   │   ├── Auth/       Login, Logout, GetCurrentUser, CreateUser, ChangeUserPassword, RequestAccessLink, RedeemAccessLink, TokenService
│   │   ├── Attendee/  Workshop/  Schedule/  Partner/  Mail/   (grupos 4–8)
│   │   ├── Port/       RateLimiter, PasswordHasher, MailQueue, EmailMessage
│   │   └── Shared/Validator
│   └── Infrastructure/                  ← adaptadores
│       ├── Http/       Controller/*Controller, Middleware/{Authenticate, Cors, SecurityHeaders}, ErrorHandler, Guards, Json
│       ├── Persistence/ Model/*Model (Eloquent), Repository/{Eloquent…, Db…}, Migration/{Migrator, Seeder}, DbRateLimiter
│       ├── Mail/       TwigMailQueue (y el envío con Symfony Mailer, grupo 4)
│       ├── Console/    *Command   ·   Config/Settings   ·   Clock/SystemClock   ·   Security/NativePasswordHasher
├── database/migrations/, database/seeds/
├── resources/emails/*.twig               ← plantillas de correo (fuera de public/)
├── storage/images/                       ← imágenes subidas (servidas por una ruta, no por URL directa al disco)
├── tests/Unit/ · tests/Integration/ · tests/Functional/ · tests/Support/{FunctionalTestCase, FrozenClock}
└── docker/, compose.yaml                 ← solo desarrollo local (api, db mariadb:10.11, queue)
```

- **Domain** no conoce Slim, Eloquent ni Twig. Las excepciones de dominio no llevan estado HTTP: `Infrastructure/Http/ErrorHandler` las traduce a `{"error": {"code", "message", "fields"?}}` (`validation_failed` 422, `not_found` 404, `unauthorized` 401, `forbidden` 403, `conflict` 409, `too_many_requests` 429, `bad_request` 400, `internal_error` 500).
- **Application** recibe interfaces (repositorios y puertos) y `Psr\Clock\ClockInterface`; los casos de uso se autoinyectan. Las pruebas cambian el reloj por `FrozenClock` en el contenedor.
- **Infrastructure**: los modelos Eloquent (`*Model`) solo viven aquí y se convierten a entidades con `toEntity()`. Controladores solo HTTP.
- Nombres de dominio heredados (`attendees`, `workshops`, `events`, `rooms`, `activities` y sus columnas, incluido el prefijo `id_` de las claves foráneas) se conservan para que la importación sea un renombrado. Tablas nuevas: `access_tokens`, `access_links`, `rate_limit_hits`, `email_outbox`, `schema_migrations`.
- *Alternativa descartada:* módulos por carpeta con nombres en español (lo hecho en los grupos 1–3 antes de esta decisión): los modelos y controladores quedaban dispersos y mezclados con la lógica.

### D3. Mapa de rutas heredadas → nuevas
| Antes (`app-ob/api`) | Ahora | Acceso |
|---|---|---|
| `POST /assistants` | `POST /attendees` | público |
| `GET /assistants` | `GET /attendees` | CMS |
| `PUT /assistants/{id}` (incl. `status: 5` para borrar) | `PUT /attendees/{id}` · `DELETE /attendees/{id}` | CMS |
| `POST /confirmManyAttendees` | `POST /attendees/confirm` | CMS |
| `POST /assistants/verifyAttendeeCode` (incl. código `99099`) · `POST /searchCode` | `POST /attendees/verify-code` (sin código maestro) | público |
| `POST /assistants/lookup-code` (devolvía el número) | `POST /attendees/remind-code` (lo envía por correo) | público |
| `GET /generateCodesAfter` | `POST /attendees/codes` | CMS |
| `GET /generateCodes` · `/assignAssistantCodes` · `/resetCodes` | `POST /attendees/codes/regenerate` | CMS |
| `GET /sendMails` · `/sendNumbers` · `/resendWelcomeEmail` · `/sendInstructions` · `/sendBook` · `/sendWhatsapp` · `/sendGeneralInfo` · `/sendCorrection` | `POST /emails/{type}` (`code`, `welcome`, `instructions`, `book`, `important-notice`, `general-info`, `correction`) + `GET /emails/{type}/recipients` · `GET /emails/log` | CMS |
| `/email-triggers` · `/email-trigger-events` | **eliminadas** (no se usan) | — |
| `GET /partners` · `GET /workshops/{id}/partners` | iguales | público |
| `POST|PUT|DELETE /partners` (id en el cuerpo; borrar = `status 0`) · `POST /partners/upload-logo` | `POST /partners` · `PUT|DELETE /partners/{id}` · `POST /partners/{id}/logo` | CMS |
| `POST /workshops/{id}/partners` (público) | `PUT /workshops/{id}/partners` | autor o CMS |
| `POST /workshops {email}` | `POST /auth/attendee-link` → `GET /attendees/{id}/workshops` | asistente |
| `POST /addworkshops` | `POST /attendees/{id}/workshops` | asistente |
| `PUT /workshops` · `DELETE /workshops` (id en el cuerpo) | `PUT /workshops/{id}` · `DELETE /workshops/{id}` | asistente autor o CMS |
| (cupo, partes: a mano en la base) | `GET /cms/workshops` · `PATCH /workshops/{id}` | CMS |
| `POST /upload-image` (público) | `POST /workshops/{id}/image` | asistente autor o CMS |
| `GET /getWorkshops` | `GET /workshops` | público |
| `GET /rooms` · `GET /activities` | iguales | CMS |
| `GET/POST /events` · `DELETE /events/{id}` | iguales | CMS |
| `GET /events/get-active` (+ `X-Test-Mode`) | `GET /events/active` (ensayo solo fuera de producción) | público |
| `GET /events/workshops` | `GET /events/schedule` | público |
| `POST /events/storeEventAttendees` | `POST /attendees/{id}/events` | asistente |
| `GET /events/getEventIdsByAttendee/{id}` | `GET /attendees/{id}/events` | asistente |
| `POST /events/removeEventAttendee` | `DELETE /attendees/{id}/events/{idEvento}` | asistente |
| `GET /events/getWorkshopsActive` | `GET /events/workshops` | CMS |
| `GET /events/getAttendeesByEvent/{id}` · `POST /getAttendees` | `GET /events/{id}/attendees` | CMS |
| `POST /login` · `POST /logout` · `GET /user` | `POST /auth/login` · `POST /auth/logout` · `GET /auth/me` | público / CMS |
| `POST /register` · `/clearConfigCache` · `/clearAllCache` · `/admin/*` | **eliminadas** (consola del servidor) | — |
| `storeMenu`, `deleteMenu`, `updateMenu` (modelo `workshops_menu`, 2024) | absorbidos por `events` + `event_attendees` | — |

### D4. Esquema
Se conserva el esquema heredado (para que la importación sea un renombrado, no una transformación) con estos cambios: `assistants` → `attendees`, `id_assistant` → `id_attendee` en `workshops`, `events_attendees` se mantiene con su nombre y columnas (`id_event`, `id_attendee`) más un índice único por par.
- `attendees`: columnas de `App\Models\Assistant` + `status` (0 pendiente, 1 confirmado, 5 eliminado), `code` único cuando no es nulo, índice por `email`.
- `workshops`: `id_attendee`, `name`, `public` (`A` adultos, `N` niños), `level` (`P` principiante, `I` intermedio, `A` avanzado), `duration`, `observations` (500), `image`, `status` (0/1/2), `tickets` (por defecto 20), `part` (por defecto 1), `relation`. La columna `time` (vacía en producción) se elimina; las del modelo de 2024 (`schedule`, `day`, `room`, `isactivity`, `id_event`) no existen en producción.
- `rooms` (`name`), `activities` (`name`, `description`, `duration`), `events` (`id_room`, `day` = `Saturday`|`Sunday`, `start_time`, `end_time`, `id_workshop` | `id_activity`, timestamps; la regla «uno u otro» se valida en el servicio porque la tabla de producción no tiene el `CHECK`).
- `partners` (`name`, `logo`, `whatsapp`, `message`, `status`) y `workshop_partner` (`id_workshop`, `id_partner`, único por par), como sus migraciones heredadas.
- `email_outbox` (cola + registro: `tipo`, `id_attendee`, `email`, `subject`, `body`, `estado`, `intentos`, `disponible_en`, `enviado_en`, `error`, `clave_unica` = tipo+asistente, nula en reenvíos). No se crean `email_triggers`, `email_campaigns`, `email_filters`, `email_recipients`, `email_logs`, `email_templates` ni `jobs`.
- `users` (heredada, hash bcrypt), `access_tokens` (`tipo` cms|asistente, `id_sujeto`, `hash` SHA-256 único, `caduca_en`, `revocado_en`; sustituye a `personal_access_tokens`), `access_links` (hash, `id_attendee`, `caduca_en`, `usado_en`), `rate_limit_hits` (limit_key, hit_at) para los límites.

### D5. Autenticación con tokens opacos en base de datos
Tokens aleatorios de 32 bytes (`random_bytes`), entregados en claro una sola vez y guardados como SHA-256; un middleware los resuelve por tipo (`cms` o `asistente`) y adjunta el sujeto a la petición. Contraseñas con `password_verify`/`password_hash` (bcrypt actual; `password_needs_rehash` las sube a Argon2id al iniciar sesión). *Alternativa descartada:* JWT, porque exige gestionar revocación aparte y no aporta nada en un solo servidor.

### D6. Correo: Symfony Mailer + Twig
`symfony/mailer` standalone con `Transport::fromDsn(MAILER_DSN)`: soporta `smtps://` (TLS implícito, 465) y `smtp://` con STARTTLS automático, verifica el certificado por defecto y valida los encabezados (rechaza saltos de línea). Twig con `autoescape: 'html'` para todas las plantillas (versionadas en el repo, no editables desde el CMS) y una versión en texto plano. En producción el arranque rechaza DSN con `verify_peer=0` o sin cifrado. En desarrollo los correos van al sandbox de Mailtrap (credenciales solo en el `.env` local); no hay contenedor de correo. *Alternativa:* PHPMailer 7 — válido, pero mezcla transporte y mensaje en una clase, no tiene DSN ni plantillas, y Symfony Mailer es el que usa Laravel por debajo, así que el comportamiento de entrega no cambia. Se envía de forma síncrona tras guardar; los fallos se registran y no se propagan (spec `correo`).

### D6b. Cola de correo en base de datos + cron
Todo correo (automático o lanzado con `POST /emails/{type}`) se inserta en `email_outbox` y lo despacha `bin/console mail:send` (lote limitado por minuto, 3 intentos con reintentos a los 1 y 5 min, pausa `MAIL_SEND_INTERVAL_MS` (1 s) entre correos porque los servidores SMTP rechazan ráfagas (lo confirmó el sandbox de Mailtrap), bloqueo por fila con `FOR UPDATE SKIP LOCKED` en MySQL). En el hosting corre por cron cada minuto; en Docker, un servicio `cola` lo ejecuta en bucle. Los automáticos de acceso (enlace y recordatorio) se intentan enviar en el acto y, si fallan, quedan en la cola. *Alternativas:* worker permanente (no disponible en hosting compartido), envío síncrono masivo (bloquea la petición y no reintenta).

### D6c. Tipos de correo como código
Cada tipo es una clase pequeña en `src/Application/Mail/EmailType/` con su consulta de destinatarios (los filtros heredados de `MODULO_EMAIL_CMS.md` §1.2), su asunto y su plantilla Twig en `resources/emails/`. Añadir un correo = una clase + una plantilla + su alta en la lista de tipos.

**Origen de cada plantilla (decisión del 2026-10-23): se porta tal cual la versión que envía hoy producción**, sin rediseñar; solo cambia la sintaxis de variables (`{{ $name }}`/`{{$obj->name}}` → `{{ name }}`). `welcome` = `email_triggers` id 1, `team-notice` = id 2, `code` = id 3, `correction` = id 8 (del volcado); `instructions` = `email4.blade.php`, `book` = `email5.blade.php`, `important-notice` = `email6.blade.php`, `general-info` = `email7.blade.php` (de `angular-ob/app-ob/resources/views/mails/`). Las `plantilla_*.html` de la raíz de `angular-ob` no se usan. Los dos correos nuevos (`access-link`, `code-reminder`) reutilizan la cabecera, los colores y el pie de `code`, para que todos se vean iguales; el marco `_base` de 2027 hecho en el grupo 3 se reemplaza. *Alternativa descartada:* disparadores configurables en el CMS (el módulo heredado no se usa: los envíos se lanzan a mano).

### D7. Concurrencia de cupos
La inscripción a eventos se hace en una transacción con `SELECT … FOR UPDATE` sobre la fila del evento, se cuenta `event_attendees` y se inserta; el índice único `(id_event, id_attendee)` impide duplicados. En SQLite (pruebas) la transacción serializa igual.

### D7b. Números de participante sin colisiones
La asignación del siguiente número (al inscribirse y en `/attendees/codes`) se hace bajo un candado con nombre de MariaDB (`GET_LOCK('origami.attendees.code')`), con el índice único de `code` como respaldo y reintento. Se descartó `SELECT MAX(code) … FOR UPDATE`: con inscripciones simultáneas provocaba *deadlocks* (error 1213) por los bloqueos de rango; lo destapó la prueba de 10 inscripciones en paralelo contra MariaDB (2026-10-23).

### D8. Validación sin dependencias pesadas
Un `Validator` propio pequeño (requerido, correo, entero en rango, en lista, longitud, hora `HH:MM`) que devuelve los mensajes por campo para el 422. *Alternativa:* `illuminate/validation`, que arrastra traductor y contenedor de Laravel.

### D9. Imágenes
Se valida el tipo real con `finfo` y `getimagesize`, máximo 5 MB, se guarda como `taller-<id>-<aleatorio>.<ext>` en `storage/images/` (fuera de `public/`) y se sirve con `GET /images/{file}` con `Content-Type` fijo y `X-Content-Type-Options: nosniff`. La importación copia las imágenes y logos heredados de `app-ob/storage/app/public/`.

### D10. Turnos y ventana configurables
Lo que en `angular-ob` estaba escrito en el código pasa a `.env`: días del congreso (`EVENT_SATURDAY`, `EVENT_SUNDAY`, fechas concretas en vez de «cualquier sábado»), `TURN_START_TIME` (08:00), `TURN_END_TIME` (19:00), `TURN_GROUP_SIZE` (25), `TURN_MINUTES` (10), zona `America/Bogota`, y `TURN_BYPASS` para ensayar, que solo actúa si `APP_ENV` no es `production` (reemplaza al código `99099` y a `X-Test-Mode`). Un servicio `RegistrationTurns` con un reloj inyectable calcula grupo, hora de ingreso y mensajes, y lo usan `verificar-codigo` y `events/activos`; las pruebas fijan la hora como hacía `setEscenario()`.

### D11. Talleres en dos partes como una sola operación
La inscripción reúne primero el conjunto de eventos afectados (el elegido y sus partes relacionadas por `part`/`relation`), los bloquea con `FOR UPDATE`, valida cupo, duplicado, choque de horario y edad para todos, y solo entonces inserta y descuenta. En `angular-ob` la parte relacionada se saltaba en silencio si no tenía cupo; aquí se rechaza todo (spec `agenda`).

## Risks / Trade-offs

- [**El hosting hoy reporta PHP 8.4.27**] → el backend exige PHP 8.5: hay que activar 8.5 en el panel del hosting (CloudLinux suele permitir elegir versión) antes de desplegar; si no existe, la decisión de PHP 8.5 se revisa con el equipo.
- [Las plantillas de `code` y `correction` viven en `email_triggers` (ids 3 y 8) del volcado, no en archivos] → se exportan de ahí a Twig al portar las plantillas (tarea 4.3).
- [El volcado contiene datos personales y hashes] → vive fuera del repositorio (`../origamib_online.sql`) y nunca se versiona; las pruebas usan datos inventados.
- [PHP 8.5 en el hosting] → si el proveedor no lo ofrece, no se despliega; confirmar antes de la fase de despliegue. La imagen Docker fija `php:8.5-apache` y `config.platform.php = 8.5`.
- [Las rutas nuevas rompen el CMS y el sitio antiguos] → el corte es simultáneo: el backend nuevo se publica junto con `frontend/` y `cms/`; mientras tanto el antiguo sigue en `app-ob/` sin tocarlo.
- [Correo síncrono alarga la respuesta de inscripción] → aceptable para el volumen (cientos de inscripciones); el envío masivo de números es una acción explícita del CMS que reporta enviados/fallidos.
- [Faltan `app-ob/app` y las migraciones heredadas] → si aparecen, se revisa la spec contra la lógica real antes de la tarea 8.1; mientras tanto la importación falla de forma explícita ante columnas desconocidas.
- [`correction` y `important-notice` eran correos puntuales de 2026] → se portan con texto genérico; `correction` exige `ids` en lugar de la lista de correos escrita en el código.
- [El cron del hosting no corre] → `GET /health` reporta la antigüedad del envío pendiente más viejo para detectarlo.
- [Enlace mágico depende de que el correo llegue] → el número de participante sigue sirviendo como segunda vía de acceso.

## Migration Plan

1. Desarrollo y pruebas en Docker con datos sembrados.
2. **Antes de todo, mitigar en producción** la subida de PHP por `/upload-image` y el registro público (ver proposal). Obtener volcado de la base de producción (`mysqldump --default-character-set=utf8mb4`) y la carpeta de imágenes.
3. Cargar el volcado (incluidos `partners` y su tabla pivote; `email_triggers` y tablas de campañas se ignoran) en el MySQL de Docker y ejecutar `migrar` (la migración de transición renombra `assistants` → `attendees` y `workshops.id_assistant` → `id_attendee`, y elimina las tablas y columnas que ya no se usan) e `import:legacy` para lo que no sea renombrado directo; comparar conteos por tabla.
4. Desplegar en una subcarpeta nueva (p. ej. `/api`) con `APP_BASE_PATH`, sin tocar `app-ob/`.
5. Cambiar `apiUrl` en `frontend/` y `cms/` y publicar.
6. **Rollback:** volver a apuntar los frontends a `app-ob/` y restaurar el volcado previo; el backend nuevo no modifica la base antigua si se migra sobre una copia.

## Open Questions

- Lista definitiva de niveles (`level`) y públicos (`public`) de los talleres: hoy son letras (`P`, `A`…); se cargan como configuración y no cambian el diseño.
- Texto final y diseño de los correos con el sistema de diseño 2027 (base: `angular-ob/plantilla_*.html`).
