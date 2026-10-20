import { Component } from '@angular/core';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import { Separador } from '@components/separador/separador';
import { Reveal } from '@core/reveal';
import evento from '@contenido/evento.json';
import { ItinerarioModal } from '@components/itinerario-modal/itinerario-modal';
import itinerario from '@contenido/itinerario.json';

/* ============ ITINERARIO (hoja de páramo) ============
   El enlace a «Recursos + Cronograma» solo aparece cuando evento.recursosUrl tiene una dirección. */

@Component({
  selector: 'app-itinerario',
  imports: [Enlace, Separador, Reveal, ItinerarioModal],
  templateUrl: './itinerario.html',
  styleUrl: './itinerario.css',
})
export class Itinerario {
  protected readonly t = traducir(itinerario.seccion);
  protected readonly edicion = evento.edicion;
  protected readonly recursosUrl = evento.recursosUrl;
}
