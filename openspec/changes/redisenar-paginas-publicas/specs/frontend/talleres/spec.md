# Spec Delta

## Purpose

La página `/talleres` presenta los talleres del congreso y es el espacio donde cada tallerista propone y mantiene los suyos (antes `WorkshopsComponent` de angular-ob).

## ADDED Requirements

### Requirement: Galería pública de talleres
`/talleres` (y `/en/talleres`) SHALL mostrar los talleres activos de `GET /workshops` con su foto, nombre, nivel, público, duración, tallerista y socios (logo y botón de WhatsApp), con una ficha ampliada al elegir uno. Un taller sin foto MUST mostrar una figura de papel plegado en lugar de un hueco.

#### Scenario: Taller con socio
- **WHEN** un taller tiene un socio activo
- **THEN** su ficha muestra el logo del socio y un enlace a su `whatsapp_url` que abre en otra pestaña

#### Scenario: Taller sin foto
- **WHEN** un taller no tiene imagen
- **THEN** la galería muestra el ícono de papel plegado con el nombre del taller

### Requirement: Talleres internacionales destacados
La página SHALL destacar los talleres internacionales definidos en el contenido (`talleres.json`: modelo, tallerista, país, nivel, papel, duración, día, biografía y redes), con aviso visible «✳ por confirmar» mientras el contenido lo marque así.

#### Scenario: Contenido provisional
- **WHEN** un taller internacional tiene `porConfirmar: true`
- **THEN** su tarjeta muestra el aviso ✳

### Requirement: Espacio del tallerista
La página SHALL permitir, sin iniciar sesión, buscar por correo (`POST /workshops/by-email`) y, para cada persona inscrita con ese correo, ver sus talleres, proponer uno o varios, editarlos, retirarlos (con confirmación), subir la foto del modelo y elegir sus socios de la lista pública de socios.

#### Scenario: Familia con un correo
- **WHEN** el correo pertenece a dos inscritos
- **THEN** se muestra un bloque por persona con sus talleres

#### Scenario: Correo no inscrito
- **WHEN** el correo no tiene inscritos
- **THEN** se indica que no hay inscripción con ese correo y se enlaza a `/inscripciones`

#### Scenario: Foto rechazada
- **WHEN** el backend rechaza la imagen (422)
- **THEN** se muestra su mensaje («Solo se aceptan imágenes JPEG, PNG o WebP», «pesa más de 5 MB») y la foto anterior se conserva

### Requirement: Validación antes de enviar
El formulario de taller SHALL validar en el navegador lo mismo que el backend (nombre obligatorio hasta 200 caracteres, nivel P/I/A, público A/N, duración 15–480 minutos, observaciones hasta 500) y SHALL comprobar tipo (JPEG, PNG, WebP) y tamaño (5 MB) de la foto antes de subirla.

#### Scenario: Duración fuera de rango
- **WHEN** el tallerista escribe 5 minutos
- **THEN** el formulario no se envía y muestra el rango permitido
