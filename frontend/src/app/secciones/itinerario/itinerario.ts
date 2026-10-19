import { Component } from '@angular/core';
import { Separador } from '../../compartido/separador';
import { Reveal } from '../../compartido/reveal';
import { ItinerarioModal } from './itinerario-modal';

/* ============ ITINERARIO (hoja de páramo) ============ */

@Component({
  selector: 'app-itinerario',
  imports: [Separador, Reveal, ItinerarioModal],
  templateUrl: './itinerario.html',
  styleUrl: './itinerario.css',
})
export class Itinerario {}
