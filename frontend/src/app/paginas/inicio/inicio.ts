import { NgComponentOutlet } from '@angular/common';
import { Component, Type } from '@angular/core';
import { Doblez } from '@components/doblez/doblez';
import { fijarMeta } from '@core/meta';
import { seccionVisible } from '@core/navegacion';
import { Acerca } from '@components/acerca/acerca';
import { Actividades } from '@components/actividades/actividades';
import { Comunidad } from '@components/comunidad/comunidad';
import { Hero } from '@components/hero/hero';
import { Invitados } from '@components/invitados/invitados';
import { Itinerario } from '@components/itinerario/itinerario';
import { Marquee } from '@components/marquee/marquee';
import { Patrocinadores } from '@components/patrocinadores/patrocinadores';
import { Tallerista } from '@components/tallerista/tallerista';

/* ============ LA HOJA (página de inicio) ============
   El orden de las secciones vive en HOJA. Una sección con `fondo` va precedida de un
   doblez de ese color (valle y montaña se alternan solos). Si su id está en
   «secciones» de contenido/evento.json, ese interruptor la oculta junto con su doblez.
   Ritmo de fondos: nunca dos secciones de color seguidas (lo comprueba app.spec.ts). */

export interface BloqueHoja {
  id: string;
  componente: Type<unknown>;
  /** Fondo de la sección, si no es papel. El mismo que pone su propio CSS. */
  fondo?: string;
  /** Color de la línea del doblez sobre ese fondo. */
  tinta?: string;
}

export const HOJA: BloqueHoja[] = [
  { id: 'hero', componente: Hero },
  { id: 'marquee', componente: Marquee },
  { id: 'acerca', componente: Acerca },
  { id: 'comunidad', componente: Comunidad },
  { id: 'itinerario', componente: Itinerario, fondo: 'var(--paramo)', tinta: 'var(--paramo-txt)' },
  { id: 'invitados', componente: Invitados },
  { id: 'actividades', componente: Actividades, fondo: 'var(--frailejon)' },
  { id: 'tallerista', componente: Tallerista, fondo: 'var(--ruana)' },
  { id: 'patrocinadores', componente: Patrocinadores },
];

/** Las secciones visibles, cada una con su doblez (si lo lleva). */
export function bloquesVisibles(hoja: BloqueHoja[] = HOJA): (BloqueHoja & { doblez?: { fondo: string; tinta: string; montana: boolean } })[] {
  let dobleces = 0;
  return hoja
    .filter((b) => seccionVisible(b.id))
    .map((b) => (b.fondo ? { ...b, doblez: { fondo: b.fondo, tinta: b.tinta ?? 'var(--tinta)', montana: dobleces++ % 2 === 1 } } : b));
}

@Component({
  selector: 'app-inicio',
  imports: [Doblez, NgComponentOutlet],
  template: `
    <main>
      @for (b of bloques; track b.id) {
        @if (b.doblez; as d) {
          <app-doblez [fondo]="d.fondo" [tinta]="d.tinta" [montana]="d.montana"/>
        }
        <ng-container *ngComponentOutlet="b.componente"/>
      }
    </main>
  `,
})
export class Inicio {
  protected readonly bloques = bloquesVisibles();

  constructor() {
    fijarMeta();
  }
}
