# Sistema de diseño — Origami Bogotá 2027

**Concepto:** *El Diagrama*. El sitio es una hoja de instrucciones de origami: cada sección es un paso numerado (Paso 00–06), los gráficos hablan el lenguaje de los diagramas (líneas de doblez en valle y montaña, flechas de doblar/desdoblar/girar, etiquetas FIG., patrones de pliegues) y el papel tiene presencia física — grano, dobleces con sombra, bordes plegados, cinta adhesiva.

**Identidad rola:** la edición 2027 se siente Bogotá — paleta de ruana y páramo, frailejón de papel como mascota, franja tejida tipo ruana, cordillera de cerros, y microcopys cercanos («¡póngase la ruana, sumercé!»).

**Tono:** cercano, fraterno, festivo. Nada académico ni corporativo. La comunidad es la protagonista; la información es provisional y se dice con honestidad («✳ listado provisional — nombres por confirmar»).

---

## 1. Color

Todo color sale de la paleta. No se inventan colores nuevos; las variantes (estados, énfasis, fondos) se derivan de estos mismos valores.

| Token | Valor | Nombre | Uso |
|---|---|---|---|
| `--papel` | `#F5F0E0` | Crema amanecer | Fondo base del sitio |
| `--papel-2` | `#EBE3C9` | Crema en sombra | Fondos secundarios, sello de fecha |
| `--tinta` | `#27251B` | Carbón | Texto principal, bordes, líneas de diagrama |
| `--tinta-60` | `rgba(29,27,20,.6)` | — | Texto secundario |
| `--tinta-30` | `rgba(29,27,20,.3)` | — | Líneas suaves, bordes discontinuos |
| `--tinta-12` | `rgba(29,27,20,.12)` | — | Divisores, patrones de fondo |
| `--ruana` | `#EBA937` | Sol de amanecer | **Acento principal en rellenos**: botones, sección tallerista, sombras duras, franja marquee. Siempre con texto `--tinta` |
| `--miel` | `#A8740D` | Miel profunda | **Acento de texto** sobre fondos claros: itálicas de títulos, ✳, FIG., anotaciones a mano |
| `--pajizo` | `#D9A92B` | Dorado pajizo | Flor del frailejón, sombras de paneles y fotos, texto sobre fondos oscuros |
| `--frailejon` | `#6C8B67` | Verde fresco | Sección de actividades, detalles botánicos |
| `--paramo` | `#33454F` | Azul pizarra de alborada | Secciones oscuras (itinerario, footer), franja marquee |
| `--paramo-txt` | `#ECE7D8` | — | Texto sobre fondos `--paramo` |

> **Dirección «Páramo al amanecer»:** más luz que la versión ruana/terracota. Regla de oro: `--ruana` (sol) solo como *relleno* con texto oscuro; `--miel` como *texto* de acento; `--pajizo` como acento brillante sobre lo oscuro.

**Reglas:**
- Fondo claro por defecto (`--papel`); las secciones oscuras (`--paramo`) y de color (`--frailejon`, `--ruana`) funcionan como «dobleces» de ritmo, nunca dos seguidas del mismo tono.
- Sobre `--paramo` el texto es `--paramo-txt`; sobre `--ruana` y `--frailejon` se usa `--papel` o `--tinta` según contraste.
- `::selection` usa `--ruana` con texto `--papel`.

## 2. Tipografía

Cargadas desde Google Fonts (`display=swap`):

| Token | Familia | Rol |
|---|---|---|
| `--serif` | **Fraunces** (opsz 9–144, 300–900, con itálicas) | Display: títulos de sección, héroe, nombres de invitados. La itálica en `--ruana` es el énfasis de marca |
| `--sans` | **Instrument Sans** (400–700) | Texto corrido, descripciones |
| `--mono` | **IBM Plex Mono** (400, 500) | Etiquetas de diagrama: `PASO 00 —`, coordenadas, chips, leyendas, numeración. Siempre en mayúsculas con `letter-spacing` amplio (.14–.2em) |
| `--mano` | **Caveat** (500, 700) | Anotaciones a mano al margen, como notas en un diagrama real («¡tu modelo puede ser el próximo paso!») |

**Escala (clamp, fluida):**
- Héroe: tres filas — palabra en `--serif` negrita, palabra en itálica `--ruana`, año en contorno (`-webkit-text-stroke`).
- Títulos de sección `h2.titulo`: `--serif`, con `em` en itálica acentuada.
- Nombre de invitado: `clamp(30px, 5vw, 68px)`.
- Etiquetas `--mono`: 10–11px, mayúsculas, tracking .16–.18em.
- Cuerpo: `--sans` 15–17px, interlineado 1.55–1.65.

**Reglas:** chino no aplica; evitar itálica mecánica — la itálica real viene de Fraunces. Las anotaciones `--mano` se usan con moderación: una por sección como máximo.

## 3. El vocabulario del diagrama

El lenguaje gráfico distintivo del sistema. SVG inline, heredan `currentColor`.

### Símbolos (`.simb`, 22×16px)
- **Valle** (`.valle`): línea segmentada `stroke-dasharray: 4 3` — «doblar hacia adentro».
- **Montaña** (`.montana`): punto-raya `stroke-dasharray: 1.5 2.5 5 2.5` — «doblar hacia afuera».
- **Doblar / Desdoblar / Girar**: flechas curvas (`.flecha`), con animación `correr` (dashoffset infinito, 1.2s lineal) para sugerir movimiento.

### Uso obligatorio
- Cada `.paso-tag` lleva su símbolo: Paso 01 valle · Paso 02 doblar · Paso 03 montaña · Paso 04 desdoblar · Paso 05 girar · Paso 06 valle + montaña.
- Cada fila de actividad lleva un marcador de doblez (rombo o triángulo) en valle o montaña.
- La franja `.leyenda` («Clave del diagrama —») documenta los cinco símbolos antes del footer: es la «leyenda del mapa» del sitio.

### Etiquetas de diagrama
- `.paso-tag`: mono, mayúsculas, `PASO 0X — NOMBRE DEL PASO`, precedido por línea discontinua.
- `FIG. 0X` para citas, figuras y retratos de invitados (color `--miel`).
- Coordenadas del héroe: `4.6680° N, 74.0536° W / Bogotá, Colombia · 2.600 msnm`.

## 4. Papel y profundidad física

La página no es plana: es una hoja.

- **Grano de papel** (`body::after`): SVG `feTurbulence` fijo a pantalla completa, `mix-blend-mode: multiply`, opacidad .5. Envuelve todo.
- **Patrones de pliegues** (crease patterns): fondos SVG inline con diagonales y ejes en dasharray valle/montaña, opacidad .14, en secciones oscuras y en el héroe (`.cp`).
- **`.doblez`** (58px): transición física entre secciones — cara de luz (`::before`) + cara de sombra (`::after`) + línea de doblez `.linea` discontinua. Variante `.doblez.montana` con patrón punto-raya. Cada sección «nace» de un doblez de la anterior.
- **Sombra de centro de hoja** en el héroe (`hero::before/::after`) y **pliegue 3D con scroll**: `perspective(1400px) rotateX(0→7deg) translateY(0→-26px)`.
- **`.pliegues-foto`**: sobre la foto de comunidad, dos diagonales con gradientes de sombra/luz, como si la foto estuviera doblada.
- **`.cinta`**: trozos de cinta adhesiva (clip-path polygon) pegando la foto — nada de marcos perfectos.
- **Sombras duras** (sin blur): `box-shadow: 8px 8px 0 var(--pajizo)` (panel del menú), `14px 14px 0 var(--ruana)` (vista flotante), `16px 16px 0 var(--paramo)` (foto de tallerista). Es sombra de papel recortado, no de interfaz.

## 5. Componentes

### Botones (`.btn`)
- Mono, mayúsculas, tracking amplio.
- `.btn-naranja`: relleno `--ruana` (CTA principal: Inscríbete).
- `.btn-linea`: solo borde (secundario).
- `.btn-claro` / `.btn-noche`: variantes sobre fondos oscuros.
- Hover: desplazamiento con sombra dura, nunca cambio de opacidad suave tipo SaaS.

### Navegación
- `header`: transparente sobre el héroe; `.solido` al hacer scroll; `.oculto` al bajar, reaparece al subir.
- **Menú desplegable `.despliegue`** («El congreso»): botón con `aria-expanded`/`aria-haspopup`; `.panel` con las páginas numeradas (`data-n`), borde `--tinta` y sombra dura `--pajizo`; aparece con `rotateX(-14deg)→0` (como desplegar un pliegue). Cierra con clic fuera y Escape. **Toda página nueva del sitio entra aquí.**
- `nav.movil`: oculto por defecto (`display:none`), visible ≤760px con menú hamburguesa que lista todas las páginas.

### Franja ruana (`.marquee`)
- Fondo `--paramo`, rotada `-1.2deg`, con franjas tejidas arriba y abajo (`::before/::after`): `repeating-linear-gradient` de `--ruana`/`--frailejon`/`--papel`/`--paramo` — el tejido de la ruana al amanecer.
- Texto en loop: «Plegamos juntos / 16 años de comunidad / 04—06 jun · Bogotá · 2.600 msnm / Ruana, papel y tinto / El finde más plegado del año».

### Filas de lista (actividades)
- División por líneas, **no tarjetas**. Título `--serif` grande + descripción `--sans` + `.chip` de categoría (mono, borde discontinuo, píldora) + «Ver detalles».
- `:has()` hover: las filas hermanas se atenúan para enfocar la activa.
- `.vista-flotante`: preview que sigue al cursor. Dos modos — `modo-foto` (foto real de la actividad) y modo figura (SVG de origami como respaldo cuando no hay foto). Crece de `scale(.85) rotate(-4deg)` con `--ease`.
- **Las actividades escalan**: agregar la fila N+1 es copiar el bloque; el layout es una lista, no una grilla fija.

### Tarjetas de invitado (`.invitado-card`)
- Exactamente 2–3 invitados, cada uno con **fotografía visible y biografía** — no una grilla de tarjetas idénticas.
- Layout alternado: foto a la izquierda en las tarjetas impares, a la derecha en las pares (`grid-template-columns` se invierte junto con `order`; la foto siempre vive en la columna estrecha `minmax(230px,330px)`).
- Foto: `aspect-ratio:1`, borde blanco 6px, sombra dura (`--pajizo` a la izquierda, `--ruana` a la derecha), rotación ±2°, `mix-blend-mode:multiply` para fundirse con el papel. Pie `FIG. 04-X — retrato provisional` en mono.
- Info: numeración `INV.0X` + etiqueta `.int`/`.nac`, nombre grande `--serif` (`Por confirmar` mientras no haya nombres), `.rol` en itálica y `.bio` en `--sans` con nota `.pend-nota` («✳ biografía provisional…»).
- Mientras los nombres sean provisionales, los retratos son **esculturas de papel plegado** (grulla, rana, zorro) — coherentes con el concepto y honestos con el visitante.
- Cierre: `.nota-invitados` en `--mano` («son solo 2 o 3 este año — pocas manos, mucho papel…»).

### Sello de fecha (`.hero-fecha`)
- Bloque tipo sello de diagrama en el héroe: borde discontinuo `--tinta-30`, fondo `--papel-2`, sombra dura `--pajizo`, rotación -1.2°. Contiene etiqueta mono `Fecha — vie a dom`, días grandes `--serif` (**04 · 05 · 06**) y `de junio, 2027` en mono `--miel`. La fecha también vive en el marquee y en el footer (`.f-fecha`).

### Avioncito de papel (`.avion`)
- SVG fijo (54px; 34px en móvil) que **vuela con el scroll**: su posición se calcula del progreso de la página (barridos laterales en coseno, altura en seno), con rotación según la tangente de la trayectoria, vaivén sutil y suavizado por interpolación en cada frame.
- Aparece al empezar a bajar (`scrollY > 40` → `.volando`) y se oculta al volver arriba. Desactivado con `prefers-reduced-motion`. Sombra `drop-shadow` dura, `pointer-events:none`, z-index 80.

### Secciones con ritmo
Acerca (papel) → Comunidad (foto a sangre completa con `.titulo-flotante` gigante encima) → Itinerario (`--paramo` oscuro + `.cerros` cordillera SVG + `.mega` «2027» en contorno) → Invitados (papel) → Actividades (`--frailejon`) → Tallerista (`--ruana` + foto con borde blanco rotada 2.5°) → Patrocinadores (lista tipográfica con separadores ✳) → Leyenda → Footer (`--paramo`, `.footer-mega` en contorno).

## 6. Movimiento

- Curva de marca: `--ease: cubic-bezier(.32,.94,.6,1)` — arranque decidido, aterrizaje suave (un papel que cae doblado).
- `.reveal` → `.in`: IntersectionObserver, `translateY` + opacidad, escalonado con `data-delay="1..3"`.
- Flechas del diagrama: `correr` (dashoffset infinito, 1.2s).
- Héroe: pliegue 3D ligado al scroll (sección 4).
- **`@media (prefers-reduced-motion: reduce)`**: se desactivan animaciones y reveals.

## 7. Layout y responsivo

- Margen lateral fluido: `--gutter: clamp(20px, 4vw, 64px)`.
- Radio de marca: `--marron: 12px` (botones y superficies pequeñas).
- Breakpoints: **1020px** (grillas a una columna, héroe apilado) y **760px** (nav móvil, tipografías contenidas).
- Accesibilidad: foco visible, `aria-expanded` en el desplegable, `aria-haspopup`, cierre con Escape, contraste verificado sobre cada fondo de color, movimiento reducido respetado.

## 8. Contenido e imágenes

- **Fotos reales primero**: la foto grupal de la comunidad y las fotos de actividades/talleres son el corazón del sitio — nunca reemplazarlas por ilustración genérica. Las imágenes generadas (frailejón, manos de taller) usan `mix-blend-mode: multiply` para fundirse con el papel.
- **Datos provisionales marcados**: todo dato no confirmado lleva aviso visible (`✳ listado provisional`, `.pend`). Honestidad antes que relleno.
- **Microcopy rolo**: «sumercé», «tinto», «ruana», «páramo» — calidez bogotana sin caer en caricatura.

## 9. Cómo extender el sistema

1. **Nueva actividad** → duplicar una fila `.actividad` en el Paso 05; elegir símbolo valle/montaña, chip de categoría y `data-img` (foto) o `data-fig` (figura SVG de respaldo).
2. **Nueva página del menú** → añadir `<a>` con `data-n` siguiente dentro de `.despliegue .panel` y en `nav.movil`.
3. **Nuevo invitado** → duplicar una `.invitado-card` con numeración correlativa, foto, rol y bio; mantener 2–3 visibles, el resto espera confirmación.
4. **Nueva sección** → asignar número de Paso, elegir símbolo de diagrama, preceder con `.doblez` (o `.doblez.montana`), elegir fondo de la paleta respetando el ritmo claro/oscuro, y registrar el símbolo en `.leyenda` si es nuevo.

---

*Documentado a partir del CSS en producción (`index.html`). Paleta «Páramo al amanecer» vigente desde la edición con fecha 04–06 jun 2027. Los tokens anteriores son la fuente de verdad: cualquier cambio visual empieza por las variables de `:root`.*
