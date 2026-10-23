# Proposal

## Why

La API del congreso (`app-ob/` dentro de `angular-ob`) cumple su función, pero corre sobre Laravel 9 en PHP 8.2, arrastra todo el framework y deja abiertos sin autenticación servicios críticos: subir archivos como «imagen de taller» (se guardan con la extensión que manda el cliente en una carpeta que Apache sirve, así que se puede subir y ejecutar PHP), registrar usuarios del CMS (que luego pueden lanzar `/admin/migrate-fresh`), listar y editar asistentes, generar y borrar números, envíos masivos de correo, consultar el número de cualquiera por su correo e inscribir a cualquier asistente a eventos sabiendo solo su id. Además tiene un código maestro (`99099`) y una cabecera (`X-Test-Mode`) que saltan los turnos. El sitio nuevo (`frontend/`) y el futuro `cms/` necesitan un backend propio, ligero, en PHP 8.5 y reproducible con Docker antes de abrir inscripciones para la edición 2027.

## What Changes

- Nuevo proyecto `backend/` en este repositorio: Slim 4 + PHP-DI + Eloquent (`illuminate/database`, sin el framework Laravel), PHP 8.5, Apache, MySQL 8.4, todo levantable con `docker compose`.
- Se replica el catálogo y las reglas de negocio de `angular-ob/app-ob` (rutas y controladores `Api/V1`): inscripción y gestión de asistentes, números de participante, propuestas de talleres e imágenes, salones, actividades, programación de eventos e inscripción a ellos, listas por evento, socios (partners) por taller, envíos de correo lanzados desde el CMS y login/logout del CMS.
- **BREAKING** Renombrado de dominio: `Assistant`/`assistants` pasa a `Attendee`/`attendees` (modelo, tabla, columnas `id_assistant`/`assistant_id` → `id_attendee`/`attendee_id`, rutas). Una migración de transición conserva los datos existentes.
- **BREAKING** Rutas REST coherentes en lugar de las heredadas (`/assistants`, `/confirmManyAttendees`, `/getWorkshops`, `/sendInstructions`…). El mapa ruta antigua → ruta nueva queda en `design.md`. El frontend nuevo y el CMS nuevo consumen solo las rutas nuevas.
- **BREAKING** Los servicios de administración exigen token del CMS y los de un asistente sobre sus propios datos exigen token de asistente; solo quedan públicos los que usa el sitio sin sesión.
- Los envíos masivos (`sendNumbers`, `sendInstructions`, `sendBook`, `sendWhatsapp`, `sendGeneralInfo`, `sendCorrection`, `resendWelcomeEmail`) se unifican en `POST /emails/{type}`, que se sigue lanzando a mano, con destinatarios fijos por tipo, prueba previa, conteo previo y una cola con reintentos que evita duplicados.
- Correo con Symfony Mailer (`symfony/mailer`) configurado solo por `MAILER_DSN` (TLS), con plantillas Twig versionadas en el repositorio.
- **Se eliminan** sin reemplazo: el módulo de disparadores de correo del CMS (`/email-triggers`, `/email-trigger-events`, tabla `email_triggers`), que no se usa; `/register` (alta pública de usuarios del CMS), `/clearConfigCache`, `/clearAllCache` y todo `/admin/*` (migrar, `migrate-fresh`, sembrar y cachés por HTTP). Pasan a la consola del servidor.
- La lógica atada a ediciones pasadas (turnos y ventanas horarias escritos en el código, `id >= 37`, `id > 90`, listas de correos en `sendCorrection`) pasa a configuración o a parámetros explícitos (`ids`).

## Capabilities

### New Capabilities
- `backend/plataforma`: ejecución del backend (PHP 8.5, Docker, configuración por `.env`, respuestas y errores JSON, CORS, salud, migraciones y despliegue en hosting PHP).
- `backend/autenticacion`: login y logout del CMS, tokens del CMS y de asistente, enlace de acceso por correo.
- `backend/attendees`: inscripción pública, verificación y recuperación del número de participante y gestión de asistentes en el CMS (listar, editar, baja lógica, confirmación masiva, asignación y reinicio de números).
- `backend/talleres`: propuestas de talleres de los asistentes, edición y retiro, imagen del taller y listado público.
- `backend/partners`: socios/patrocinadores con logo y contacto por WhatsApp, y su asociación a talleres.
- `backend/agenda`: salones, actividades, eventos programados con cupo e inscripción de asistentes a eventos con control de cupo y de choques de horario.
- `backend/correo`: transporte seguro, correos automáticos (inscripción, enlace de acceso, recordatorio del número) y envíos por tipo lanzados desde el CMS, con cola y registro.

### Modified Capabilities
- Ninguna (no hay specs previas en `openspec/specs/`).

## Impact

- Código nuevo: `backend/` completo (sin cambios en `frontend/` ni `cms/` en este cambio). El frontend tendrá que cambiar `POST {apiUrl}/assistants` por `POST {apiUrl}/attendees` cuando se conecte (cambio aparte).
- Origen de referencia: `../angular-ob/app-ob` — `routes/api.php`, `app/Http/Controllers/Api/V1/*`, `app/Models/*`, `app/Mail/*`, `resources/views/mails/*`, `database/migrations/*`; plantillas `../angular-ob/plantilla_*.html`.
- Datos: la base de producción conserva usuarios (hash bcrypt compatible con `password_verify`) y registros; la migración de transición renombra tablas y columnas.
- Dependencias: `slim/slim`, `slim/psr7`, `php-di/php-di`, `illuminate/database`, `vlucas/phpdotenv`, `symfony/mailer`, `twig/twig`, `phpunit/phpunit`.
- Hosting: debe ofrecer PHP 8.5 y una tarea programada (cron) cada minuto para la cola de correo.

### Fuera de alcance
- Proyecto `cms/` y cambios en `frontend/`.
- Pagos en línea, tienda, generación de certificados en PDF y envíos por WhatsApp (el «WhatsApp» heredado era un correo informativo).
- Ejecutar la importación de producción (se construye el comando y se documenta; correrlo es una tarea operativa aparte).
