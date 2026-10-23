# Spec Delta

## Purpose

Controla quién puede usar los servicios de administración del congreso: inicio y cierre de sesión del personal del CMS y protección por token de las rutas privadas.

## ADDED Requirements

### Requirement: Inicio de sesión del CMS
`POST /auth/login` con `{"email", "password"}` SHALL validar las credenciales contra los usuarios del CMS y, si son correctas, responder 200 con `{"access_token": <texto>, "token_type": "Bearer", "expires_at": <ISO 8601>, "user": {"id", "name", "email"}}`. Las contraseñas existentes con hash bcrypt de la base heredada MUST seguir siendo válidas.

#### Scenario: Credenciales correctas
- **WHEN** un usuario del CMS envía su correo y contraseña correctos
- **THEN** la respuesta es 200 con un `access_token` y los datos del usuario sin la contraseña

#### Scenario: Credenciales incorrectas
- **WHEN** se envía una contraseña incorrecta o un correo inexistente
- **THEN** la respuesta es 401 con el mismo mensaje genérico en ambos casos

#### Scenario: Usuario migrado de la base heredada
- **WHEN** un usuario cuyo hash se creó con Laravel inicia sesión con su contraseña de siempre
- **THEN** el inicio de sesión es correcto

### Requirement: Límite de intentos de inicio de sesión
El backend SHALL rechazar con 429 los intentos de inicio de sesión que superen 5 fallos en 15 minutos para un mismo correo o una misma IP, hasta que pase la ventana.

#### Scenario: Fuerza bruta
- **WHEN** una IP falla el inicio de sesión 5 veces seguidas y vuelve a intentarlo
- **THEN** la respuesta es 429 aunque la contraseña sea correcta

### Requirement: Tokens de acceso
Cada token SHALL ser aleatorio criptográficamente (al menos 256 bits), guardarse en la base solo como hash, caducar a las 12 horas configurables y quedar revocado al cerrar sesión. Las rutas privadas MUST exigir `Authorization: Bearer <token>` válido y vigente.

#### Scenario: Ruta privada sin token
- **WHEN** se pide `GET /attendees` sin encabezado `Authorization`
- **THEN** la respuesta es 401

#### Scenario: Token caducado
- **WHEN** se usa un token emitido hace más de su vigencia
- **THEN** la respuesta es 401

#### Scenario: Token en la base
- **WHEN** se inspecciona la tabla de tokens tras un inicio de sesión
- **THEN** no aparece el token en claro

### Requirement: Cierre de sesión
`POST /auth/logout` con un token válido SHALL revocarlo y responder 204; usos posteriores de ese token MUST responder 401.

#### Scenario: Token tras cerrar sesión
- **WHEN** se cierra sesión y se repite una petición privada con el mismo token
- **THEN** la respuesta es 401

### Requirement: Usuario actual
`GET /auth/yo` con token válido SHALL responder los datos del usuario autenticado (`id`, `name`, `email`).

#### Scenario: Consultar sesión
- **WHEN** el CMS pide `GET /auth/yo` con un token vigente
- **THEN** la respuesta es 200 con los datos de ese usuario

### Requirement: Alta de usuarios por consola
Los usuarios del CMS SHALL crearse o cambiar su contraseña solo desde la consola del backend; la API MUST NOT exponer registro público de usuarios.

#### Scenario: Crear usuario
- **WHEN** se ejecuta el comando de consola para crear un usuario con correo y contraseña
- **THEN** ese usuario puede iniciar sesión en el CMS

### Requirement: Acceso de asistentes
Los servicios que un asistente usa sobre sus propios datos (sus talleres propuestos y sus eventos elegidos) SHALL exigir un token de asistente, distinto del token del CMS, vigente 2 horas y válido solo para ese asistente. Se obtiene al verificar correo y número de participante, o con el enlace de acceso enviado por correo.

#### Scenario: Token de otro asistente
- **WHEN** un asistente con token válido intenta modificar los talleres de otro asistente
- **THEN** la respuesta es 403

#### Scenario: Token de asistente en ruta del CMS
- **WHEN** se usa un token de asistente en `GET /attendees`
- **THEN** la respuesta es 401

### Requirement: Enlace de acceso por correo
`POST /auth/enlace-asistente` (público) con `{"email"}` SHALL enviar, si existe un asistente no eliminado con ese correo, un enlace de un solo uso válido 30 minutos; `POST /auth/canjear-enlace` con su clave SHALL devolver un token de asistente. La respuesta del primer paso MUST ser la misma exista o no el correo, y se limita a 3 solicitudes por hora por correo.

#### Scenario: Correo no registrado
- **WHEN** se pide el enlace para un correo que no está inscrito
- **THEN** la respuesta es 202 igual que para uno inscrito y no se envía correo

#### Scenario: Enlace reutilizado
- **WHEN** se canjea por segunda vez la misma clave
- **THEN** la respuesta es 401
