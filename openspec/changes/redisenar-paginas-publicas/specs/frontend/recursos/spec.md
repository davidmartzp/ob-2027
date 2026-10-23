# Spec Delta

## Purpose

La página `/recursos` reúne los documentos del congreso (guía, cronograma, bases de convocatorias) y permite leer el cronograma sin descargarlo.

## ADDED Requirements

### Requirement: Documentos descargables
`/recursos` SHALL listar los documentos de `recursos.json` (nombre, descripción, tipo de archivo, enlace), cada uno con su botón «Descargar» o «Abrir» que abre en otra pestaña. Un documento marcado `porConfirmar` MUST mostrar el aviso ✳.

#### Scenario: Documento de Google Docs
- **WHEN** el recurso es un enlace a Google Docs
- **THEN** el botón dice «Abrir» y abre en otra pestaña con `rel="noopener"`

### Requirement: Cronograma embebido
La página SHALL mostrar el cronograma PDF embebido con el visor configurado y, si el navegador no lo muestra, un enlace alternativo para abrirlo.

#### Scenario: Visor bloqueado
- **WHEN** el navegador no carga el visor
- **THEN** queda visible el enlace «Abrir cronograma»

### Requirement: Accesos desde la home
La sección de itinerario de la home y el menú SHALL enlazar a `/recursos` (hoy el destino `recursos` apunta a `#itinerario`).

#### Scenario: Menú del congreso
- **WHEN** se elige «Recursos» en el menú
- **THEN** se abre `/recursos` (o `/en/recursos` en inglés)
