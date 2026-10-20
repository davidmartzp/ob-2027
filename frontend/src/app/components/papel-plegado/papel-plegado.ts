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
  styleUrl: './papel-plegado.css',
  template: `
    <span class="sombra"></span>
    <span class="hoja">
      <span class="base"></span>
      <svg class="valle" viewBox="0 0 10 10" preserveAspectRatio="none"><line x1="0" y1="0" x2="10" y2="10"/></svg>
      <span class="solapa"></span>
    </span>
  `,
})
export class PapelPlegado {}
