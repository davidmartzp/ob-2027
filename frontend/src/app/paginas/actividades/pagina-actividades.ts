import { afterNextRender, Component, computed, DestroyRef, ElementRef, inject, signal } from '@angular/core';
import { fijarMeta } from '../../compartido/meta';
import { ACTIVIDADES, CATEGORIAS } from '../../datos/actividades';
import { SECCIONES_VISIBLES } from '../../datos/evento';
import { Actividad, CategoriaActividad } from '../../datos/modelos';
import { Figura } from '../../compartido/figura';
import { Reveal } from '../../compartido/reveal';
import { Separador } from '../../compartido/separador';
import { Doblez } from '../../compartido/doblez';
import { Hero } from '../../secciones/hero/hero';
import { Patrocinadores } from '../../secciones/patrocinadores/patrocinadores';
import { FichaActividad } from './ficha-actividad';

/* ============ PÁGINA: ACTIVIDADES ============
   Como toda página interna: el hero del evento (toro y título) arriba, luego la cabecera
   propia de la página y los patrocinadores al final.
   Buscador y filtros en el navegador (sin backend). Sin filtros, el prerender
   trae las 14 fichas completas. Abre la ficha del ancla (actividades#chocogami)
   al cargar y cuando cambia el hash. */

/** Minúsculas y sin tildes: «taller» encuentra «Talleres», «bogota» a «Bogotá». */
const normalizar = (t: string): string => t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

@Component({
  selector: 'app-pagina-actividades',
  imports: [Doblez, Figura, Hero, Reveal, Separador, Patrocinadores, FichaActividad],
  templateUrl: './pagina-actividades.html',
  styleUrl: './pagina-actividades.css',
})
export class PaginaActividades {
  protected readonly patrocinadoresVisibles = SECCIONES_VISIBLES.patrocinadores;
  protected readonly total = ACTIVIDADES.length;
  protected readonly categorias = (Object.keys(CATEGORIAS) as CategoriaActividad[]).map((id) => ({ id, nombre: CATEGORIAS[id] }));

  protected readonly termino = signal('');
  protected readonly categoria = signal<CategoriaActividad | null>(null);

  /** Texto en el que busca cada actividad: título, resumen, texto, nota y categoría. */
  private readonly indice = new Map<Actividad, string>(ACTIVIDADES.map((a) => [a, normalizar([
    a.titulo.antes, a.titulo.enfasis, a.titulo.despues, a.resumen, ...a.parrafos, a.nota, CATEGORIAS[a.categoria],
  ].filter(Boolean).join(' '))]));

  protected readonly filtradas = computed(() => {
    const palabras = normalizar(this.termino()).split(/\s+/).filter(Boolean);
    const cat = this.categoria();
    return ACTIVIDADES.filter((a) =>
      (!cat || a.categoria === cat) && palabras.every((p) => this.indice.get(a)!.includes(p)));
  });

  private readonly host = inject<ElementRef<HTMLElement>>(ElementRef);

  constructor() {
    fijarMeta(
      $localize`:@@meta.tituloActividades:Actividades — Origami Bogotá 2027`,
      $localize`:@@meta.descripcionActividades:Chocogami, talleres por niveles, intercambio de pines y tarjetas, exposición, chiva rumbera y más: todas las actividades del Congreso Origami Bogotá 2027.`,
      'actividades',
    );

    const destroyRef = inject(DestroyRef);
    afterNextRender(() => {
      const alCambiarHash = (): void => this.abrirDelAncla();
      this.abrirDelAncla();
      addEventListener('hashchange', alCambiarHash);
      destroyRef.onDestroy(() => removeEventListener('hashchange', alCambiarHash));
    });
  }

  protected limpiar(campo: HTMLInputElement): void {
    this.termino.set('');
    this.categoria.set(null);
    campo.focus();
  }

  /** actividades#chocogami: quita filtros (la ficha podría estar oculta), la abre y la lleva a la vista. */
  private abrirDelAncla(): void {
    const id = decodeURIComponent(location.hash.slice(1));
    if (!ACTIVIDADES.some((a) => a.id === id)) return;
    this.termino.set('');
    this.categoria.set(null);
    // tras repintar la lista sin filtros
    requestAnimationFrame(() => {
      const ficha = this.host.nativeElement.querySelector<HTMLElement>(`li[id="${id}"]`);
      const detalles = ficha?.querySelector('details');
      if (!ficha || !detalles) return;
      detalles.open = true;
      ficha.scrollIntoView({ block: 'start' });
    });
  }
}
