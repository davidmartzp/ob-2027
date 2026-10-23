# Spec Delta

## Purpose

Gestiona los talleres que proponen los asistentes (talleristas): la propuesta y edición por su autor, la imagen del modelo, la revisión en el CMS y el listado público de talleres aprobados.

## ADDED Requirements

### Requirement: Estados del taller
Cada taller SHALL tener uno de estos estados: 1 (activo, se publica y se puede programar), 2 (propuesto, en revisión) y 0 (retirado). Un taller retirado MUST NOT aparecer en listados públicos ni poder programarse.

#### Scenario: Taller retirado
- **WHEN** un taller pasa a estado 0
- **THEN** deja de aparecer en el listado público y en los talleres disponibles para programar

### Requirement: Propuesta de talleres por el asistente
`POST /attendees/{id}/workshops` con token de ese asistente SHALL crear uno o varios talleres con `name`, `level`, `public`, `time` y `observations`, en estado 2, asociados al asistente, y responder 201 con los talleres creados. `level` MUST ser uno de los niveles configurados y `time` un número de minutos entre 15 y 480.

#### Scenario: Propuesta de dos talleres
- **WHEN** un asistente autenticado propone dos talleres válidos
- **THEN** la respuesta es 201 con ambos talleres en estado 2

### Requirement: Talleres del asistente
`GET /attendees/{id}/workshops` con token de ese asistente SHALL devolver sus talleres en estado 1 o 2 con `id`, `name`, `public`, `level`, `time`, `observations`, `image` (URL absoluta o `null`) y `status`.

#### Scenario: Consultar mis talleres
- **WHEN** un asistente autenticado consulta sus talleres
- **THEN** recibe solo los suyos, sin los retirados

### Requirement: Edición y retiro de talleres
`PUT /workshops/{id}` SHALL actualizar `name`, `public`, `level`, `time` y `observations`, y `DELETE /workshops/{id}` SHALL pasar el taller a estado 0. Ambos MUST aceptar el token del CMS o el token del asistente autor del taller y rechazar con 403 a cualquier otro asistente.

#### Scenario: Autor edita su taller
- **WHEN** el autor cambia el nivel de su taller
- **THEN** la respuesta es 200 con el taller actualizado

#### Scenario: Otro asistente intenta retirar
- **WHEN** un asistente que no es el autor envía `DELETE /workshops/{id}`
- **THEN** la respuesta es 403 y el taller no cambia

### Requirement: Imagen del taller
`POST /workshops/{id}/imagen` (multipart, campo `image`) SHALL guardar la imagen del modelo del taller y responder `{"image": <URL absoluta>}`. MUST aceptar solo JPEG, PNG o WebP verificados por su contenido (no por la extensión), de hasta 5 MB, guardarlos con un nombre generado por el servidor y reemplazar la imagen anterior. Mismos permisos que la edición.

#### Scenario: Archivo que no es imagen
- **WHEN** se sube un `.php` renombrado como `.jpg`
- **THEN** la respuesta es 422 y no se guarda ningún archivo

#### Scenario: Imagen demasiado grande
- **WHEN** se sube una imagen de 8 MB
- **THEN** la respuesta es 422

### Requirement: Listado público de talleres
`GET /workshops` (público) SHALL devolver, en orden aleatorio, los talleres activos que tienen imagen, con `id`, `name`, `image`, `observations`, el nombre y apellido del tallerista (`instructor: {id, name}`) y sus socios (`partners: [{id, name, logo, whatsapp_url}]`), sin datos de contacto del tallerista.

#### Scenario: Taller sin imagen
- **WHEN** hay un taller activo sin imagen
- **THEN** no aparece en el listado público

#### Scenario: Datos personales
- **WHEN** se consulta el listado público
- **THEN** ninguna entrada incluye correo, teléfono ni documento del tallerista

### Requirement: Talleres en el CMS
`GET /cms/workshops` (privado) SHALL devolver todos los talleres no retirados con sus datos y los del tallerista (`id`, `name`, `lastname`), y `PATCH /workshops/{id}/estado` (privado) SHALL cambiar su estado entre 0, 1 y 2.

#### Scenario: Aprobar propuesta
- **WHEN** el CMS cambia un taller de estado 2 a 1
- **THEN** el taller pasa a estar disponible para programar
