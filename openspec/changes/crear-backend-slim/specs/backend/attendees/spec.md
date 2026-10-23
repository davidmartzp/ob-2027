# Spec Delta

## Purpose

Gestiona a los asistentes (attendees) del congreso: su inscripción desde el sitio público, su número de participante, el acceso por turnos para elegir talleres y su administración desde el CMS.

## ADDED Requirements

### Requirement: Inscripción pública
`POST /attendees` (público) SHALL registrar un asistente con el JSON que envía el formulario del sitio (`name`, `lastname`, `age`, `country`, `city`, `email`, `phone`, `companion`, `idname`, `paymethod`, `paydate`, `receipt`, `group`, `origamigroup`, `medium`, `othermedium`, `info`, `cards`, `cardgroup`, `days`, `printCertificate`, `go_to_chiva`, `chiva_companions`) y responder 201 con `{"id", "status": 0, "code"}`. El asistente nuevo MUST quedar en estado pendiente (0).

#### Scenario: Inscripción válida
- **WHEN** el sitio envía una inscripción completa y válida
- **THEN** la respuesta es 201, el asistente queda guardado con estado 0 y se encolan los correos de inscripción

#### Scenario: Medio «otro»
- **WHEN** la inscripción trae `othermedium` no vacío
- **THEN** el valor guardado en `info` es el de `othermedium`

### Requirement: Número al inscribirse
Al inscribirse, un asistente elegible SHALL recibir de inmediato el número siguiente al mayor asignado. Es elegible quien no es staff, tiene edad mayor o igual a la mínima configurada (12) y paga con un método fuera de la lista excluida configurada (`Grupo de apoyo`, `Invitado`, `Invitado(a)`); los demás quedan sin número (`code` nulo). Dos inscripciones simultáneas MUST NOT recibir el mismo número.

#### Scenario: Inscripción elegible
- **WHEN** el mayor número asignado es 140 y se inscribe un adulto que paga por transferencia
- **THEN** recibe el número 141

#### Scenario: Niño o invitado
- **WHEN** se inscribe un niño de 9 años o alguien que paga como `Invitado(a)`
- **THEN** queda sin número

### Requirement: Validación de la inscripción
La inscripción MUST rechazarse con 422 cuando falten `name`, `lastname`, `age`, `email`, `phone`, `idname`, `paymethod` o `days`; cuando `email` no sea un correo válido; cuando `age` no esté entre 1 y 120; cuando `days` no sea `T`, `S` o `D`; o cuando algún texto supere su longitud máxima. Varios asistentes MAY compartir correo (familias).

#### Scenario: Correo inválido
- **WHEN** se envía `email` igual a `pepito`
- **THEN** la respuesta es 422 con un mensaje para `email` y no se guarda nada

### Requirement: Protección contra abuso de la inscripción
La inscripción pública SHALL limitarse a 10 envíos por hora por IP (429 al superarlo) y SHALL ignorar sin guardar los envíos que llenen el campo trampa `website`, respondiendo como si fueran correctos.

#### Scenario: Bot con campo trampa
- **WHEN** llega una inscripción con `website` no vacío
- **THEN** la respuesta es 201 pero no se guarda ningún asistente ni se envían correos

### Requirement: Verificación del número de participante
`POST /attendees/verify-code` (público) con `{"email", "code"}` SHALL buscar un asistente no eliminado con ese correo cuyo `code` o `special_code` coincida. Si existe y su turno está abierto, responde `{"valid": true, "id", "code", "name", "token"}` con un token de asistente; si no existe, `{"valid": false, "message"}` sin revelar qué dato falla. MUST NOT existir ningún código maestro que salte la verificación o el turno.

#### Scenario: Código especial
- **WHEN** un asistente sin `code` pero con `special_code` `A12` verifica con `A12` dentro de su turno
- **THEN** la respuesta es `valid: true` con su `id` y un token

#### Scenario: Adivinar números
- **WHEN** se prueban 10 números equivocados para el mismo correo en 15 minutos
- **THEN** el siguiente intento responde 429 aunque el número sea correcto

#### Scenario: Código maestro heredado
- **WHEN** alguien envía el código `99099` con cualquier correo
- **THEN** se trata como cualquier otro código y, si no existe, la respuesta es `valid: false`

### Requirement: Turnos para elegir talleres
El acceso SHALL abrirse solo en los días del congreso configurados que correspondan a `days` del asistente (`S` sábado, `D` domingo, `T` ambos), entre la hora de inicio (08:00) y de cierre (19:00) configuradas, por grupos: el grupo es `ceil(número / tamaño)` (25) y entra `(grupo − 1) × minutos` (10) después del inicio; con `special_code` (p. ej. `A12`) el número son sus dígitos. Fuera del turno la respuesta es `valid: false` con la hora y el día a partir de los cuales podrá ingresar.

#### Scenario: Grupo aún no habilitado
- **WHEN** el número 60 (grupo 3) verifica el sábado del congreso a las 08:05
- **THEN** la respuesta es `valid: false` con un mensaje que indica las 08:20

#### Scenario: Día no elegido
- **WHEN** un asistente con `days` igual a `D` verifica el sábado del congreso
- **THEN** la respuesta es `valid: false` indicando que su inscripción empieza el domingo

#### Scenario: Después del cierre
- **WHEN** un asistente con `days` igual a `T` verifica el sábado a las 19:30
- **THEN** la respuesta es `valid: false` indicando su hora de ingreso del domingo

#### Scenario: Grupo fuera de horario
- **WHEN** el turno de un número empezaría después de la hora de cierre
- **THEN** la respuesta es `valid: false` indicando que el horario de su grupo ya concluyó

### Requirement: Pruebas del turno sin puerta trasera
Para ensayar fuera de los días del congreso, la configuración SHALL permitir ignorar el turno solo cuando el entorno no es de producción; en producción esa opción MUST ignorarse.

#### Scenario: Opción activa en producción
- **WHEN** `APP_ENV` es `production` y la opción de ignorar el turno está activa
- **THEN** los turnos se aplican igualmente

### Requirement: Recordatorio del número de participante
`POST /attendees/remind-code` (público) con `{"email"}` SHALL enviar a ese correo un único mensaje con el nombre y el número (o código especial, o «sin número aún») de cada asistente no eliminado registrado con él, y responder siempre 202 con el mismo cuerpo. La API MUST NOT devolver números en la respuesta. Se limita a 3 solicitudes por hora por correo.

#### Scenario: Familia con un mismo correo
- **WHEN** una madre y dos hijos están inscritos con el mismo correo y se pide el recordatorio
- **THEN** llega un correo con los tres participantes y la respuesta no contiene ningún número

### Requirement: Listado de asistentes en el CMS
`GET /attendees` (privado) SHALL devolver los asistentes no eliminados ordenados por `paydate`, con todos sus campos (`id`, `code`, `special_code`, `name`, `lastname`, `age`, `country`, `city`, `email`, `phone`, `companion`, `idname`, `paydate`, `paymethod`, `receipt`, `origamigroup`, `info`, `cardsgroup`, `cards`, `days`, `staff`, `go_to_chiva`, `printCertificate`, `chiva_companions`, `pay_amount`, `status`, `created_at`, `updated_at`). `GET /attendees/{id}` SHALL devolver uno.

#### Scenario: Asistente eliminado
- **WHEN** el CMS lista los asistentes y uno tiene estado 5
- **THEN** ese asistente no aparece en el listado

### Requirement: Edición de asistentes en el CMS
`PUT /attendees/{id}` (privado) SHALL actualizar los campos de la inscripción y además `code`, `special_code`, `staff`, `status` y `pay_amount`, con las mismas reglas de formato y longitud (solo nombre, apellido, edad y correo son obligatorios, para poder guardar registros heredados incompletos), y responder 200 con el asistente actualizado. Un `code` ya usado por otro asistente MUST rechazarse con 409.

#### Scenario: Asistente inexistente
- **WHEN** el CMS edita `PUT /attendees/999999`
- **THEN** la respuesta es 404

#### Scenario: Número repetido
- **WHEN** el CMS asigna a un asistente el número que ya tiene otro
- **THEN** la respuesta es 409

### Requirement: Baja lógica de asistentes
`DELETE /attendees/{id}` (privado) SHALL marcar al asistente con estado 5 (eliminado) sin borrar la fila y responder 204.

#### Scenario: Eliminar asistente
- **WHEN** el CMS elimina un asistente
- **THEN** la fila sigue en la base con estado 5 y deja de aparecer en el listado

### Requirement: Confirmación masiva
`POST /attendees/confirm` (privado) con `{"ids": [<número>…]}` SHALL pasar a estado 1 solo a los asistentes indicados que estén en estado 0 y responder `{"confirmed": <número>}`.

#### Scenario: Mezcla de estados
- **WHEN** se confirman 3 ids, de los cuales uno ya estaba confirmado
- **THEN** la respuesta es `confirmed: 2`

### Requirement: Números para inscritos sin número
`POST /attendees/codes` (privado) SHALL asignar números consecutivos, continuando desde el mayor asignado, a los asistentes no eliminados y elegibles que no tienen número, ordenados por `paydate` ascendente (y por `id` en empate), y responder cuántos se asignaron.

#### Scenario: Inscritos tardíos
- **WHEN** el mayor número es 120 y dos elegibles sin número pagaron el 3 y el 1 de mayo
- **THEN** el del 1 de mayo recibe 121, el del 3 de mayo 122 y nadie más cambia

### Requirement: Regenerar todos los números
`POST /attendees/codes/regenerate` (privado) con `{"confirmation": "regenerate"}` SHALL quitar el número a todos los asistentes y volver a numerar desde 1, por `paydate` ascendente, a los no eliminados y elegibles; sin esa confirmación literal MUST responder 422 sin cambiar nada.

#### Scenario: Orden por fecha de pago
- **WHEN** se regenera con tres elegibles que pagaron el 3, el 1 y el 2 de marzo
- **THEN** reciben los números 3, 1 y 2 respectivamente

#### Scenario: Sin confirmación
- **WHEN** el CMS pide regenerar sin el campo `confirmation`
- **THEN** la respuesta es 422 y los números se conservan
