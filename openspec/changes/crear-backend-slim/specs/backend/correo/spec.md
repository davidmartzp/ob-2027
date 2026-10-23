# Spec Delta

## Purpose

Entrega los correos del congreso de forma segura: los automáticos (inscripción, recordatorio del número) y los envíos que el equipo lanza a mano por tipo, con cola, reintentos y sin duplicados.

## ADDED Requirements

### Requirement: Configuración segura del correo
El transporte de correo SHALL configurarse solo con variables de entorno (`MAILER_DSN`, remitente, buzón del equipo). En producción MUST usar SMTP con TLS (implícito o STARTTLS) y verificación del certificado; MUST NOT haber credenciales de correo en el código ni en el repositorio.

#### Scenario: Transporte sin cifrar en producción
- **WHEN** la API arranca con `APP_ENV=production` y un `MAILER_DSN` que desactiva TLS o la verificación del certificado
- **THEN** el arranque falla indicando que el transporte de correo no es seguro

### Requirement: Un fallo de correo no rompe la operación
Cuando una operación (inscripción, asignación de números, recordatorio) dispara correos, un fallo del servidor de correo MUST NOT anular la operación ni cambiar su respuesta: el fallo se registra y el envío queda para reintento.

#### Scenario: Servidor de correo caído
- **WHEN** un asistente se inscribe y el servidor SMTP no responde
- **THEN** la inscripción queda guardada, la respuesta es 201 y el correo queda pendiente de reintento

### Requirement: Contenido seguro de los correos
Los correos SHALL enviarse en HTML con alternativa en texto plano, en UTF-8, con el diseño del congreso. Todo dato aportado por el usuario MUST escaparse al insertarse en el HTML, y los encabezados MUST NOT admitir saltos de línea provenientes de datos del usuario.

#### Scenario: Nombre con HTML
- **WHEN** un asistente se inscribe con nombre `<script>alert(1)</script>`
- **THEN** el correo muestra el texto literal y no contiene la etiqueta `<script>`

#### Scenario: Inyección de encabezados
- **WHEN** el nombre de un asistente contiene un salto de línea seguido de `Bcc: x@y.com`
- **THEN** el correo no tiene destinatarios adicionales

### Requirement: Entrega verificable en desarrollo
En desarrollo los correos SHALL ir a un buzón de pruebas (sandbox de Mailtrap configurado en `MAILER_DSN` del `.env` local) que los capture sin entregarlos a direcciones reales, consultable desde el navegador.

#### Scenario: Correo de prueba
- **WHEN** se inscribe un asistente en el entorno de desarrollo y corre la cola
- **THEN** ambos correos aparecen en el sandbox de Mailtrap y no llegan a ningún buzón real

### Requirement: Correos de inscripción
Al registrarse un asistente SHALL encolarse un correo de bienvenida al asistente y un aviso al buzón de inscripciones del equipo con nombre, correo, teléfono, país, método, fecha y comprobante de pago.

#### Scenario: Inscripción
- **WHEN** se inscribe un asistente
- **THEN** quedan encolados la bienvenida al asistente y el aviso al buzón del equipo

### Requirement: Recordatorio inmediato
El recordatorio del número de participante SHALL enviarse en cuanto se pide y, si falla, quedar en cola para reintento.

#### Scenario: Recordatorio del número
- **WHEN** el asistente 27 pide recordar su número
- **THEN** recibe un correo con su nombre y el número 27

### Requirement: Envíos por tipo lanzados desde el CMS
`POST /emails/{type}` (privado) SHALL encolar el correo de ese tipo para sus destinatarios y responder cuántos se encolaron. Tipos y destinatarios por defecto: `welcome` (no eliminados), `code` (con número asignado), `instructions` y `book` (confirmados), `important-notice` e `general-info` (no eliminados) y `correction` (solo los `ids` indicados: sin `ids` MUST responder 422). Un tipo desconocido MUST responder 404.

#### Scenario: Envío de instrucciones
- **WHEN** hay 80 confirmados y 20 pendientes y el CMS lanza `instructions`
- **THEN** la respuesta indica 80 encolados y solo los confirmados lo reciben

### Requirement: Acotar un envío
`POST /emails/{type}` SHALL aceptar `{"ids": [...]}` para enviar solo a esos asistentes (dentro de los destinatarios del tipo) o `{"test": <correo>}` para enviar un único ejemplo, con los datos del primer destinatario, a esa dirección sin marcar a nadie como enviado.

#### Scenario: Prueba antes de enviar
- **WHEN** el CMS lanza `book` con `test` igual a su correo
- **THEN** solo llega un correo a esa dirección y ningún asistente queda marcado

### Requirement: Previsualizar destinatarios
`GET /emails/{type}/recipients` (privado) SHALL devolver el conteo y la lista (`id`, `name`, `lastname`, `email`, `already_sent`) de quienes lo recibirían, sin enviar nada.

#### Scenario: Conteo previo
- **WHEN** el CMS consulta los destinatarios de `code` con 120 asistentes numerados
- **THEN** recibe 120 sin que se envíe ningún correo

### Requirement: Cola sin duplicados
Los correos SHALL procesarse en una cola persistente con hasta 3 intentos (reintentos a los 1 y 5 minutos) y un tope de envíos por minuto configurable. Un mismo tipo MUST NOT enviarse dos veces al mismo asistente, salvo `welcome` y `code` enviados con `ids` explícitos (reenvío). `GET /emails/log` (privado) SHALL devolver el estado de los envíos (`pending`, `sent`, `failed`) paginado y filtrable por tipo.

#### Scenario: Doble lanzamiento
- **WHEN** el CMS lanza dos veces `general-info`
- **THEN** cada asistente lo recibe una sola vez y el segundo lanzamiento responde 0 encolados

#### Scenario: Reenvío del número
- **WHEN** el CMS lanza `code` con `ids: [27]` a un asistente que ya lo recibió
- **THEN** se encola de nuevo para ese asistente
