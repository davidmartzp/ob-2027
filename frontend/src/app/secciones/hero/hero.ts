import { Component, computed, signal } from '@angular/core';
import { enlaceVisible, EVENTO, INSCRIPCIONES_ABIERTAS } from '../../datos/evento';
import { RedesSociales } from '../../compartido/redes-sociales';
import { Reveal } from '../../compartido/reveal';

/* ============ HERO ============
   La hoja se pliega suavemente (rotateX) al hacer scroll. */

@Component({
  selector: 'app-hero',
  imports: [RedesSociales, Reveal],
  templateUrl: './hero.html',
  styleUrl: './hero.css',
  host: { '(window:scroll)': 'alHacerScroll()' },
})
export class Hero {
  protected readonly evento = EVENTO;
  protected readonly inscripcionesAbiertas = INSCRIPCIONES_ABIERTAS;
  protected readonly itinerarioVisible = enlaceVisible('#itinerario');

  private readonly avance = signal(0);
  protected readonly pliegue = computed(() => {
    const p = this.avance();
    return p === 0 ? null : `perspective(1400px) rotateX(${p * 7}deg) translateY(${p * -26}px)`;
  });
  protected readonly opacidad = computed(() => (this.avance() === 0 ? null : 1 - this.avance() * .3));

  protected alHacerScroll(): void {
    this.avance.set(Math.min(scrollY / innerHeight, 1));
  }
}
