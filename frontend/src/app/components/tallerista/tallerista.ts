import { Component } from '@angular/core';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import { Separador } from '@components/separador/separador';
import { Reveal } from '@core/reveal';
import evento from '@contenido/evento.json';
import contenido from '@contenido/tallerista.json';

/* ============ TALLERISTA ============
   El botón abre un correo a evento.correo (contenido/evento.json) con el asunto ya escrito. */

@Component({
  selector: 'app-tallerista',
  imports: [Enlace, Separador, Reveal],
  templateUrl: './tallerista.html',
  styleUrl: './tallerista.css',
})
export class Tallerista {
  protected readonly t = traducir(contenido);
  protected readonly correo = `mailto:${evento.correo}?subject=${encodeURIComponent(this.t.asunto)}`;
}
