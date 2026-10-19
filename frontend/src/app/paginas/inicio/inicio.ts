import { Component } from '@angular/core';
import { fijarMeta } from '../../compartido/meta';
import { SECCIONES_VISIBLES } from '../../datos/evento';
import { Doblez } from '../../compartido/doblez';
import { Acerca } from '../../secciones/acerca/acerca';
import { Actividades } from '../../secciones/actividades/actividades';
import { Comunidad } from '../../secciones/comunidad/comunidad';
import { Hero } from '../../secciones/hero/hero';
import { Invitados } from '../../secciones/invitados/invitados';
import { Itinerario } from '../../secciones/itinerario/itinerario';
import { Marquee } from '../../secciones/marquee/marquee';
import { Patrocinadores } from '../../secciones/patrocinadores/patrocinadores';
import { Tallerista } from '../../secciones/tallerista/tallerista';

/* ============ LA HOJA (página de inicio) ============
   Página única: el orden de las secciones y sus dobleces vive aquí.
   Ritmo de fondos: nunca dos secciones oscuras/de color seguidas. */

@Component({
  selector: 'app-inicio',
  imports: [Doblez, Hero, Marquee, Acerca, Comunidad, Itinerario, Invitados, Actividades, Tallerista, Patrocinadores],
  template: `
    <main>
      <app-hero/>
      <app-marquee/>
      <app-acerca/>
      @if (visibles.comunidad) {
        <app-comunidad/>
      }

      @if (visibles.itinerario) {
        <app-doblez fondo="var(--paramo)" tinta="var(--paramo-txt)"/>
        <app-itinerario/>
      }

      <app-invitados/>

      @if (visibles.actividades) {
        <app-doblez fondo="var(--frailejon)" montana/>
        <app-actividades/>
      }

      @if (visibles.tallerista) {
        <app-doblez fondo="var(--ruana)"/>
        <app-tallerista/>
      }

      @if (visibles.patrocinadores) {
        <app-patrocinadores/>
      }
    </main>
  `,
})
export class Inicio {
  protected readonly visibles = SECCIONES_VISIBLES;

  constructor() {
    fijarMeta(
      $localize`:@@meta.titulo:Origami Bogotá 2027 — El finde más plegado del año`,
      $localize`:@@meta.descripcion:Congreso de Origami en Colombia. 16 años celebrando el arte del origami. Liceo Francés Louis Pasteur, Bogotá. Edición 2027.`,
    );
  }
}
