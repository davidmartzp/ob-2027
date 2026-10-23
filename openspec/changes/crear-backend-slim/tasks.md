# Tasks

## 1. Andamiaje y Docker

- [x] 1.1 Crear `backend/` con `composer.json` (PHP `^8.5`, `config.platform.php = 8.5`, autoload PSR-4 `App\\` → `src/`) y las dependencias de `proposal.md`; verificar que `composer install` termina sin conflictos dentro del contenedor
- [x] 1.2 Crear `docker/Dockerfile` (`php:8.5-apache`, `pdo_mysql`, `pdo_sqlite`, `fileinfo`, `mod_rewrite`) y `compose.yaml` (api :8080, db `mariadb:10.11` :3307 utf8mb4, correo Mailpit :8025, `cola` que ejecuta `mail:send` en bucle); verificar `docker compose up -d --wait` y `php -v` = 8.5 en el contenedor
- [x] 1.3 Crear `.env.example` (BD, `MAILER_DSN`, remitentes, `CORS_ORIGINS`, `APP_BASE_PATH`, `APP_DEBUG`, `APP_ENV`, vigencias de tokens, turno) y entradas en `.gitignore` (`backend/.env`, `backend/vendor/`, `backend/storage/`); verificar con `git status` que `.env` no aparece

## 2. Plataforma (spec `backend/plataforma`)

Nota: los grupos 1–3 se reorganizaron el 2026-10-23 a arquitectura hexagonal y nombres en inglés (design.md D2); las tareas describen el resultado actual.

- [x] 2.1 `config/bootstrap.php` (dotenv con variables obligatorias → PHP-DI → Capsule de Eloquent → Slim con ruta base) compartido por `public/index.php`, `bin/console` y `tests/Support/FunctionalTestCase.php`; prueba: falta `DB_HOST` → error que la nombra
- [x] 2.2 Middleware de errores JSON (`{"error": {code, message, fields?}}`, sin trazas con `APP_DEBUG=false`, log del detalle), excepciones de dominio en `src/Domain/Shared/Exception/` mapeadas en `Infrastructure/Http/ErrorHandler` y `Validator`; pruebas: 404 `not_found`, 422 con `fields`, 500 genérico sin traza
- [x] 2.3 Middleware CORS con orígenes de `CORS_ORIGINS` y respuesta a `OPTIONS`; pruebas de origen permitido y no permitido
- [x] 2.4 Módulo `Salud` (`GET /health` 200/503 con `cola_correo_minutos`); prueba funcional
- [x] 2.5 Ejecutor de migraciones por lotes (`migrate`, `rollback`, `seed`) en `bin/console`; prueba: migrate + rollback deja el esquema vacío
- [x] 2.6 Migraciones del esquema de `design.md` D4 (attendees, workshops, partners, workshop_partner, rooms, activities, events, events_attendees, email_outbox, users, access_tokens, access_links, intentos) compatibles con MySQL y SQLite; verificar migrando en ambos motores
- [x] 2.7 Migración de transición `assistants` → `attendees`, `workshops.id_assistant` → `id_attendee`, borrado de tablas y columnas sin uso (`workshops_menu`, `email_*`, `jobs`, `personal_access_tokens`, `schedule`/`day`/`room`/`isactivity`) (no hace nada en base vacía); prueba con una base SQLite sembrada con el esquema heredado: mismos ids y conteos
- [x] 2.8 `.htaccess` de `public/` y de la raíz (reescritura a `public/`, 404 para `.env`, `src/`, `vendor/`, `resources/`, `storage/`); verificar con `curl` contra Apache del contenedor sin raíz en `public/`

## 3. Autenticación (spec `backend/autenticacion`)

- [x] 3.1 Modelo `User` sobre `users`, comandos `user:create` y `user:password`; prueba: usuario creado por consola inicia sesión (casos de uso `CreateUser`, `ChangeUserPassword`)
- [x] 3.2 Servicio de tokens (32 bytes aleatorios, SHA-256, tipo cms|asistente, caducidad, revocación) (`Application/Auth/TokenService`) y middleware `Authenticate` + `Guards` por tipo; pruebas: sin token 401, caducado 401, token de asistente en ruta CMS 401, el token no está en claro en la base
- [x] 3.3 `POST /auth/login`, `POST /auth/logout`, `GET /auth/me`, con hash bcrypt heredado y rehash a Argon2id; pruebas: correcto, incorrecto (mismo mensaje), hash de Laravel válido, token revocado tras logout
- [x] 3.4 Puerto `RateLimiter` con adaptador `DbRateLimiter` (tabla `rate_limit_hits`) aplicado a login (5/15 min por correo e IP); prueba: el sexto intento devuelve 429
- [x] 3.5 `POST /auth/attendee-link` y `POST /auth/redeem-link` (un solo uso, 30 min, 3/hora por correo, misma respuesta 202); pruebas: correo inexistente sin envío, clave reutilizada 401

## 4. Correo (spec `backend/correo`)

Hecho: `Application/Mail` (EmailCatalog, EmailType, EmailVariables, SendEmail, ListEmailRecipients, GetEmailLog), `Infrastructure/Mail` (TransportFactory, OutboxProcessor, TwigMailQueue, HtmlToText), `bin/console mail:send`, `resources/emails/*` portadas tal cual. Envío local al sandbox de Mailtrap verificado con un correo de cada tipo (2026-10-23). `team-notice` y `code-reminder` se disparan desde los casos de uso del grupo 5.

- [x] 4.1 Adaptador de envío (`Infrastructure/Mail`) con Symfony Mailer (`MAILER_DSN`) y Twig (`autoescape: html`, versión texto); el arranque falla en producción con DSN sin TLS o `verify_peer=0`; pruebas con transporte en memoria: nombre con `<script>` escapado, salto de línea en nombre no añade encabezados
- [x] 4.2 Cola `email_outbox` (ya existe `MailQueue::queue()` con Twig y la plantilla `access-link`, adelantados en 3.5; falta el despacho): comando `mail:send` (lote por minuto, 3 intentos con reintentos a los 1 y 5 min, `unique_key` contra duplicados); pruebas: fallo SMTP → reintento, tres fallos → `failed`
- [x] 4.3 Tipos de correo (D6c) con las plantillas que envía hoy producción, sin rediseñar (origen por tipo en D6c: `email_triggers` 1, 2, 3 y 8 del volcado; `email4`–`email7.blade.php`), y `access-link`/`code-reminder` con el aspecto de `code`; prueba de que cada plantilla renderiza sin variables sin resolver, y destinatarios de `MODULO_EMAIL_CMS.md` §1.2: `welcome`, `team-notice`, `code`, `instructions`, `book`, `important-notice`, `general-info`, `correction`, `access-link`, `code-reminder`; prueba por tipo de que la consulta devuelve los destinatarios esperados
- [x] 4.4 `POST /emails/{type}` (con `ids` y `prueba`), `GET /emails/{type}/recipients` y `GET /emails/log`; pruebas: 80 confirmados de 100, doble lanzamiento → 0 encolados, reenvío de `code` con `ids`, prueba sin marcar a nadie, tipo desconocido 404
- [x] 4.5 Verificar en el sandbox de Mailtrap que, tras correr la cola, llegan los correos de cada tipo con su diseño

## 5. Attendees (spec `backend/attendees`)

Hecho (2026-10-23): dominio (`Attendee`, `CodePolicy`, `RegistrationTurns`, `TurnSettings`), casos de uso (`RegisterAttendee`, `VerifyAttendeeCode`, `RemindAttendeeCode`, `ManageAttendees`) y `AttendeeController`. Verificado contra MariaDB: 3 rondas de 10 inscripciones simultáneas con números únicos y seguidos, y los 165 asistentes vigentes del volcado real leídos y re-guardados por el CMS sin error.

- [x] 5.1 Completar la entidad `Domain/Attendee/Attendee` (todos los campos), `AttendeeModel` (casts booleanos, `pay_amount` texto), `AttendeeRepository` y datos de prueba (`Tests\Support\Attendees`, sin semilla para no crear asistentes falsos en producción); prueba de serialización con todos los campos del listado
- [x] 5.2 `POST /attendees` público con número al inscribirse para elegibles (sin duplicados concurrentes), con el JSON de `frontend/src/app/core/registro.ts` (`EnvioInscripcion`), validación, `othermedium` → `info`, estado 0, campo trampa `website`, límite 10/hora por IP y encola bienvenida y aviso al equipo; pruebas de cada escenario de la spec, incluido SMTP caído → 201
- [x] 5.3 Servicio `RegistrationTurns` con reloj inyectable (D10), `POST /attendees/verify-code` (`code` o `special_code`, token de asistente) y `POST /attendees/remind-code` (un correo por dirección con todos sus participantes, 202 siempre, 3/hora); pruebas con los escenarios de turno de la spec (grupo 3 a las 08:05 → 08:20, día no elegido, después de las 19:00, grupo fuera de horario), `99099` sin efecto, `TURN_BYPASS` sin efecto en producción, la respuesta del recordatorio no contiene números
- [x] 5.4 CMS: `GET /attendees` (por `paydate`), `GET /attendees/{id}`, `PUT /attendees/{id}` (incl. `code`, `special_code`, `staff`, `status`), `DELETE /attendees/{id}` (estado 5); pruebas: eliminados no se listan, 404 en inexistente, `code` repetido 409
- [x] 5.5 CMS: `POST /attendees/confirm`, `POST /attendees/codes` (continúa desde el máximo) y `POST /attendees/codes/regenerate` (con confirmación); pruebas: `confirmed: 2` con mezcla de estados, tardíos 121/122 por `paydate`, regenerar 3/1/2, excluidos por edad e `Invitado(a)`, sin confirmación 422

## 6. Talleres (spec `backend/talleres`)

Hecho (2026-10-23): `Domain/Workshop`, `Application/Workshop` (InstructorWorkshops, EditWorkshop, ManageWorkshops, WorkshopView), puerto `ImageStorage` con `Infrastructure/Storage/LocalImageStorage` y `ImageController`. Adelantado del grupo 7: entidad `Partner` y su lectura por taller (`PartnerRepository::activeByWorkshop`) para el listado público. Verificado con Apache: subida multipart real, imagen con PHP y archivo de 8 MB rechazados.

- [x] 6.1 Modelo `Workshop` (`duration`, `tickets`, `part`, `relation`) y servicio con estados 0/1/2 y niveles/públicos configurables; prueba de que un retirado no se lista ni se programa
- [x] 6.2 `POST|GET /attendees/{id}/workshops` con token del asistente; pruebas: propuesta en estado 1, 403 con token de otro asistente
- [x] 6.3 `PUT /workshops/{id}`, `DELETE /workshops/{id}` (autor o CMS) y `GET /cms/workshops`, `PATCH /workshops/{id}` (`status`, `tickets`, `part`, `relation`); pruebas de permisos, cupo y relación inválida 422
- [x] 6.4 `POST /workshops/{id}/image` y `GET /images/{file}` (D9); pruebas: `.php` y `.jpg` con código PHP 422, 8 MB 422, sin token 401, imagen válida reemplaza la anterior
- [x] 6.5 `GET /workshops` público aleatorio, solo activos, con socios activos y sin datos de contacto; prueba de los campos devueltos (requiere grupo 7)

## 7. Partners (spec `backend/partners`)

Hecho (2026-10-23): `Application/Partner` (ManagePartners, WorkshopPartners, PartnerView), `EloquentPartnerRepository`, `PartnerController`. Verificado en MariaDB: crear socio, subir logo, asociarlo a un taller y verlo en `GET /workshops`.

- [x] 7.1 Modelo `Partner` con `whatsapp_url`, `GET /partners` y CRUD del CMS; pruebas: URL de WhatsApp codificada, 422 con `+57 300-123`, desactivado no aparece en listados públicos
- [x] 7.2 `POST /partners/{id}/logo` reutilizando la validación de imágenes de 6.4; prueba: SVG 422
- [x] 7.3 `GET|PUT /workshops/{id}/partners`; pruebas: reemplazo [1,2] → [2,3], sin token 401, id inexistente 422

## 8. Agenda (spec `backend/agenda`)

Hecho (2026-10-23): `Domain/Schedule` (Event, Day, Room, Activity, EnrolmentWindow, ScheduleRepository), `Application/Schedule` (ManageSchedule, PublicSchedule, WorkshopEnrolment, ScheduleView), puerto `TransactionManager`, `DbScheduleRepository`, `ScheduleController`, semillas `01_rooms` y `02_activities` con los datos de producción. Verificado en MariaDB: 10 inscripciones simultáneas al último cupo (entra 1), 10 simultáneas cruzadas a un taller de dos partes con 3 cupos (entran 3 en ambas partes, 0 errores) y la agenda del volcado real (137 eventos, 62 talleres programados).

- [x] 8.1 `GET /rooms` y `GET /activities` (CMS) y semillas de ejemplo; prueba con token
- [x] 8.2 `POST /events` (XOR taller/actividad, solape por salón y día), `GET /events`, `DELETE /events/{id}` devolviendo cupos; pruebas: 422 con ambos, 409 por solape, borrar con 5 inscritos deja 8 cupos
- [x] 8.3 `GET /events/active` con ventana (servicio `RegistrationTurns`) y la forma de la spec; pruebas con reloj fijo: sábado 07:59 → 403 `logout`, domingo 08:11 solo `sunday`, `TURN_BYPASS` fuera de producción devuelve ambos días
- [x] 8.4 `POST|GET /attendees/{id}/events` y `DELETE /attendees/{id}/events/{idEvento}` con transacción y bloqueo, partes 1/2 (D11) y menores en talleres `A`; pruebas: último cupo disputado, duplicado 409, choque 409 con nombre, menor 422, parte 2 sin cupo rechaza todo, cancelar una parte cancela ambas
- [x] 8.5 `GET /events/schedule` (público), `GET /events/workshops` y `GET /events/{id}/attendees` (CMS); pruebas: lista vacía 200, orden por número 3, 7, 12

## 9. Importación y documentación

- [ ] 9.1 Comando `import:legacy` (lee una base heredada configurada aparte, mapea columnas por nombre, falla listando columnas desconocidas, copia imágenes y logos de `app-ob/storage/app/public/images`); prueba con un volcado de ejemplo con la forma de `origamib_online.sql` (datos inventados) y verificación manual contra una copia del volcado real cargada en el MariaDB de Docker: mismos conteos por tabla
- [ ] 9.2 Actualizar `CLAUDE.md` (sección Backend: comandos, módulos, rutas, trampas) y crear `backend/README.md` con el mapa de rutas de D3; verificar que los comandos documentados funcionan tal cual
- [ ] 9.3 Comando `bin/package` que, dentro de Docker, instala dependencias sin las de desarrollo (`composer install --no-dev --optimize-autoloader`) y arma `dist/backend-AAAAMMDD.zip` solo con `public/`, `src/`, `config/`, `database/`, `resources/`, `bin/console`, `vendor/`, `.htaccess`, `.env.example` y `storage/` vacío (sin `tests/`, `docker/`, `compose.yaml` ni `.env`); verificar descomprimiendo el zip en un Apache con PHP 8.5 sin Docker de por medio (contenedor `php:8.5-apache` sin Composer) que `/salud` responde y `/.env` no

## 10. Verificación integral

- [ ] 10.1 Suite completa `docker compose exec api vendor/bin/phpunit` en verde y recorrido manual con `curl`: login CMS → crear salón, actividad y evento → crear socio y asociarlo a un taller → inscripción pública → correr la cola → confirmar → generar códigos → prueba y envío de `code` → verificar código → inscribirse a evento → lista del evento
- [ ] 10.2 `openspec validate crear-backend-slim --strict` sin errores

## Workflow follow-up

- Confirmar con el hosting la disponibilidad de PHP 8.5 antes de desplegar.
- Activar PHP 8.5 en el hosting (hoy reporta 8.4.27).
- Despliegue (sin Docker): subir el zip de 9.3 por FTP/administrador de archivos, crear `.env` real en el servidor (`APP_ENV=production`, `APP_DEBUG=false`, `MAILER_DSN` del SMTP del hosting), ejecutar `php bin/console migrate` desde la terminal o el cron del panel, y configurar el cron de `php bin/console mail:send` cada minuto.
- Cambios aparte: conectar `frontend/` a `POST /attendees` y crear el proyecto `cms/`.
- Archivar el cambio cuando se apruebe la revisión.
