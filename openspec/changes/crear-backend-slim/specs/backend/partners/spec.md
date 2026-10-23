# Spec Delta

## Purpose

Gestiona los socios (partners) que patrocinan talleres: su nombre, logo y contacto directo por WhatsApp, y su asociación a los talleres que se muestran en el sitio.

## ADDED Requirements

### Requirement: Listado público de socios
`GET /partners` (público) SHALL devolver los socios con `id`, `name`, `logo` (URL absoluta o `null`), `whatsapp`, `message` y `whatsapp_url` (enlace `https://wa.me/<whatsapp>?text=<message codificado>`).

#### Scenario: Enlace de WhatsApp
- **WHEN** un socio tiene `whatsapp` 573001234567 y `message` «Hola, vengo del congreso»
- **THEN** su `whatsapp_url` es `https://wa.me/573001234567?text=Hola%2C%20vengo%20del%20congreso`

### Requirement: Gestión de socios en el CMS
`POST /partners`, `PUT /partners/{id}` y `DELETE /partners/{id}` (privados) SHALL crear, editar y eliminar socios. `name`, `whatsapp` y `message` son obligatorios; `whatsapp` MUST tener solo dígitos en formato internacional (8 a 15). Eliminar un socio MUST quitarlo de los talleres a los que estaba asociado.

#### Scenario: WhatsApp inválido
- **WHEN** se crea un socio con `whatsapp` igual a `+57 300-123`
- **THEN** la respuesta es 422 con un mensaje para `whatsapp`

#### Scenario: Eliminar socio asociado
- **WHEN** se elimina un socio asociado a dos talleres
- **THEN** ninguno de los dos talleres lo lista

### Requirement: Logo del socio
`POST /partners/{id}/logo` (privado, multipart, campo `logo`) SHALL guardar el logo con las mismas reglas que la imagen de un taller (JPEG, PNG o WebP verificados por contenido, hasta 5 MB, nombre generado) y responder `{"logo": <URL absoluta>}`.

#### Scenario: Logo SVG
- **WHEN** se sube un logo en SVG
- **THEN** la respuesta es 422

### Requirement: Socios de un taller
`GET /workshops/{id}/partners` (público) SHALL devolver los socios del taller, y `PUT /workshops/{id}/partners` con `{"partner_ids": [...]}` SHALL reemplazar la lista completa de socios del taller. Este último MUST exigir token del CMS o del asistente autor del taller y rechazar ids de socios inexistentes con 422.

#### Scenario: Reemplazar socios
- **WHEN** un taller tiene los socios 1 y 2 y se envían `partner_ids: [2, 3]`
- **THEN** el taller queda con los socios 2 y 3

#### Scenario: Sin permiso
- **WHEN** se envía `PUT /workshops/{id}/partners` sin token
- **THEN** la respuesta es 401 y los socios no cambian
