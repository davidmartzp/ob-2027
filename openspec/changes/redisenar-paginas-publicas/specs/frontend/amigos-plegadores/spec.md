# Spec Delta

## Purpose

La página `/amigos-plegadores` presenta al grupo que organiza el congreso: su origen, sus reuniones en AseisManos, el evento anual y la chiva, con su galería de fotos.

## ADDED Requirements

### Requirement: Historia del grupo
`/amigos-plegadores` SHALL presentar, en pasos numerados del diagrama, los apartados del sitio antiguo: el origen, las reuniones (con enlace a AseisManos), el evento anual y la chiva, con el texto actual en español y su traducción al inglés.

#### Scenario: Enlace a AseisManos
- **WHEN** se lee el apartado de las reuniones
- **THEN** «AseisManos» enlaza a su sitio en otra pestaña

### Requirement: Galería «El parche»
La página SHALL mostrar las fotos del grupo (`originales/congreso/images/ap`) en una galería navegable con teclado, con texto alternativo descriptivo en cada foto y sin cargar todas a resolución completa al abrir la página.

#### Scenario: Navegar con teclado
- **WHEN** se abre una foto y se pulsa la flecha derecha
- **THEN** se muestra la siguiente foto; Escape cierra la vista ampliada

### Requirement: Presencia en el menú
El menú del congreso y el pie SHALL enlazar a `/amigos-plegadores`.

#### Scenario: Desde el pie
- **WHEN** se elige «Amigos Plegadores» en el pie
- **THEN** se abre la página en el idioma actual
