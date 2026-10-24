import { Component, inject } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';
import { Doblez } from '@components/doblez/doblez';
import { PaginaInterna } from '@components/pagina-interna/pagina-interna';
import { Separador } from '@components/separador/separador';
import recursos from '@contenido/recursos.json';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import { fijarMeta } from '@core/meta';
import { Reveal } from '@core/reveal';

/* ============ PÁGINA: RECURSOS ============
   Paso I: los documentos del congreso como «lista de piezas» del diagrama (numerada,
   con su tipo y su botón: «Abrir» para documentos en línea, «Descargar» para archivos).
   Paso II: el cronograma embebido con el visor de Google y, siempre visible, el enlace
   directo al PDF (por si el navegador o el visor no lo muestran).
   Contenido: contenido/recursos.json. */

@Component({
  selector: 'app-pagina-recursos',
  imports: [PaginaInterna, Doblez, Enlace, Reveal, Separador],
  templateUrl: './pagina-recursos.html',
  styleUrl: './pagina-recursos.css',
})
export class PaginaRecursos {
  protected readonly t = traducir(recursos);
  protected readonly tipos: Record<string, string> = this.t.tipos;
  /** La URL del visor sale de nuestro propio contenido (recursos.json), no de quien visita. */
  protected readonly visor = inject(DomSanitizer).bypassSecurityTrustResourceUrl(this.t.cronograma.visorUrl);

  constructor() {
    fijarMeta('recursos');
  }
}
