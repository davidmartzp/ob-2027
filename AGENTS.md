# AGENTS.md — Origami Bogotá 2027

Guía para agentes de IA que trabajen en este proyecto. Léela completa antes de tocar código.

## 1. Descripción del proyecto

Sitio web de una sola página (landing) para el **Congreso Origami Bogotá 2027** (edición N.º 16), evento de la comunidad de origami de Bogotá, Colombia, los días **04–06 de junio de 2027** en el Liceo Francés Louis Pasteur. El contenido es 100 % estático, en **español (colombiano)**.

Concepto rector — **«El Diagrama»**: el sitio se comporta como una hoja de instrucciones de origami. Cada sección es un paso numerado (Paso 00–06), el lenguaje gráfico replica la notación de los diagramas de plegado (líneas de valle y montaña, flechas de doblar/desdoblar/girar, etiquetas `FIG. 0X`) y la página tiene presencia física de papel (grano, dobleces, cinta adhesiva, sombras duras).

## 2. Estructura del repositorio

```
OB-2027/
├── AGENTS.md                                  ← este archivo
├── Sistema de diseño — Origami Bogotá 2027.md ← FUENTE DE VERDAD del sistema de diseño
└── app/
    ├── index.html                             ← TODO el sitio (HTML + CSS + JS inline, 1220 líneas)
    └── assets/                                ← imágenes (JPG/PNG, referenciadas con ruta relativa)
        ├── act-chocogami.jpg, act-explora.jpg, act-expo.jpg, act-rifas.jpg
        ├── foto-comunidad.jpg
        ├── inv-1.png, inv-2.png, inv-3.png
        ├── origami-frailejon.png, origami-tigre.png
        └── taller-manos.png
```

**No hay ningún archivo de configuración**: no existe `package.json`, `pyproject.toml`, `Cargo.toml` ni similar. No hay dependencias, ni gestor de paquetes, ni paso de compilación. El CSS vive en dos bloques `<style>` dentro de `index.html` (tokens/utilidades + componentes) y el JavaScript en un único bloque `<script>` al final del `<body>`.

## 3. Cómo ejecutar y verificar

- **Vista previa**: sirve o abre `app/index.html` directamente en el navegador (por ejemplo `open app/index.html`, o `python3 -m http.server` desde `app/`).
- **Build**: no existe. El HTML entregado es el código de producción.
- **Tests**: no hay suite de tests automatizados. La verificación es manual en navegador:
  - Sección por sección (Hero → Acerca → Comunidad → Itinerario → Invitados → Actividades → Tallerista → Patrocinadores → Leyenda → Footer).
  - Menú desplegable «El congreso» (abre/cierra con clic fuera y Escape), menú móvil (≤760px), reveals al hacer scroll, vista flotante que sigue el cursor sobre las actividades, avioncito de papel animado con el scroll.
  - Responsivo en los breakpoints **1020px** y **760px**.
  - `prefers-reduced-motion: reduce` desactiva las animaciones.

## 4. Sistema de diseño (reglas obligatorias)

El documento `Sistema de diseño — Origami Bogotá 2027.md` es el contrato de diseño y está vigente. Cambios visuales empiezan siempre por las variables de `:root` en `index.html`. Resumen de lo innegociable:

- **Paleta fija** (no inventar colores): `--papel #F5F0E0`, `--papel-2 #EBE3C9`, `--tinta #27251B`, `--ruana #EBA937`, `--miel #A8740D`, `--pajizo #D9A92B`, `--frailejon #6C8B67`, `--paramo #33454F`, `--paramo-txt #ECE7D8`, más tintas con alfa (`--tinta-60/30/12`).
- **Dirección «Páramo al amanecer»**: `--ruana` solo como *relleno* con texto oscuro; `--miel` como *texto* de acento sobre fondos claros; `--pajizo` como acento brillante sobre lo oscuro.
- **Ritmo de fondos**: claro por defecto; las secciones oscuras/de color son «dobleces» de ritmo, nunca dos seguidas del mismo tono. Entre secciones van transiciones `.doblez`.
- **Tipografías** (Google Fonts): Fraunces (`--serif`, display), Instrument Sans (`--sans`, cuerpo), IBM Plex Mono (`--mono`, etiquetas de diagrama: mayúsculas, tracking .14–.22em), Caveat (`--mano`, anotaciones a mano, máximo una por sección).
- **Vocabulario del diagrama**: cada `.paso-tag` lleva su símbolo SVG (valle `stroke-dasharray: 4 3`, montaña `1.5 2.5 5 2.5`, flechas con animación `correr`). La franja `.leyenda` antes del footer documenta los cinco símbolos: si se añade uno nuevo, registrarlo ahí.
- **Sombras duras sin blur** (papel recortado, nunca sombras SaaS), grano de papel global en `body::after`, imágenes generadas con `mix-blend-mode: multiply`.
- **Movimiento**: curva de marca `--ease: cubic-bezier(.32,.94,.6,1)`; reveals con IntersectionObserver escalonados por `data-delay`; todo respeta `prefers-reduced-motion`.
- **Honestidad de contenido**: todo dato no confirmado lleva aviso visible (`✳ listado provisional`, `.pend-nota`). Mientras los invitados sean «Por confirmar», sus retratos son esculturas de papel plegado.
- **Microcopy rolo**: tono cercano y festivo («sumercé», «tinto», «ruana»), nunca corporativo.

## 5. Convenciones de código

- Un solo archivo HTML autocontenido: estilos en los bloques `<style>` del `<head>`, scripts al final del `<body>`. Sin frameworks, sin preprocessors, vanilla JS sin módulos.
- Identificadores, clases y comentarios del código en **español**; comentarios en bloques por sección (`/* ============ HERO ============ */`).
- Rutas de assets relativas (`assets/...`).
- Accesibilidad requerida: foco visible, `aria-expanded`/`aria-haspopup` en el desplegable, cierre con Escape, textos `alt` descriptivos, contraste verificado sobre cada fondo de color.

## 6. Cómo extender el sitio (patrones documentados)

1. **Nueva actividad** → duplicar una fila `<li>` con `.actividad` en el Paso 05 (`app/index.html:982`); elegir símbolo valle/montaña, `.chip` de categoría y `data-img="assets/..."` (foto) o `data-fig` (figura SVG de respaldo; claves disponibles en el objeto `figuras` del script: `grulla`, `rana`, `zorro`, `pez`, `tigre`). La lista escala copiando el bloque, no es una grilla fija.
2. **Nueva página del menú** → añadir el `<a>` con `data-n` correlativo dentro de `.despliegue .panel` (línea ~703) **y** en `nav.movil` (línea ~721). Toda página nueva entra ahí.
3. **Nuevo invitado** → duplicar una `.invitado-card` con numeración `INV.0X` correlativa; mantener 2–3 visibles.
4. **Nueva sección** → asignarle número de Paso, símbolo de diagrama, precederla con `.doblez` (o `.doblez.montana`) y elegir fondo de la paleta respetando el ritmo claro/oscuro.

## 7. Seguridad y despliegue

- No hay secretos, backend ni formularios activos: los enlaces de inscripción son `mailto:`/`tel:` y los de redes/apóyenos/apertura de mapa son externos (`target="_blank" rel="noopener"` donde aplica).
- Despliegue: basta publicar la carpeta `app/` en cualquier hosting estático. No hay CI/CD configurado.
- Al publicar, verificar que las rutas `assets/` respetan mayúsculas/minúsculas (servidores Linux son sensibles a ellas).

## 8. Estado conocido / deuda

- Los nombres de invitados y varios datos son **provisionales** y así están marcados en el contenido.
- Enlaces sociales del footer y de contacto rápido con `href="#"` pendientes de URL real.
- El CSS tiene un comentario residual en la línea 28 (`--miel`) que mezcla el nombre antiguo con el nuevo; los tokens vigentes son los del documento de diseño.
