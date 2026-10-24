import { afterNextRender, Component, computed, ElementRef, signal, viewChild } from '@angular/core';
import { Doblez } from '@components/doblez/doblez';
import { EspacioTallerista } from '@components/espacio-tallerista/espacio-tallerista';
import { EstadoDatos } from '@components/estado-datos/estado-datos';
import { FichaTaller } from '@components/ficha-taller/ficha-taller';
import { PaginaInterna } from '@components/pagina-interna/pagina-interna';
import { PapelPlegado } from '@components/papel-plegado/papel-plegado';
import { Separador } from '@components/separador/separador';
import comun from '@contenido/comun.json';
import talleres from '@contenido/talleres.json';
import { api } from '@core/api';
import { DatosVivos } from '@core/datos-vivos';
import { Enlace } from '@core/enlace';
import { rellenar, traducir } from '@core/idioma';
import { fijarMeta } from '@core/meta';
import { NivelTaller, PublicoTaller, Taller } from '@core/modelos';
import { seccionVisible } from '@core/navegacion';
import { Reveal } from '@core/reveal';
import { desdeGaleria, TallerGaleriaApi } from '@core/talleres';

/* ============ PÁGINA: TALLERES ============
   Paso I · talleres internacionales destacados (contenido fijo, talleres.json → internacionales,
   con aviso ✳ mientras porConfirmar). Paso II · galería de GET /workshops (FichaTaller variante
   galería) con filtro por nivel y público y ficha ampliada en <dialog>. Paso III · espacio del
   tallerista (EspacioTallerista). La galería se carga en el navegador; el resto va en el HTML. */

interface Internacional {
  id: string;
  modelo: string;
  tallerista: string;
  pais: string;
  dia: string;
  nivel: string;
  papel: string;
  papelNota: string;
  duracion: string;
  foto?: string;
  fotoAlt?: string;
  red?: { texto: string; href: string };
  bio: string[];
  porConfirmar?: boolean;
}

@Component({
  selector: 'app-pagina-talleres',
  imports: [PaginaInterna, Doblez, Enlace, Reveal, Separador, EstadoDatos, FichaTaller, PapelPlegado, EspacioTallerista],
  templateUrl: './pagina-talleres.html',
  styleUrl: './pagina-talleres.css',
})
export class PaginaTalleres {
  protected readonly t = traducir(talleres);
  protected readonly internacionales = this.t.internacionales.lista as Internacional[];
  private readonly etiquetas = traducir(comun).taller;
  protected readonly niveles = (['P', 'I', 'A'] as NivelTaller[]).map((id) => ({ id, nombre: this.etiquetas.niveles[id] }));
  protected readonly publicos = (['A', 'N'] as PublicoTaller[]).map((id) => ({ id, nombre: this.etiquetas.publicos[id] }));
  protected readonly programaVisible = seccionVisible('booklet');

  protected readonly galeria = new DatosVivos<Taller[]>(async () =>
    (await api<TallerGaleriaApi[]>('GET', 'workshops')).map(desdeGaleria));

  protected readonly nivel = signal<NivelTaller | null>(null);
  protected readonly publico = signal<PublicoTaller | null>(null);
  protected readonly filtrados = computed(() => (this.galeria.datos() ?? []).filter((t) =>
    (!this.nivel() || t.nivel === this.nivel()) && (!this.publico() || t.publico === this.publico())));
  protected readonly contador = computed(() => rellenar(this.t.galeria.contador, { n: this.filtrados().length }));

  protected readonly ampliado = signal<Taller | null>(null);
  private readonly dialogo = viewChild.required<ElementRef<HTMLDialogElement>>('dialogo');

  constructor() {
    fijarMeta('talleres');
    afterNextRender(() => this.galeria.cargar());
  }

  protected irA(id: string): void {
    document.getElementById(id)?.scrollIntoView({ block: 'start' });
  }

  protected verAria(t: Taller): string {
    return rellenar(this.t.galeria.verAria, { nombre: t.nombre });
  }

  protected ampliar(t: Taller): void {
    this.ampliado.set(t);
    const d = this.dialogo().nativeElement;
    if (d.open) return;
    // jsdom (pruebas) no implementa showModal()
    if (typeof d.showModal === 'function') d.showModal();
    else d.setAttribute('open', '');
  }

  protected cerrar(): void {
    const d = this.dialogo().nativeElement;
    if (typeof d.close === 'function') d.close();
    else d.removeAttribute('open');
  }

  /** Clic en el fondo oscuro (fuera de la hoja) cierra. */
  protected alHacerClic(e: MouseEvent): void {
    if (e.target === this.dialogo().nativeElement) this.cerrar();
  }
}
