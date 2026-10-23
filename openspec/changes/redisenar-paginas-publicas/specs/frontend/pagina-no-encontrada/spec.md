# Spec Delta

## Purpose

Una página 404 propia, con el lenguaje del diagrama, que orienta al visitante en vez de llevarlo sin aviso a la home.

## ADDED Requirements

### Requirement: Página 404
Una ruta inexistente SHALL mostrar la página «no encontrada» (en el idioma de la URL) con el mensaje del sitio antiguo adaptado («¡Ups! Esta página se perdió en un pliegue»), enlaces a Inicio, Talleres, Inscripciones y Recursos, y MUST NOT redirigir a la home.

#### Scenario: Ruta inexistente en inglés
- **WHEN** se visita `/en/no-existe`
- **THEN** se muestra la página no encontrada en inglés con sus enlaces

### Requirement: Código 404 en el hosting
El build SHALL generar la página 404 como HTML estático y el despliegue MUST configurar el hosting para servirla con estado HTTP 404 ante rutas inexistentes, para que los buscadores no indexen páginas vacías.

#### Scenario: Petición directa al hosting
- **WHEN** se pide `https://origamibogota.com/no-existe`
- **THEN** la respuesta tiene estado 404 y el contenido de la página no encontrada
