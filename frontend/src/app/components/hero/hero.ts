import { Component, computed, signal } from '@angular/core';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import comun from '@contenido/comun.json';
import evento from '@contenido/evento.json';
import { RedesSociales } from '@components/redes-sociales/redes-sociales';
import { Reveal } from '@core/reveal';
import { enlaceVisible } from '@core/navegacion';
import contenido from '@contenido/hero.json';

/* ============ HERO ============
   La hoja se pliega suavemente (rotateX) al hacer scroll. */

@Component({
  selector: 'app-hero',
  imports: [Enlace, RedesSociales, Reveal],
  templateUrl: './hero.html',
  styleUrl: './hero.css',
  host: { '(window:scroll)': 'alHacerScroll()' },
})
export class Hero {
  protected readonly t = traducir(contenido);
  protected readonly comun = traducir(comun);
  protected readonly evento = traducir(evento);
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
