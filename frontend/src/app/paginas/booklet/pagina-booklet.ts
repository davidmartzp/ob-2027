import { PlatformLocation } from '@angular/common';
import { afterNextRender, Component, computed, DestroyRef, ElementRef, inject, signal } from '@angular/core';
import { Doblez } from '@components/doblez/doblez';
import { EstadoDatos } from '@components/estado-datos/estado-datos';
import { FichaTaller } from '@components/ficha-taller/ficha-taller';
import { PaginaInterna } from '@components/pagina-interna/pagina-interna';
import { Separador } from '@components/separador/separador';
import booklet from '@contenido/booklet.json';
import comun from '@contenido/comun.json';
import evento from '@contenido/evento.json';
import { api } from '@core/api';
import { DatosVivos } from '@core/datos-vivos';
import { Enlace } from '@core/enlace';
import { rellenar, traducir } from '@core/idioma';
import { fijarMeta } from '@core/meta';
import { Taller } from '@core/modelos';
import { seccionVisible } from '@core/navegacion';
import { Reveal } from '@core/reveal';
import { desdePrograma, TallerProgramaApi } from '@core/talleres';
import {
  agrupar, DIAS, filtrar, filtrosAUrl, filtrosDesdeUrl, FiltrosPrograma, hayFiltros, NIVELES, opciones, PUBLICOS, SIN_FILTROS,
} from './filtros-programa';

/* ============ PÁGINA: BOOKLET (programa de talleres) ============
   GET /events/schedule, agrupado por día y hora, con filtros en la URL (filtros-programa.ts)
   y versión imprimible. Cada taller es un <details name="taller"> con su FichaTaller
   (variante programa). Solo muestra el nombre del tallerista: nunca inscritos ni contactos
   (esas listas están en el CMS). El HTML prerenderizado trae título, intro y meta; los
   talleres llegan en el navegador. Textos: contenido/booklet.json. */

@Component({
  selector: 'app-pagina-booklet',
  imports: [PaginaInterna, Doblez, Enlace, Reveal, Separador, EstadoDatos, FichaTaller],
  templateUrl: './pagina-booklet.html',
  styleUrl: './pagina-booklet.css',
})
export class PaginaBooklet {
  protected readonly t = traducir(booklet);
  private readonly comun = traducir(comun).taller;
  protected readonly dias = DIAS.map((id) => ({ id, nombre: this.comun.dias[id] }));
  protected readonly niveles = NIVELES.map((id) => ({ id, nombre: this.comun.niveles[id] }));
  protected readonly publicos = PUBLICOS.map((id) => ({ id, nombre: this.comun.publicos[id] }));
  protected readonly nombreDia = this.comun.dias;
  /** El enlace a /inscribir-talleres solo mientras la inscripción a talleres está abierta. */
  protected readonly inscripcionAbierta = evento.inscripcionTalleresAbierta && seccionVisible('inscribir-talleres');

  protected readonly programa = new DatosVivos<Taller[]>(async () =>
    (await api<TallerProgramaApi[]>('GET', 'events/schedule')).map(desdePrograma));

  protected readonly filtros = signal<FiltrosPrograma>(SIN_FILTROS);
  protected readonly conFiltros = computed(() => hayFiltros(this.filtros()));

  private readonly todos = computed(() => this.programa.datos() ?? []);
  protected readonly salas = computed(() => opciones(this.todos().map((t) => t.evento?.salon)));
  protected readonly talleristas = computed(() => opciones(this.todos().map((t) => t.tallerista)));
  protected readonly filtrados = computed(() => filtrar(this.todos(), this.filtros()));
  protected readonly porDia = computed(() => agrupar(this.filtrados()));
  protected readonly contador = computed(() => {
    const n = this.filtrados().length;
    const total = this.todos().length;
    return n === total ? rellenar(this.t.filtros.contador, { n }) : rellenar(this.t.filtros.contadorFiltrados, { n, total });
  });

  private readonly host = inject<ElementRef<HTMLElement>>(ElementRef);
  private readonly ubicacion = inject(PlatformLocation);

  constructor() {
    fijarMeta('booklet');
    const destroyRef = inject(DestroyRef);
    afterNextRender(() => {
      this.filtros.set(filtrosDesdeUrl(this.ubicacion.search));
      this.programa.cargar();

      // Al imprimir, todas las fichas abiertas (sin «name», que dejaría solo una) y luego como estaban.
      let antes: { d: HTMLDetailsElement; abierta: boolean }[] = [];
      const alImprimir = (): void => {
        antes = [...this.host.nativeElement.querySelectorAll('details')].map((d) => ({ d, abierta: d.open }));
        for (const { d } of antes) { d.removeAttribute('name'); d.open = true; }
      };
      const trasImprimir = (): void => {
        for (const { d, abierta } of antes) { d.open = abierta; d.setAttribute('name', 'taller'); }
      };
      addEventListener('beforeprint', alImprimir);
      addEventListener('afterprint', trasImprimir);
      destroyRef.onDestroy(() => {
        removeEventListener('beforeprint', alImprimir);
        removeEventListener('afterprint', trasImprimir);
      });
    });
  }

  protected poner<K extends keyof FiltrosPrograma>(campo: K, valor: FiltrosPrograma[K]): void {
    this.filtros.update((f) => ({ ...f, [campo]: valor || null }));
    this.guardarEnUrl();
  }

  protected limpiar(): void {
    this.filtros.set(SIN_FILTROS);
    this.guardarEnUrl();
  }

  protected imprimir(): void {
    print();
  }

  protected valor(e: Event): string {
    return (e.target as HTMLSelectElement).value;
  }

  /** La vista filtrada queda en la URL (sin sumar entradas al historial). */
  private guardarEnUrl(): void {
    const u = this.ubicacion;
    u.replaceState(u.getState(), '', u.pathname + filtrosAUrl(this.filtros()) + u.hash);
  }
}
