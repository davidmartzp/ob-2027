# Spec Delta

## Purpose

Gestiona los talleres que proponen los asistentes (talleristas): la propuesta y edición por su autor, la imagen del modelo, el cupo y los talleres en dos partes, la revisión en el CMS y el listado público.

## ADDED Requirements

### Requirement: Estados del taller
Cada taller SHALL tener uno de estos estados: 1 (activo: se publica y se puede programar), 2 (oculto: lo ve su autor, no se publica) y 0 (retirado). Un taller retirado MUST NOT aparecer en ningún listado ni poder programarse o recibir inscripciones.

#### Scenario: Taller retirado
- **WHEN** un taller pasa a estado 0
- **THEN** deja de aparecer en el listado público, en los talleres del autor y en los disponibles para programar

### Requirement: Propuesta de talleres por el asistente
`POST /attendees/{id}/workshops` con token de ese asistente SHALL crear uno o varios talleres con `name`, `level`, `public`, `duration` (minutos, 15 a 480) y `observations`, en estado 1, asociados al asistente, y responder 201 con los talleres creados. `level` y `public` MUST ser valores de las listas configuradas.

#### Scenario: Propuesta de dos talleres
- **WHEN** un asistente autenticado propone dos talleres válidos
- **THEN** la respuesta es 201 con ambos talleres en estado 1

### Requirement: Talleres del asistente
`GET /attendees/{id}/workshops` con token de ese asistente SHALL devolver sus talleres en estado 1 o 2 con `id`, `name`, `public`, `level`, `duration`, `observations`, `image` (URL absoluta o `null`), `status` y sus socios.

#### Scenario: Consultar mis talleres
- **WHEN** un asistente autenticado consulta sus talleres
- **THEN** recibe solo los suyos, sin los retirados

### Requirement: Edición y retiro de talleres
`PUT /workshops/{id}` SHALL actualizar `name`, `public`, `level`, `duration` y `observations`, y `DELETE /workshops/{id}` SHALL pasar el taller a estado 0. Ambos MUST aceptar el token del CMS o el token del asistente autor del taller y rechazar con 403 a cualquier otro asistente.

#### Scenario: Autor edita su taller
- **WHEN** el autor cambia el nivel de su taller
- **THEN** la respuesta es 200 con el taller actualizado

#### Scenario: Otro asistente intenta retirar
- **WHEN** un asistente que no es el autor envía `DELETE /workshops/{id}`
- **THEN** la respuesta es 403 y el taller no cambia

### Requirement: Imagen del taller
`POST /workshops/{id}/image` (multipart, campo `image`) SHALL guardar la imagen del modelo del taller y responder `{"image": <URL absoluta>}`. MUST aceptar solo JPEG, PNG o WebP verificados por su contenido (no por la extensión ni el tipo que declara el cliente), de hasta 5 MB, guardarlos con un nombre y extensión generados por el servidor en una carpeta donde no se ejecuta código, y reemplazar la imagen anterior. Mismos permisos que la edición.

#### Scenario: Script disfrazado
- **WHEN** se sube un archivo `.php` (o un `.jpg` que contiene código PHP)
- **THEN** la respuesta es 422 y no se guarda ningún archivo

#### Scenario: Imagen demasiado grande
- **WHEN** se sube una imagen de 8 MB
- **THEN** la respuesta es 422

#### Scenario: Sin sesión
- **WHEN** se sube una imagen sin token
- **THEN** la respuesta es 401

### Requirement: Listado público de talleres
`GET /workshops` (público) SHALL devolver, en orden aleatorio, los talleres activos con `id`, `name`, `observations`, `duration`, `public`, `level`, `image`, el tallerista (`instructor: {id, name, lastname}`) y sus socios activos (`partners: [{id, name, logo, whatsapp, message, whatsapp_url}]`), sin datos de contacto del tallerista.

#### Scenario: Datos personales
- **WHEN** se consulta el listado público
- **THEN** ninguna entrada incluye correo, teléfono ni documento del tallerista

### Requirement: Talleres en el CMS
`GET /cms/workshops` (privado) SHALL devolver todos los talleres no retirados con sus datos, el tallerista y sus campos de programación. `PATCH /workshops/{id}` (privado) SHALL cambiar `status`, `tickets` (cupo restante, entero mayor o igual a 0), `part` (nulo, 1 o 2) y `relation` (id del taller del que es continuación).

#### Scenario: Fijar cupo
- **WHEN** el CMS fija `tickets` en 20 para un taller
- **THEN** el taller admite 20 inscripciones más

#### Scenario: Relación inválida
- **WHEN** el CMS fija como `relation` el id de un taller inexistente o del propio taller
- **THEN** la respuesta es 422
