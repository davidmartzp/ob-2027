# Spec Delta

## Purpose

Organiza la programación del congreso: salones, actividades, eventos (un taller o actividad en un salón, día y franja horaria) y la inscripción de los asistentes a esos eventos con control de cupo y de choques de horario.

## ADDED Requirements

### Requirement: Salones
`GET /rooms` SHALL devolver los salones con `id`, `name` y `capacity`. El CMS SHALL poder crearlos, editarlos y eliminarlos (`POST /rooms`, `PUT /rooms/{id}`, `DELETE /rooms/{id}`, privados); un salón con eventos programados MUST NOT eliminarse (409).

#### Scenario: Eliminar salón ocupado
- **WHEN** el CMS elimina un salón que tiene eventos
- **THEN** la respuesta es 409 y el salón se conserva

### Requirement: Actividades
`GET /activities` SHALL devolver las actividades no retiradas (`id`, `name`, `public`, `level`, `time`, `observations`, `status`). El CMS SHALL poder crearlas, editarlas y retirarlas (`POST /activities`, `PUT /activities/{id}`, `DELETE /activities/{id}` → estado 0).

#### Scenario: Retirar actividad
- **WHEN** el CMS retira una actividad
- **THEN** deja de aparecer en `GET /activities`

### Requirement: Programar eventos
`POST /events` (privado) con `id_room`, `day` (`Saturday` o `Sunday`), `start_time`, `end_time` (`HH:MM`), `capacity` opcional y exactamente uno de `id_workshop` o `id_activity` SHALL crear el evento y responder 201. MUST rechazar con 409 si en ese salón y día ya hay un evento que se solapa, y con 422 si `end_time` no es posterior a `start_time` o si el taller no está activo.

#### Scenario: Solapamiento en el salón
- **WHEN** ya existe un evento de 10:00 a 12:00 en el salón 1 el sábado y se programa otro de 11:00 a 13:00 en el mismo salón y día
- **THEN** la respuesta es 409

#### Scenario: Taller y actividad a la vez
- **WHEN** se envían `id_workshop` e `id_activity` en el mismo evento
- **THEN** la respuesta es 422

### Requirement: Listado y retiro de eventos en el CMS
`GET /events` (privado) SHALL devolver todos los eventos con `id`, `id_room`, `id_workshop`, `id_activity`, `day`, `start_time`, `end_time`, `capacity`, `inscritos` y `active`. `DELETE /events/{id}` (privado) SHALL eliminar el evento y las inscripciones de asistentes a él.

#### Scenario: Eliminar evento con inscritos
- **WHEN** el CMS elimina un evento con 5 inscritos
- **THEN** el evento y sus 5 inscripciones desaparecen

### Requirement: Eventos activos para el público
`GET /events/activos` (público) SHALL devolver los eventos activos como `[{event: {id, day, start_time, end_time, capacity, cupos_libres}, room: {id, name}, workshop: {id, name, image, instructor: {id, name}} | null, activity: {id, name} | null}]`, ordenados por día, hora de inicio y salón.

#### Scenario: Cupos libres
- **WHEN** un evento tiene capacidad 20 y 18 inscritos
- **THEN** su `cupos_libres` es 2

### Requirement: Inscripción de un asistente a un evento
`POST /attendees/{id}/events` con token de ese asistente y `{"id_event"}` SHALL inscribirlo y responder 201. MUST rechazar con 409 si el evento no tiene cupo, si ya estaba inscrito o si se solapa en día y hora con otro evento suyo, indicando cuál. Cupo e inscripción MUST resolverse de forma atómica: dos peticiones simultáneas por el último cupo no pueden ambas tener éxito.

#### Scenario: Último cupo disputado
- **WHEN** quedan 1 cupo y dos asistentes se inscriben a la vez
- **THEN** solo uno recibe 201 y el otro recibe 409 por falta de cupo

#### Scenario: Choque de horario
- **WHEN** el asistente ya tiene un evento el sábado de 10:00 a 12:00 y se inscribe a otro el sábado de 11:00 a 12:30
- **THEN** la respuesta es 409 con el nombre del evento con el que choca

### Requirement: Eventos de un asistente
`GET /attendees/{id}/events` con token de ese asistente SHALL devolver los ids de sus eventos, y `DELETE /attendees/{id}/events/{idEvento}` SHALL cancelar su inscripción y liberar el cupo (204).

#### Scenario: Cancelar inscripción
- **WHEN** un asistente cancela su inscripción a un evento con 0 cupos libres
- **THEN** el evento pasa a tener 1 cupo libre

### Requirement: Listas de asistentes por evento
`GET /events/{id}/attendees` (privado) SHALL devolver los inscritos al evento (`id`, `code`, `name`, `lastname`) ordenados por número de participante, y `GET /events/talleres` (privado) SHALL devolver los eventos de taller activos con el nombre del taller y del tallerista, para imprimir las listas.

#### Scenario: Lista de un taller
- **WHEN** el CMS pide la lista de un evento con inscritos de números 12, 3 y 7
- **THEN** recibe los inscritos en el orden 3, 7, 12
