import { afterNextRender, Component, computed, DestroyRef, inject, signal, viewChildren } from '@angular/core';
import { Doblez } from '@components/doblez/doblez';
import { Enlace } from '@core/enlace';
import { rellenar, sinEtiquetas, traducir } from '@core/idioma';
import { fijarMeta } from '@core/meta';
import { Reveal } from '@core/reveal';
import { Separador } from '@components/separador/separador';
import actividades from '@contenido/actividades.json';
import { Actividad, CategoriaActividad } from '@core/modelos';
import { PaginaInterna } from '@components/pagina-interna/pagina-interna';
import { FichaActividad } from '@components/ficha-actividad/ficha-actividad';
import { Itinerario } from '@components/itinerario/itinerario';
import { seccionVisible } from '@core/navegacion';

/* ============ PÁGINA: ACTIVIDADES ============
   Buscador y filtros en el navegador (sin backend). Sin filtros, el prerender
   trae todas las fichas completas (cada una con su diálogo). Abre el diálogo de la
   ficha del ancla (actividades#chocogami) al cargar y cuando cambia el hash.
   Después del catálogo va el itinerario de la home (Itinerario, con su modal). */

/** Minúsculas y sin tildes (quita las marcas diacríticas): «taller» encuentra «Talleres», «bogota» a «Bogotá». */
const normalizar = (t: string): string => t.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();

@Component({
  selector: 'app-pagina-actividades',
  imports: [Doblez, Enlace, Reveal, Separador, PaginaInterna, FichaActividad, Itinerario],
  templateUrl: './pagina-actividades.html',
  styleUrl: './pagina-actividades.css',
})
export class PaginaActividades {
  private readonly contenido = traducir(actividades);
  protected readonly t = this.contenido.pagina;
  private readonly todas = this.contenido.items as Actividad[];
  private readonly nombresCategoria = this.contenido.categorias as Record<CategoriaActividad, string>;
  protected readonly total = this.todas.length;
  protected readonly categorias = (Object.keys(this.nombresCategoria) as CategoriaActividad[])
    .map((id) => ({ id, nombre: this.nombresCategoria[id] }));

  protected readonly itinerarioVisible = seccionVisible('itinerario');

  protected readonly termino = signal('');
  protected readonly categoria = signal<CategoriaActividad | null>(null);

  /** Texto en el que busca cada actividad: título, resumen, texto, nota y categoría. */
  private readonly indice = new Map<Actividad, string>(this.todas.map((a) => [a, normalizar([
    sinEtiquetas(a.titulo), a.resumen, ...a.parrafos, a.nota, this.nombresCategoria[a.categoria],
  ].filter(Boolean).join(' '))]));

  protected readonly filtradas = computed(() => {
    const palabras = normalizar(this.termino()).split(/\s+/).filter(Boolean);
    const cat = this.categoria();
    return this.todas.filter((a) =>
      (!cat || a.categoria === cat) && palabras.every((p) => this.indice.get(a)!.includes(p)));
  });
  /** Con foto: una tarjeta cada una. Sin foto: notas apiladas en una sola celda, al final. */
  protected readonly conFoto = computed(() => this.filtradas().filter((a) => a.foto));
  protected readonly sinFoto = computed(() => this.filtradas().filter((a) => !a.foto));

  protected readonly contador = computed(() => {
    const n = this.filtradas().length;
    if (n === this.total) return rellenar(this.t.contadorTodas, { n });
    return n ? rellenar(this.t.contadorFiltradas, { n, total: this.total }) : this.t.contadorCero;
  });

  private readonly fichas = viewChildren(FichaActividad);

  constructor() {
    fijarMeta('actividades');

    const destroyRef = inject(DestroyRef);
    afterNextRender(() => {
      const alCambiarHash = (): void => this.abrirDelAncla();
      this.abrirDelAncla();
      addEventListener('hashchange', alCambiarHash);
      destroyRef.onDestroy(() => removeEventListener('hashchange', alCambiarHash));
    });
  }

  protected limpiar(campo?: HTMLInputElement): void {
    this.termino.set('');
    this.categoria.set(null);
    campo?.focus();
  }

  /** actividades#chocogami: quita filtros (la ficha podría estar oculta), la lleva a la vista y abre su diálogo. */
  private abrirDelAncla(): void {
    const id = decodeURIComponent(location.hash.slice(1));
    if (!this.todas.some((a) => a.id === id)) return;
    this.limpiar();
    // tras repintar la lista sin filtros
    requestAnimationFrame(() => {
      const ficha = this.fichas().find((f) => f.actividad().id === id);
      if (!ficha) return;
      document.getElementById(id)?.scrollIntoView({ block: 'center' });
      ficha.abrir();
    });
  }
}
