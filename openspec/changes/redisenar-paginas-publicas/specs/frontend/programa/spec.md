# Spec Delta

## Purpose

La página `/booklet` es el programa público de talleres del congreso, consultable e imprimible (antes `BookletComponent` de angular-ob).

## ADDED Requirements

### Requirement: Programa por día
`/booklet` SHALL mostrar la programación de `GET /events/schedule` agrupada por día (sábado, domingo) y hora, con nombre, salón, horario, nivel, público, duración, tallerista, observaciones, foto y socios.

#### Scenario: Dos días
- **WHEN** hay talleres el sábado y el domingo
- **THEN** cada día aparece en su bloque, ordenado por hora de inicio

### Requirement: Filtros
La página SHALL filtrar por día, nivel (principiante, intermedio, avanzado), público (adultos, niños), salón y tallerista, combinables, con «Limpiar filtros» y un mensaje cuando ningún taller coincide. Los filtros MUST reflejarse en la URL para poder compartir una vista filtrada.

#### Scenario: Vista compartida
- **WHEN** se abre `/booklet?dia=domingo&nivel=A`
- **THEN** se muestran solo los talleres avanzados del domingo

### Requirement: Sin datos personales
El programa público MUST NOT mostrar inscritos ni datos de contacto de nadie (el booklet antiguo mostraba correo y teléfono de los participantes de cada taller); solo el nombre y primer apellido del tallerista.

#### Scenario: Ficha de un taller
- **WHEN** se abre la ficha de un taller
- **THEN** no aparece ninguna lista de participantes, correo ni teléfono

### Requirement: Versión imprimible
La página SHALL tener estilos de impresión: un taller por bloque sin cortes, sin menú, filtros ni animaciones, en tinta sobre blanco.

#### Scenario: Imprimir el sábado
- **WHEN** se imprime la vista filtrada por sábado
- **THEN** el papel muestra solo los talleres del sábado, completos
