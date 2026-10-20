import { Component } from '@angular/core';
import { traducir } from '@core/idioma';
import { Separador } from '@components/separador/separador';
import { Reveal } from '@core/reveal';
import contenido from '@contenido/acerca.json';

/* ============ ACERCA ============ */

@Component({
  selector: 'app-acerca',
  imports: [Separador, Reveal],
  templateUrl: './acerca.html',
  styleUrl: './acerca.css',
})
export class Acerca {
  protected readonly t = traducir(contenido);
}
