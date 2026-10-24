import { Component } from '@angular/core';
import { Doblez } from '@components/doblez/doblez';
import { FotoGaleria, GaleriaFotos } from '@components/galeria-fotos/galeria-fotos';
import { PaginaInterna } from '@components/pagina-interna/pagina-interna';
import { Separador } from '@components/separador/separador';
import contenido from '@contenido/amigos-plegadores.json';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import { fijarMeta } from '@core/meta';
import { Reveal } from '@core/reveal';

/* ============ PÁGINA: AMIGOS PLEGADORES ============
   Quiénes organizan el congreso: cuatro pasos del diagrama (origen, reuniones en
   AseisManos, evento anual, la chiva) que alternan papel / papel-2, y la galería
   «El parche». Contenido: contenido/amigos-plegadores.json; fotos en
   public/assets/ap/ (WebP: <nombre>.webp y la miniatura <nombre>-mini.webp). */

const ROMANOS = ['I', 'II', 'III', 'IV', 'V', 'VI'];

@Component({
  selector: 'app-pagina-amigos-plegadores',
  imports: [PaginaInterna, Doblez, Enlace, Reveal, Separador, GaleriaFotos],
  templateUrl: './pagina-amigos-plegadores.html',
  styleUrl: './pagina-amigos-plegadores.css',
})
export class PaginaAmigosPlegadores {
  protected readonly t = traducir(contenido);
  protected readonly romanos = ROMANOS;
  protected readonly fotos: FotoGaleria[] = this.t.parche.fotos.map((f) => ({
    mini: `assets/ap/${f.foto}-mini.webp`,
    grande: `assets/ap/${f.foto}.webp`,
    alt: f.alt,
  }));

  constructor() {
    fijarMeta('amigos-plegadores');
  }
}
