import { Component } from '@angular/core';

/* ============ PAPEL PLEGADO (icono de las actividades) ============
   Un cuadrado doblado por la diagonal: cerrado es un triángulo; al abrir, la
   solapa gira 180° sobre la diagonal (rotate3d) y queda el cuadrado con su
   línea de valle. Se abre solo cuando la ficha que lo contiene está abierta
   (details[open], vía :host-context); sin animación de hover, a propósito.
   La sombra dura es una capa propia (.sombra) y no un filter: un filter en un
   ancestro aplana el 3D en Safari y la solapa dejaría de verse girar.
   Uso: <app-papel-plegado/> dentro de un contenedor con ancho. */

@Component({
  selector: 'app-papel-plegado',
  host: { 'aria-hidden': 'true' },
  template: `
    <span class="sombra"></span>
    <span class="hoja">
      <span class="base"></span>
      <svg class="valle" viewBox="0 0 10 10" preserveAspectRatio="none"><line x1="0" y1="0" x2="10" y2="10"/></svg>
      <span class="solapa"></span>
    </span>
  `,
  styles: `
    :host{ display:block; position:relative; width:100%; aspect-ratio:1; }
    .sombra, .hoja, .base, .solapa, .valle{ position:absolute; inset:0; width:100%; height:100%; display:block; }

    /* sombra dura: el triángulo cerrado, o el cuadrado entero cuando se abre */
    .sombra{
      background:var(--tinta); transform:translate(3px, 3px);
      clip-path:polygon(0 0, 0 100%, 100% 100%, 0 100%);
      transition:clip-path .7s var(--ease);
    }
    .hoja{ perspective:240px; }
    .base{ background:var(--ruana); clip-path:polygon(0 0, 0 100%, 100% 100%); }
    .solapa{
      background:var(--pajizo); clip-path:polygon(0 0, 100% 0, 100% 100%);
      transform:rotate3d(1, 1, 0, 180deg); transition:transform .7s var(--ease);
    }
    .valle{ overflow:visible; opacity:0; transition:opacity .3s; }
    .valle line{ stroke:var(--tinta); stroke-width:1.2px; stroke-dasharray:4 3; vector-effect:non-scaling-stroke; }

    :host-context(details[open]) .solapa{ transform:rotate3d(1, 1, 0, 0deg); }
    :host-context(details[open]) .sombra{ clip-path:polygon(0 0, 100% 0, 100% 100%, 0 100%); }
    :host-context(details[open]) .valle{ opacity:1; transition-delay:.45s; }
  `,
})
export class PapelPlegado {}
