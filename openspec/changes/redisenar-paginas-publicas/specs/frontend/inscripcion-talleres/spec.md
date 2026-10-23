# Spec Delta

## Purpose

La página `/inscribir-talleres` es donde, el día del congreso y en su turno, cada asistente elige sus talleres (antes `PublicAttendeesComponent` de angular-ob).

## ADDED Requirements

### Requirement: Acceso por correo y número
La página SHALL pedir correo y número de participante (o código especial) y validarlos con `POST /attendees/verify-code`. Con `valid: false` MUST mostrar el `message` del backend (turno, día o datos incorrectos). Con `valid: true` SHALL recordar la sesión del asistente en el navegador (`id`, nombre, número) hasta que pulse «Salir».

#### Scenario: Turno aún cerrado
- **WHEN** el backend responde `valid: false` con «Tu grupo inicia a partir de las 08:20»
- **THEN** la página muestra ese mensaje junto al formulario

#### Scenario: Volver a la página
- **WHEN** un asistente verificado recarga la página
- **THEN** sigue viendo sus talleres sin volver a escribir sus datos

### Requirement: Olvidé mi número
La página SHALL ofrecer «¿Olvidaste tu número?», que llama a `POST /attendees/remind-code` con el correo y muestra siempre el mismo mensaje de confirmación.

#### Scenario: Recordatorio
- **WHEN** el asistente pide el recordatorio
- **THEN** ve «Si ese correo está inscrito, te enviamos tu número» sin el número en pantalla

### Requirement: Talleres del día con filtros
Tras verificar, la página SHALL listar `GET /events/active` agrupados por hora, con filtros por salón, hora y tallerista, cupos restantes, nivel, público, tallerista y socios, y marcar los que el asistente ya tiene (`GET /attendees/{id}/events`). Si el backend responde 403 `enrolment_closed` con `logout: true`, MUST cerrar la sesión local y mostrar el mensaje.

#### Scenario: Ventana cerrada
- **WHEN** a las 19:01 del sábado la lista responde `logout: true`
- **THEN** la sesión local se borra y se muestra cuándo vuelve a abrir

### Requirement: Inscribirse y cancelar
La página SHALL inscribir con `POST /attendees/{id}/events` y cancelar con confirmación con `DELETE /attendees/{id}/events/{event}`, refrescando cupos y «mis talleres». Los rechazos del backend (sin cupo, choque de horario, menor en taller de adultos) MUST mostrarse con su mensaje y el aviso de que un taller en dos partes inscribe ambas.

#### Scenario: Choque de horario
- **WHEN** el backend responde 409 «Ya tienes «Grulla» a esa hora»
- **THEN** el mensaje aparece en la tarjeta del taller y no cambia nada más

#### Scenario: Taller en dos partes
- **WHEN** el asistente se inscribe a una parte
- **THEN** «mis talleres» muestra las dos partes
