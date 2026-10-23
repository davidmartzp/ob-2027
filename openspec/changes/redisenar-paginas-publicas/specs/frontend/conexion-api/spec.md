# Spec Delta

## Purpose

Define cómo el sitio público habla con el backend: dónde se configura la API, cómo se tratan sus errores y cómo se comportan las páginas con datos vivos sobre un sitio prerenderizado sin servidor Node.

## ADDED Requirements

### Requirement: URL de la API en el contenido
La URL del backend SHALL configurarse en un solo lugar del contenido (`evento.json → apiUrl`) y todas las páginas MUST usarla. Con `apiUrl` nulo, las páginas con datos vivos SHALL mostrar un aviso visible de modo demostración en lugar de fallar.

#### Scenario: Sin API configurada
- **WHEN** `apiUrl` es `null` y se abre `/talleres`
- **THEN** la página muestra su contenido fijo y un aviso de que los talleres aún no están conectados, sin errores en consola

### Requirement: Errores de la API legibles
Ante una respuesta de error del backend (`{"error": {"code", "message", "fields"}}`) el sitio SHALL mostrar el `message` y, en formularios, el mensaje de cada campo de `fields` junto a su campo. Un fallo de red MUST mostrar un mensaje genérico con opción de reintentar.

#### Scenario: Validación en un formulario
- **WHEN** el backend responde 422 con `fields.email`
- **THEN** el mensaje aparece bajo el campo de correo y el resto del formulario conserva lo escrito

#### Scenario: Sin conexión
- **WHEN** la petición falla por red
- **THEN** se muestra «No pudimos conectar» con un botón para reintentar

### Requirement: Datos vivos sobre HTML prerenderizado
Las páginas SHALL prerenderizarse con su contenido fijo (títulos, textos, imágenes) y cargar los datos vivos en el navegador después del primer pintado, mostrando un estado de carga, uno vacío y uno de error. Ninguna página MUST depender de un servidor Node en producción.

#### Scenario: HTML sin JavaScript
- **WHEN** un buscador descarga `/booklet`
- **THEN** recibe el título, la introducción y la meta de la página en el HTML, aunque la lista de talleres llegue después

### Requirement: Inscripción contra el servicio nuevo
El formulario de `/inscripciones` SHALL enviar la inscripción a `POST {apiUrl}/attendees` con el mismo JSON de hoy (`EnvioInscripcion`) y mostrar el número de participante devuelto cuando lo hay.

#### Scenario: Inscripción con número
- **WHEN** el backend responde 201 con `code: 141`
- **THEN** la pantalla de éxito muestra el número 141
