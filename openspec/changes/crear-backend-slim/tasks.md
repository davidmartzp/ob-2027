# Tasks

## 1. Andamiaje y Docker

- [x] 1.1 Crear `backend/` con `composer.json` (PHP `^8.5`, `config.platform.php = 8.5`, autoload PSR-4 `App\\` → `src/`) y las dependencias de `proposal.md`; verificar que `composer install` termina sin conflictos dentro del contenedor
- [x] 1.2 Crear `docker/Dockerfile` (`php:8.5-apache`, `pdo_mysql`, `pdo_sqlite`, `fileinfo`, `mod_rewrite`) y `compose.yaml` (api :8080, db `mysql:8.4` :3307 utf8mb4, correo Mailpit :8025, `cola` que ejecuta `correo:procesar` en bucle); verificar `docker compose up -d --wait` y `php -v` = 8.5 en el contenedor
- [x] 1.3 Crear `.env.example` (BD, `MAILER_DSN`, remitentes, `CORS_ORIGENES`, `APP_BASE_PATH`, `APP_DEBUG`, `APP_ENV`, vigencias de tokens, turno) y entradas en `.gitignore` (`backend/.env`, `backend/vendor/`, `backend/almacen/`); verificar con `git status` que `.env` no aparece

## 2. Plataforma (spec `backend/plataforma`)

- [ ] 2.1 `config/arranque.php` (dotenv con variables obligatorias → PHP-DI → Capsule de Eloquent → Slim con ruta base) compartido por `public/index.php`, `bin/consola` y `tests/CasoFuncional.php`; prueba: falta `DB_HOST` → error que la nombra
- [ ] 2.2 Middleware de errores JSON (`{"error": {codigo, mensaje, campos?}}`, sin trazas con `APP_DEBUG=false`, log del detalle), errores de dominio en `src/Compartido/Errores/` y `Validador`; pruebas: 404 `no_encontrado`, 422 con `campos`, 500 genérico sin traza
- [ ] 2.3 Middleware CORS con orígenes de `CORS_ORIGENES` y respuesta a `OPTIONS`; pruebas de origen permitido y no permitido
- [ ] 2.4 Módulo `Salud` (`GET /salud` 200/503 con `cola_correo_minutos`); prueba funcional
- [ ] 2.5 Ejecutor de migraciones por lotes (`migrar`, `revertir`, `sembrar`) en `bin/consola`; prueba: migrar + revertir deja el esquema vacío
- [ ] 2.6 Migraciones del esquema de `design.md` D4 (attendees, workshops, partners, workshop_partner, rooms, activities, events, events_attendees, email_envios, users, tokens_acceso, enlaces_acceso, intentos) compatibles con MySQL y SQLite; verificar migrando en ambos motores
- [ ] 2.7 Migración de transición `assistants` → `attendees`, `workshops.id_assistant` → `id_attendee`, borrado de tablas y columnas sin uso (`workshops_menu`, `email_*`, `jobs`, `personal_access_tokens`, `schedule`/`day`/`room`/`isactivity`) (no hace nada en base vacía); prueba con una base SQLite sembrada con el esquema heredado: mismos ids y conteos
- [ ] 2.8 `.htaccess` de `public/` y de la raíz (reescritura a `public/`, 404 para `.env`, `src/`, `vendor/`, `plantillas/`, `almacen/`); verificar con `curl` contra Apache del contenedor sin raíz en `public/`

## 3. Autenticación (spec `backend/autenticacion`)

- [ ] 3.1 Modelo `User` sobre `users`, comandos `usuario:crear` y `usuario:clave`; prueba: usuario creado por consola inicia sesión
- [ ] 3.2 Servicio de tokens (32 bytes aleatorios, SHA-256, tipo cms|asistente, caducidad, revocación) y middleware `Autenticacion` por tipo; pruebas: sin token 401, caducado 401, token de asistente en ruta CMS 401, el token no está en claro en la base
- [ ] 3.3 `POST /auth/login`, `POST /auth/logout`, `GET /auth/yo`, con hash bcrypt heredado y rehash a Argon2id; pruebas: correcto, incorrecto (mismo mensaje), hash de Laravel válido, token revocado tras logout
- [ ] 3.4 Middleware `LimiteIntentos` (tabla `intentos`) aplicado a login (5/15 min por correo e IP); prueba: el sexto intento devuelve 429
- [ ] 3.5 `POST /auth/enlace-asistente` y `POST /auth/canjear-enlace` (un solo uso, 30 min, 3/hora por correo, misma respuesta 202); pruebas: correo inexistente sin envío, clave reutilizada 401

## 4. Correo (spec `backend/correo`)

- [ ] 4.1 Servicio `Correo` con Symfony Mailer (`MAILER_DSN`) y Twig (`autoescape: html`, versión texto); arranque que falla en producción con DSN sin TLS o `verify_peer=0`; pruebas con transporte en memoria: nombre con `<script>` escapado, salto de línea en nombre no añade encabezados
- [ ] 4.2 Cola `email_envios` y comando `correo:procesar` (lote por minuto, reintentos 1/5/30 min, `clave_unica` contra duplicados); pruebas: fallo SMTP → reintento, tres fallos → `failed`
- [ ] 4.3 Tipos de correo (D6c) con plantillas portadas de `angular-ob/plantilla_*.html` y destinatarios de `MODULO_EMAIL_CMS.md` §1.2: `bienvenida`, `aviso-equipo`, `numero`, `instrucciones`, `libro`, `aviso-importante`, `informacion-general`, `correccion`, `enlace-acceso`, `recordatorio-numero`; prueba por tipo de que la consulta devuelve los destinatarios esperados
- [ ] 4.4 `POST /correos/{tipo}` (con `ids` y `prueba`), `GET /correos/{tipo}/destinatarios` y `GET /correos/registro`; pruebas: 80 confirmados de 100, doble lanzamiento → 0 encolados, reenvío de `numero` con `ids`, prueba sin marcar a nadie, tipo desconocido 404
- [ ] 4.5 Verificar en Mailpit (`http://localhost:8025`) que una inscripción en Docker genera bienvenida y aviso al equipo tras correr la cola

## 5. Attendees (spec `backend/attendees`)

- [ ] 5.1 Modelo `Attendee` (casts booleanos, `pay_amount` texto) y semilla de ejemplo; prueba de serialización con todos los campos del listado
- [ ] 5.2 `POST /attendees` público con número al inscribirse para elegibles (sin duplicados concurrentes), con el JSON de `frontend/src/app/core/registro.ts` (`EnvioInscripcion`), validación, `othermedium` → `info`, estado 0, campo trampa `website`, límite 10/hora por IP y encola bienvenida y aviso al equipo; pruebas de cada escenario de la spec, incluido SMTP caído → 201
- [ ] 5.3 Servicio `Turnos` con reloj inyectable (D10), `POST /attendees/verificar-codigo` (`code` o `special_code`, token de asistente) y `POST /attendees/recordar-codigo` (un correo por dirección con todos sus participantes, 202 siempre, 3/hora); pruebas con los escenarios de turno de la spec (grupo 3 a las 08:05 → 08:20, día no elegido, después de las 19:00, grupo fuera de horario), `99099` sin efecto, `TURNO_IGNORAR` sin efecto en producción, la respuesta del recordatorio no contiene números
- [ ] 5.4 CMS: `GET /attendees` (por `paydate`), `GET /attendees/{id}`, `PUT /attendees/{id}` (incl. `code`, `special_code`, `staff`, `status`), `DELETE /attendees/{id}` (estado 5); pruebas: eliminados no se listan, 404 en inexistente, `code` repetido 409
- [ ] 5.5 CMS: `POST /attendees/confirmar`, `POST /attendees/codigos` (continúa desde el máximo) y `POST /attendees/codigos/regenerar` (con confirmación); pruebas: `confirmados: 2` con mezcla de estados, tardíos 121/122 por `paydate`, regenerar 3/1/2, excluidos por edad e `Invitado(a)`, sin confirmación 422

## 6. Talleres (spec `backend/talleres`)

- [ ] 6.1 Modelo `Workshop` (`duration`, `tickets`, `part`, `relation`) y servicio con estados 0/1/2 y niveles/públicos configurables; prueba de que un retirado no se lista ni se programa
- [ ] 6.2 `POST|GET /attendees/{id}/workshops` con token del asistente; pruebas: propuesta en estado 1, 403 con token de otro asistente
- [ ] 6.3 `PUT /workshops/{id}`, `DELETE /workshops/{id}` (autor o CMS) y `GET /cms/workshops`, `PATCH /workshops/{id}` (`status`, `tickets`, `part`, `relation`); pruebas de permisos, cupo y relación inválida 422
- [ ] 6.4 `POST /workshops/{id}/imagen` y `GET /imagenes/{archivo}` (D9); pruebas: `.php` y `.jpg` con código PHP 422, 8 MB 422, sin token 401, imagen válida reemplaza la anterior
- [ ] 6.5 `GET /workshops` público aleatorio, solo activos, con socios activos y sin datos de contacto; prueba de los campos devueltos (requiere grupo 7)

## 7. Partners (spec `backend/partners`)

- [ ] 7.1 Modelo `Partner` con `whatsapp_url`, `GET /partners` y CRUD del CMS; pruebas: URL de WhatsApp codificada, 422 con `+57 300-123`, desactivado no aparece en listados públicos
- [ ] 7.2 `POST /partners/{id}/logo` reutilizando la validación de imágenes de 6.4; prueba: SVG 422
- [ ] 7.3 `GET|PUT /workshops/{id}/partners`; pruebas: reemplazo [1,2] → [2,3], sin token 401, id inexistente 422

## 8. Agenda (spec `backend/agenda`)

- [ ] 8.1 `GET /rooms` y `GET /activities` (CMS) y semillas de ejemplo; prueba con token
- [ ] 8.2 `POST /events` (XOR taller/actividad, solape por salón y día), `GET /events`, `DELETE /events/{id}` devolviendo cupos; pruebas: 422 con ambos, 409 por solape, borrar con 5 inscritos deja 8 cupos
- [ ] 8.3 `GET /events/activos` con ventana (servicio `Turnos`) y la forma de la spec; pruebas con reloj fijo: sábado 07:59 → 403 `logout`, domingo 08:11 solo `sunday`, `TURNO_IGNORAR` fuera de producción devuelve ambos días
- [ ] 8.4 `POST|GET /attendees/{id}/events` y `DELETE /attendees/{id}/events/{idEvento}` con transacción y bloqueo, partes 1/2 (D11) y menores en talleres `A`; pruebas: último cupo disputado, duplicado 409, choque 409 con nombre, menor 422, parte 2 sin cupo rechaza todo, cancelar una parte cancela ambas
- [ ] 8.5 `GET /events/programacion` (público), `GET /events/talleres` y `GET /events/{id}/attendees` (CMS); pruebas: lista vacía 200, orden por número 3, 7, 12

## 9. Importación y documentación

- [ ] 9.1 Comando `importar-legado` (lee una base heredada configurada aparte, mapea columnas por nombre, falla listando columnas desconocidas, copia imágenes y logos de `app-ob/storage/app/public/images`); prueba con un volcado SQLite de ejemplo
- [ ] 9.2 Actualizar `CLAUDE.md` (sección Backend: comandos, módulos, rutas, trampas) y crear `backend/README.md` con el mapa de rutas de D3; verificar que los comandos documentados funcionan tal cual

## 10. Verificación integral

- [ ] 10.1 Suite completa `docker compose exec api vendor/bin/phpunit` en verde y recorrido manual con `curl`: login CMS → crear salón, actividad y evento → crear socio y asociarlo a un taller → inscripción pública → correr la cola → confirmar → generar códigos → prueba y envío de `numero` → verificar código → inscribirse a evento → lista del evento
- [ ] 10.2 `openspec validate crear-backend-slim --strict` sin errores

## Workflow follow-up

- Confirmar con el hosting la disponibilidad de PHP 8.5 antes de desplegar.
- Conseguir un volcado real de la base de producción del congreso para validar el esquema y `importar-legado`.
- Configurar en el hosting el cron de `bin/consola correo:procesar` cada minuto.
- Cambios aparte: conectar `frontend/` a `POST /attendees` y crear el proyecto `cms/`.
- Archivar el cambio cuando se apruebe la revisión.
