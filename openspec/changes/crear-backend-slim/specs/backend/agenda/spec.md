# Spec Delta

## Purpose

Organiza la programación del congreso: salones, actividades, eventos (un taller o actividad en un salón, día y franja horaria) y la inscripción de los asistentes a los talleres programados, con cupo, choques de horario, restricción de edad y talleres en dos partes.

## ADDED Requirements

### Requirement: Salones y actividades
`GET /rooms` SHALL devolver los salones (`id`, `name`) y `GET /activities` las actividades (`id`, `name`, `description`, `duration`), ambos para el CMS. Se cargan por semilla o importación; la API no los crea ni edita.

#### Scenario: Salones del CMS
- **WHEN** el CMS pide `GET /rooms` con token
- **THEN** recibe todos los salones con su nombre

### Requirement: Programar eventos
`POST /events` (privado) con `id_room`, `day` (`Saturday` o `Sunday`, como en los datos heredados; se acepta en cualquier combinación de mayúsculas y se guarda así), `start_time`, `end_time` (`HH:MM:SS`) y exactamente uno de `id_workshop` o `id_activity` SHALL crear el evento y responder 201. MUST rechazar con 422 si `end_time` no es posterior a `start_time`, si el salón, taller o actividad no existen o si el taller está retirado, y con 409 si en ese salón y día ya hay un evento que se solapa.

#### Scenario: Taller y actividad a la vez
- **WHEN** se envían `id_workshop` e `id_activity` en el mismo evento
- **THEN** la respuesta es 422

#### Scenario: Solapamiento en el salón
- **WHEN** ya existe un evento de 10:00 a 12:00 en el salón 1 el sábado y se programa otro de 11:00 a 13:00 en el mismo salón y día
- **THEN** la respuesta es 409

### Requirement: Listado y retiro de eventos en el CMS
`GET /events` (privado) SHALL devolver todos los eventos con `id`, `id_room`, `id_workshop`, `id_activity`, `day`, `start_time` y `end_time`. `DELETE /events/{id}` (privado) SHALL eliminar el evento y sus inscripciones, devolviendo al taller un cupo por cada inscripción borrada.

#### Scenario: Eliminar evento con inscritos
- **WHEN** el CMS elimina el evento de un taller con 5 inscritos y 3 cupos restantes
- **THEN** el evento y sus 5 inscripciones desaparecen y el taller queda con 8 cupos

### Requirement: Ventana de inscripción a talleres
`GET /events/active` (público) SHALL responder solo dentro de la ventana configurada (el sábado del congreso de 08:00 a 19:00 y el domingo desde las 08:00, hora de Bogotá, con las fechas de `EVENT_SATURDAY`/`EVENT_SUNDAY`), con los eventos de taller del día en curso. Fuera de la ventana MUST responder 403 con `{"error": {"code": "enrolment_closed", …}, "logout": true}` y el mensaje de cuándo abre.

#### Scenario: Sábado antes de abrir
- **WHEN** se pide el sábado a las 07:59
- **THEN** la respuesta es 403 con `logout: true`

#### Scenario: Domingo abierto
- **WHEN** se pide el domingo a las 08:11
- **THEN** la respuesta solo incluye eventos con `day` igual a `Sunday`

#### Scenario: Ensayo fuera del congreso
- **WHEN** la opción de ignorar el turno está activa en un entorno que no es de producción
- **THEN** la respuesta incluye los eventos de taller de ambos días a cualquier hora

### Requirement: Forma de los eventos activos
Cada evento activo SHALL devolverse como `{event: {id, day, start_time, end_time}, workshop: {id, name, instructor: {id, name}, duration, level, public, image, tickets, partners}, room: {id, name}}`, ordenados por hora de inicio. `instructor.name` es el nombre y el primer apellido; `partners` son los socios activos del taller.

#### Scenario: Cupos visibles
- **WHEN** un taller programado tiene 2 cupos restantes
- **THEN** su `workshop.tickets` es 2

### Requirement: Inscripción de un asistente a un taller
`POST /attendees/{id}/events` con token de ese asistente y `{"id_event"}` SHALL inscribirlo, descontar un cupo del taller y responder 201. MUST rechazar con 422 si el taller está retirado o si el asistente tiene menos de 13 años y el taller es de público adulto (`public` = `A`), y con 409 si no quedan cupos, si ya estaba inscrito o si se solapa en día y hora con otro taller suyo. Cupo e inscripción MUST resolverse de forma atómica.

#### Scenario: Último cupo disputado
- **WHEN** queda 1 cupo y dos asistentes se inscriben a la vez
- **THEN** solo uno recibe 201 y el otro recibe 409 por falta de cupo

#### Scenario: Choque de horario
- **WHEN** el asistente ya tiene un taller el sábado de 10:00 a 12:00 y se inscribe a otro el sábado de 11:00 a 12:30
- **THEN** la respuesta es 409 con el nombre del taller con el que choca

#### Scenario: Menor en taller de adultos
- **WHEN** un asistente de 11 años se inscribe a un taller con `public` igual a `A`
- **THEN** la respuesta es 422 y no se descuenta cupo

### Requirement: Talleres en dos partes
Al inscribirse a un taller de parte 1 SHALL inscribirse también a cada taller de parte 2 que lo continúa (`relation`), y al inscribirse a uno de parte 2, a su parte 1, descontando un cupo en cada uno. Si alguna parte no tiene cupo o choca de horario, la inscripción completa MUST rechazarse sin inscribir ninguna parte. Cancelar una parte SHALL cancelar las demás y devolver sus cupos.

#### Scenario: Inscripción a la parte 1
- **WHEN** un asistente se inscribe a la parte 1 de un taller cuya parte 2 tiene cupo
- **THEN** queda inscrito en ambas partes y cada una pierde un cupo

#### Scenario: Parte 2 sin cupo
- **WHEN** la parte 2 no tiene cupos y el asistente se inscribe a la parte 1
- **THEN** la respuesta es 409 y no queda inscrito en ninguna parte

### Requirement: Eventos de un asistente
`GET /attendees/{id}/events` con token de ese asistente SHALL devolver los ids de sus eventos, y `DELETE /attendees/{id}/events/{idEvento}` SHALL cancelar su inscripción, devolver el cupo y responder 204.

#### Scenario: Cancelar inscripción
- **WHEN** un asistente cancela su inscripción a un taller con 0 cupos
- **THEN** el taller pasa a tener 1 cupo

### Requirement: Programación pública
`GET /events/schedule` (público) SHALL devolver los talleres activos que tienen evento, con `id`, `name`, `image`, `level`, `observations`, `public`, `duration`, `instructor: {id, name}`, `event: {id, day, start_time, end_time, room: {id, name}}` y `partners`, ordenados por día, hora de inicio y salón, a cualquier hora.

#### Scenario: Programación sin eventos
- **WHEN** no hay talleres programados
- **THEN** la respuesta es 200 con una lista vacía

### Requirement: Listas de asistentes por taller
`GET /events/workshops` (privado) SHALL devolver los eventos de taller con `id_event`, `workshop_name` e `instructor`, y `GET /events/{id}/attendees` (privado) los inscritos del evento con `id`, `code` y `name` (nombre y primer apellido), ordenados por número de participante.

#### Scenario: Lista de un taller
- **WHEN** el CMS pide la lista de un evento con inscritos de números 12, 3 y 7
- **THEN** recibe los inscritos en el orden 3, 7, 12
