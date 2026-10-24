import { Component } from '@angular/core';
import { AvisoPago } from '@components/aviso-pago/aviso-pago';
import { EstadoDatos } from '@components/estado-datos/estado-datos';
import { FormularioInscripcion } from '@components/formulario-inscripcion/formulario-inscripcion';
import { MetodosPago } from '@components/metodos-pago/metodos-pago';
import { PaginaInterna } from '@components/pagina-interna/pagina-interna';
import { PreciosInscripcion } from '@components/precios-inscripcion/precios-inscripcion';
import { PreguntasFrecuentes } from '@components/preguntas-frecuentes/preguntas-frecuentes';
import { Doblez } from '@components/doblez/doblez';
import { Separador } from '@components/separador/separador';
import evento from '@contenido/evento.json';
import inscripciones from '@contenido/inscripciones.json';
import preguntas from '@contenido/preguntas.json';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import { fijarMeta } from '@core/meta';
import { Reveal } from '@core/reveal';

/* ============ PÁGINA: INSCRIPCIONES ============
   La del sitio anterior con el diseño nuevo: cómo inscribirse (dos pasos) →
   Paso I: precios y métodos de pago → Paso II: formulario (wizard) → preguntas frecuentes.
   Todo el contenido, en contenido/inscripciones.json y contenido/preguntas.json.
   Los botones «Inscríbete» del sitio enlazan aquí cuando evento.inscripcionesAbiertas es true;
   con false, el Paso II muestra el aviso «abre pronto» en lugar del formulario. */

@Component({
  selector: 'app-pagina-inscripciones',
  imports: [
    PaginaInterna, Doblez, Enlace, Separador, Reveal, AvisoPago,
    PreciosInscripcion, MetodosPago, FormularioInscripcion, PreguntasFrecuentes, EstadoDatos,
  ],
  templateUrl: './pagina-inscripciones.html',
  styleUrl: './pagina-inscripciones.css',
})
export class PaginaInscripciones {
  private readonly contenido = traducir(inscripciones);
  protected readonly t = this.contenido.pagina;
  protected readonly precios = this.contenido.precios;
  protected readonly metodos = this.contenido.metodosPago;
  protected readonly formulario = this.contenido.formulario;
  protected readonly preguntas = traducir(preguntas);
  /** El anuncio «¡abiertas!» y el formulario solo si lo están de verdad (evento.inscripcionesAbiertas). */
  protected readonly abiertas = evento.inscripcionesAbiertas;

  constructor() {
    fijarMeta('inscripciones');
  }

  protected irA(id: string): void {
    document.getElementById(id)?.scrollIntoView({ block: 'start' });
  }
}
