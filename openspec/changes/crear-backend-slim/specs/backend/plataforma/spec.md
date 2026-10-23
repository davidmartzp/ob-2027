# Spec Delta

## Purpose

Define cómo se ejecuta, configura, versiona y despliega el backend del congreso, y la forma común de sus respuestas, para que sea reproducible en Docker y publicable en un hosting PHP.

## ADDED Requirements

### Requirement: Entorno de ejecución PHP 8.5
El backend SHALL ejecutarse sobre PHP 8.5 y sus dependencias MUST resolverse para esa versión. El entorno de desarrollo MUST levantarse completo (API y base de datos MariaDB 10.11, el mismo motor del hosting) con un solo comando de Docker Compose, sin PHP ni Composer instalados en la máquina.

#### Scenario: Arranque en limpio
- **WHEN** se copia `.env.example` a `.env` y se ejecuta `docker compose up -d --wait`
- **THEN** la API responde en `http://localhost:8080` y la base de datos acepta conexiones

#### Scenario: Versión de PHP
- **WHEN** se consulta la versión de PHP dentro del contenedor de la API
- **THEN** la versión mayor y menor es 8.5

### Requirement: Configuración solo por entorno
Toda credencial y parámetro dependiente del entorno (base de datos, correo, orígenes permitidos, ruta base, modo de depuración, secretos) SHALL leerse de variables de entorno o del archivo `.env`, que MUST quedar fuera del control de versiones. El repositorio SHALL incluir un `.env.example` sin secretos reales.

#### Scenario: Falta una variable obligatoria
- **WHEN** la API arranca sin la variable de conexión a la base de datos
- **THEN** el arranque falla con un mensaje que nombra la variable que falta

### Requirement: Respuestas JSON uniformes
Todas las respuestas de la API SHALL ser JSON UTF-8. Los errores MUST tener la forma `{"error": {"code": <texto>, "message": <texto>}}` con el código HTTP correspondiente (400, 401, 403, 404, 409, 422, 500); los errores de validación MUST incluir `fields` con el mensaje de cada campo inválido.

#### Scenario: Ruta inexistente
- **WHEN** se pide `GET /no-existe`
- **THEN** la respuesta es 404 con `error.code` igual a `not_found`

#### Scenario: Validación fallida
- **WHEN** se envía un cuerpo con campos obligatorios vacíos
- **THEN** la respuesta es 422 y `error.fields` lista cada campo con su mensaje

### Requirement: Sin detalles internos en producción
Con el modo de depuración desactivado, las respuestas de error 500 MUST NOT incluir trazas, rutas de archivos, consultas SQL ni mensajes de excepciones internas; el detalle SHALL registrarse en el log del servidor.

#### Scenario: Excepción inesperada en producción
- **WHEN** ocurre una excepción no controlada con `APP_DEBUG=false`
- **THEN** la respuesta es 500 con un mensaje genérico y el detalle queda en el log

### Requirement: CORS restringido
La API SHALL responder a peticiones de navegador solo desde los orígenes configurados (sitio público y CMS) e incluir los encabezados CORS necesarios, incluidas las peticiones de verificación previa `OPTIONS`.

#### Scenario: Origen permitido
- **WHEN** llega una petición con `Origin` igual a un origen configurado
- **THEN** la respuesta incluye `Access-Control-Allow-Origin` con ese origen

#### Scenario: Origen no permitido
- **WHEN** llega una petición con un `Origin` no configurado
- **THEN** la respuesta no incluye `Access-Control-Allow-Origin`

### Requirement: Salud del servicio
La API SHALL exponer `GET /health`, público, que responda 200 con `{"status": "ok", "mail_queue_minutes": <antigüedad del envío pendiente más viejo>}` cuando la base de datos responde y 503 cuando no.

#### Scenario: Servicio sano
- **WHEN** se pide `GET /health` con la base de datos disponible
- **THEN** la respuesta es 200 con `status` igual a `ok`

### Requirement: Esquema versionado con migraciones
El esquema de la base de datos SHALL crearse y modificarse solo mediante migraciones ordenadas, aplicables y reversibles por lotes desde la consola, compatibles con MariaDB y con SQLite (usado en las pruebas).

#### Scenario: Migrar y revertir
- **WHEN** se ejecuta la migración y luego la reversión del último lote
- **THEN** el esquema vuelve exactamente al estado anterior al lote

### Requirement: Transición desde la base heredada
Una migración de transición SHALL convertir una base de `angular-ob` al esquema nuevo conservando todos los registros: la tabla `assistants` pasa a `attendees` y toda columna `id_assistant` pasa a `id_attendee`. Sobre una base vacía MUST no hacer nada.

#### Scenario: Base heredada con asistentes
- **WHEN** se migra una base que tiene 300 filas en `assistants`
- **THEN** existen 300 filas en `attendees` con los mismos `id` y datos, y no existe la tabla `assistants`

#### Scenario: Base nueva
- **WHEN** se migra una base vacía
- **THEN** se crea el esquema nuevo sin errores

### Requirement: Despliegue en hosting PHP
El backend SHALL poder publicarse en un hosting Apache con PHP 8.5 sin Docker: con la raíz web en `public/` o, si no se puede, mediante reescritura que MUST impedir el acceso web a `.env`, código fuente, dependencias y plantillas. La API SHALL funcionar bajo una ruta base configurable (p. ej. `/api`).

#### Scenario: Archivo sensible
- **WHEN** se pide por web `/.env` en un despliegue sin raíz en `public/`
- **THEN** la respuesta es 404 o 403 y no se entrega su contenido
