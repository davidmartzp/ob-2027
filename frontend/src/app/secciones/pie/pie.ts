import { Component } from '@angular/core';
import { enlaceVisible, EVENTO, INSCRIPCIONES_ABIERTAS, NAV_PIE } from '../../datos/evento';
import { Enlace } from '../../compartido/enlace';
import { RedesSociales } from '../../compartido/redes-sociales';

/* ============ FOOTER — contacto e inscripciones (#inscribete) ============ */

@Component({
  selector: 'app-pie',
  imports: [Enlace, RedesSociales],
  templateUrl: './pie.html',
  styleUrl: './pie.css',
})
export class Pie {
  protected readonly evento = EVENTO;
  protected readonly inscripcionesAbiertas = INSCRIPCIONES_ABIERTAS;
  protected readonly enlaces = NAV_PIE.filter((e) => enlaceVisible(e.href));
}
