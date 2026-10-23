# Spec Delta

## Purpose

Gestiona a los asistentes (attendees) del congreso: su inscripción desde el sitio público, la verificación de su número de participante y su administración desde el CMS.

## ADDED Requirements

### Requirement: Inscripción pública
`POST /attendees` (público) SHALL registrar un asistente con el JSON que envía el formulario del sitio (`name`, `lastname`, `age`, `country`, `city`, `email`, `phone`, `companion`, `idname`, `paymethod`, `paydate`, `receipt`, `group`, `origamigroup`, `medium`, `othermedium`, `info`, `cards`, `cardgroup`, `days`, `printCertificate`, `go_to_chiva`, `chiva_companions`) y responder 201 con `{"id": <número>, "status": 0}`. El asistente nuevo MUST quedar en estado pendiente (0).

#### Scenario: Inscripción válida
- **WHEN** el sitio envía una inscripción completa y válida
- **THEN** la respuesta es 201, el asistente queda guardado con estado 0 y se disparan los correos de inscripción

#### Scenario: Medio «otro»
- **WHEN** la inscripción trae `othermedium` no vacío
- **THEN** el valor guardado en `info` es el de `othermedium`

### Requirement: Validación de la inscripción
La inscripción MUST rechazarse con 422 cuando falten `name`, `lastname`, `age`, `email`, `phone`, `idname`, `paymethod` o `days`; cuando `email` no sea un correo válido; cuando `age` no esté entre 1 y 120; cuando `days` no sea `T`, `S` o `D`; o cuando algún texto supere su longitud máxima.

#### Scenario: Correo inválido
- **WHEN** se envía `email` igual a `pepito`
- **THEN** la respuesta es 422 con un mensaje para `email` y no se guarda nada

### Requirement: Protección contra abuso de la inscripción
La inscripción pública SHALL limitarse a 10 envíos por hora por IP (429 al superarlo) y SHALL ignorar sin guardar los envíos que llenen el campo trampa `website`, respondiendo como si fueran correctos.

#### Scenario: Bot con campo trampa
- **WHEN** llega una inscripción con `website` no vacío
- **THEN** la respuesta es 201 pero no se guarda ningún asistente ni se envían correos

### Requirement: Verificación del número de participante
`POST /attendees/verificar-codigo` (público) con `{"email", "code"}` SHALL responder `{"valid": true, "id": <número>, "token": <token de asistente>}` si existe un asistente no eliminado con ese correo (sin distinguir mayúsculas) y ese número; si no, `{"valid": false, "message": <texto>}` sin revelar cuál de los dos datos falla. Si hay un turno de ingreso configurado y aún no le toca al grupo del número, MUST responder `valid: false` con la hora a partir de la cual podrá ingresar.

#### Scenario: Datos correctos
- **WHEN** se envía el correo y el número de un asistente confirmado
- **THEN** la respuesta es `valid: true` con su `id` y un token de asistente

#### Scenario: Turno aún no habilitado
- **WHEN** el turno empieza a las 08:00, cada grupo de 20 números entra cada 10 minutos, y el número 45 verifica a las 08:05
- **THEN** la respuesta es `valid: false` con un mensaje que indica las 08:20

### Requirement: Listado de asistentes en el CMS
`GET /attendees` (privado) SHALL devolver los asistentes no eliminados con todos sus campos (`id`, `code`, `name`, `lastname`, `age`, `country`, `city`, `email`, `phone`, `companion`, `idname`, `paydate`, `paymethod`, `receipt`, `origamigroup`, `info`, `cardsgroup`, `cards`, `days`, `staff`, `special_code`, `go_to_chiva`, `printCertificate`, `chiva_companions`, `pay_amount`, `status`, `created_at`, `updated_at`), con booleanos como `true`/`false`. `GET /attendees/{id}` SHALL devolver uno.

#### Scenario: Asistente eliminado
- **WHEN** el CMS lista los asistentes y uno tiene estado 5
- **THEN** ese asistente no aparece en el listado

### Requirement: Edición de asistentes en el CMS
`PUT /attendees/{id}` (privado) SHALL actualizar los campos editables del asistente con las mismas reglas de validación de la inscripción y responder 200 con el asistente actualizado; `pay_amount` MUST guardarse como texto.

#### Scenario: Asistente inexistente
- **WHEN** el CMS edita `PUT /attendees/999999`
- **THEN** la respuesta es 404

### Requirement: Baja lógica de asistentes
`DELETE /attendees/{id}` (privado) SHALL marcar al asistente con estado 5 (eliminado) sin borrar la fila y responder 204.

#### Scenario: Eliminar asistente
- **WHEN** el CMS elimina un asistente
- **THEN** la fila sigue en la base con estado 5 y deja de aparecer en el listado

### Requirement: Confirmación masiva
`POST /attendees/confirmar` (privado) con `{"ids": [<número>…]}` SHALL pasar a estado 1 solo a los asistentes indicados que estén en estado 0 y responder `{"confirmados": <número>}`.

#### Scenario: Mezcla de estados
- **WHEN** se confirman 3 ids, de los cuales uno ya estaba confirmado
- **THEN** la respuesta es `confirmados: 2`

### Requirement: Asignación de números de participante
`POST /attendees/codigos` (privado) SHALL asignar números consecutivos, continuando desde el mayor ya asignado, a los asistentes sin número (`code` 0 o nulo) que cumplan: no staff, no eliminados, edad mayor o igual a la mínima configurada (12) y método de pago fuera de la lista excluida configurada (`Grupo de apoyo`, `Invitado`). El orden es por `paydate` ascendente y por `id` en empate. Responde cuántos se asignaron.

#### Scenario: Orden por fecha de pago
- **WHEN** no hay números asignados y tres asistentes válidos tienen fechas de pago 3, 1 y 2 de marzo
- **THEN** reciben los números 3, 1 y 2 respectivamente

#### Scenario: Inscritos tardíos
- **WHEN** el mayor número asignado es 120 y se asignan números a dos inscritos nuevos
- **THEN** reciben 121 y 122 y nadie más cambia de número

#### Scenario: Excluidos
- **WHEN** un asistente tiene 10 años o paga como `Invitado`
- **THEN** no recibe número

### Requirement: Reinicio de números de participante
`POST /attendees/codigos/reiniciar` (privado) con `{"confirmacion": "reiniciar"}` SHALL poner sin número a todos los asistentes y responder cuántos cambiaron; sin esa confirmación literal MUST responder 422 sin cambiar nada.

#### Scenario: Sin confirmación
- **WHEN** el CMS pide el reinicio sin el campo `confirmacion`
- **THEN** la respuesta es 422 y los números se conservan

### Requirement: Recordatorio del número de participante
`POST /attendees/recordar-codigo` (público) con `{"email"}` SHALL enviar por correo el número de participante al asistente con ese correo, si existe y tiene número, y responder siempre 202 con el mismo cuerpo. La API MUST NOT devolver el número en la respuesta. Se limita a 3 solicitudes por hora por correo.

#### Scenario: Consulta de un tercero
- **WHEN** alguien pide el recordatorio con el correo de otra persona
- **THEN** la respuesta es 202 sin el número, y el número solo llega al buzón del asistente
