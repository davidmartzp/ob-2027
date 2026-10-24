# Tasks

## 1. Piezas compartidas (spec `frontend/conexion-api`)

- [x] 1.1 `core/api.ts` (`api()`, `ErrorApi`, `ErrorRed`) y `apiUrl` movido a `evento.json`; pruebas Vitest: respuesta 2xx, error 422 con `fields`, fallo de red, `apiUrl` nulo
- [x] 1.2 `core/registro.ts` envía a `POST /attendees` y la pantalla de éxito muestra el `code` devuelto; actualizar `registro.spec.ts` y verificar contra el backend en Docker (201 con número)
- [x] 1.3 Componente `estado-datos` (cargando, vacío, error con reintentar, modo demostración, cerrado) con sus textos en `comun.json`; prueba de cada estado
- [x] 1.4 Componentes `ficha-taller` (variantes galería, programa, inscripción) y `socios-taller`, etiquetas de nivel y público en `comun.json`; pruebas: taller sin foto muestra `PapelPlegado`, enlace de WhatsApp con `rel="noopener"`

## 2. Páginas estáticas (specs `frontend/recursos`, `frontend/pagina-no-encontrada`)

- [x] 2.1 `recursos.json` con los cinco documentos y el cronograma del sitio antiguo (es/en) y página `/recursos` (documentos + visor embebido + enlace alternativo); prueba: cada documento tiene enlace y botón correcto
- [x] 2.2 Destino `recursos` del menú → `recursos` y enlace «Ver recursos» en el itinerario de la home; prueba de navegación
- [x] 2.3 Página `no-encontrada` (es/en), rutas `**` y `en/**`, prerender de `/404` y `public/.htaccess` con `ErrorDocument`; verificar con `npm run preview` que una ruta inexistente responde 404 con la página

## 3. Amigos Plegadores (spec `frontend/amigos-plegadores`)

- [x] 3.1 `amigos-plegadores.json` con los cuatro apartados (es del original, en nuevo) y fotos de `originales/congreso/images/ap` exportadas a WebP en `public/assets/ap/`
- [x] 3.2 Página con pasos I–IV y galería «El parche» en `<dialog>` con flechas y Escape; pruebas: textos en ambos idiomas, navegación con teclado de la galería
- [x] 3.3 Enlaces desde el menú del congreso y el pie

## 4. Programa (spec `frontend/programa`)

- [x] 4.1 `booklet.json` y página `/booklet` con `GET /events/schedule` agrupado por día y hora; pruebas con respuesta simulada: orden por día y hora, estado vacío
- [x] 4.2 Filtros día/nivel/público/salón/tallerista sincronizados con la URL y «Limpiar filtros»; pruebas: `?dia=domingo&nivel=A` filtra, sin coincidencias muestra el mensaje
- [x] 4.3 Estilos de impresión y botón «Imprimir»; verificación manual de la vista de impresión; prueba de que no se muestran participantes ni contactos

## 5. Talleres (spec `frontend/talleres`)

- [ ] 5.1 `talleres.json` (textos, talleres internacionales con `porConfirmar`) y página `/talleres` con Paso I (internacionales) y Paso II (galería de `GET /workshops` con vista ampliada); pruebas: aviso ✳, galería con y sin foto
- [ ] 5.2 Paso III espacio del tallerista: búsqueda por correo, bloque por persona, proponer/editar/retirar con validación en el navegador; pruebas: familia con dos personas, correo no inscrito, duración fuera de rango no se envía
- [ ] 5.3 Foto del taller (tipo y tamaño en el navegador, vista previa, error 422 del backend) y selección de socios (`GET /partners` + `PUT /workshops/{id}/partners`); pruebas de cada caso
- [ ] 5.4 Verificación contra el backend en Docker: proponer, subir foto, asociar socio y verlo en la galería

## 6. Inscripción a talleres (spec `frontend/inscripcion-talleres`)

- [ ] 6.1 `inscribir-talleres.json` e interruptor `inscripcionTalleresAbierta`; Paso I verificación (`verify-code`) con mensaje de turno en `aria-live`, sesión en `sessionStorage`, «Salir» y «¿Olvidaste tu número?» (`remind-code`); pruebas de cada respuesta
- [ ] 6.2 Paso II talleres de hoy (`GET /events/active`) por franja con filtros y estados de cada taller; 403 `logout` limpia la sesión; pruebas con respuestas simuladas
- [ ] 6.3 Inscribirse y cancelar con confirmación, Paso III «mis talleres» y aviso de dos partes; pruebas: choque 409 muestra su mensaje, dos partes aparecen juntas
- [ ] 6.4 Verificación contra el backend en Docker con `TURN_BYPASS=true`: verificar, inscribirse, cupo agotado, cancelar

## 7. Navegación, meta e integración

- [ ] 7.1 `menu.json` (destinos y listas `congreso`, `movil`, `pie`) y `meta.json` (título, descripción y OG de las seis páginas); prueba de que cada destino existe y de que cada texto tiene `es` y `en`
- [ ] 7.2 Interruptores de `secciones` para las páginas nuevas; prueba: `talleres: false` quita la ruta y sus enlaces
- [ ] 7.3 `npm test` y `npm run build` en verde, prerender de todas las rutas en ambos idiomas, y revisión en navegador en 1020 px, 760 px y `prefers-reduced-motion`
- [ ] 7.4 Actualizar `CLAUDE.md` (páginas nuevas, `core/api.ts`, interruptores) y verificar que los comandos documentados funcionan

## Workflow follow-up

- Revisar con el equipo las traducciones nuevas al inglés y el contenido 2027 de los talleres internacionales.
- Configurar `apiUrl` con la URL del backend desplegado antes de publicar.
- Archivar el cambio tras la revisión.
