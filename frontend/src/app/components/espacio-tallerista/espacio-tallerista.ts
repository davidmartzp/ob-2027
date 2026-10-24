import { NgTemplateOutlet } from '@angular/common';
import { Component, computed, DestroyRef, ElementRef, inject, signal, viewChild } from '@angular/core';
import { EstadoDatos } from '@components/estado-datos/estado-datos';
import { PapelPlegado } from '@components/papel-plegado/papel-plegado';
import comun from '@contenido/comun.json';
import evento from '@contenido/evento.json';
import talleres from '@contenido/talleres.json';
import { api, apiUrl, ErrorApi } from '@core/api';
import { DatosVivos, estadoDeError } from '@core/datos-vivos';
import { Enlace } from '@core/enlace';
import {
  CampoTaller, construirTaller, DatosTaller, ErroresTaller, tallerVacio, validarImagen, validarTaller,
} from '@core/formulario-taller';
import { rellenar, traducir } from '@core/idioma';
import { NivelTaller, PublicoTaller, Socio } from '@core/modelos';
import { TallerPropioApi } from '@core/talleres';

/* ============ ESPACIO DEL TALLERISTA (/talleres, Paso III) ============
   Sin iniciar sesión, como el sitio anterior: el correo de la inscripción trae a cada
   persona inscrita con él (familias) y sus talleres (POST /workshops/by-email). Por persona:
   proponer (POST /attendees/{id}/workshops), editar (PUT /workshops/{id}), retirar con
   confirmación (DELETE), foto (POST /workshops/{id}/image) y socios (GET /partners +
   PUT /workshops/{id}/partners). Valida en el navegador lo mismo que el backend
   (core/formulario-taller.ts) y muestra junto a cada campo lo que el backend rechace.
   Abierto solo con evento.inscripcionesAbiertas. Textos: talleres.json → tallerista. */

interface Persona {
  id: number;
  name: string;
  workshops: TallerPropioApi[];
}

/** Qué está abierto: un formulario (nuevo o edición), la foto o los socios de un taller. */
type Panel =
  | { tipo: 'nuevo'; persona: number }
  | { tipo: 'editar' | 'foto' | 'socios'; persona: number; taller: number };

const ESTADO_RETIRADO = 0;
const ESTADO_OCULTO = 2;

@Component({
  selector: 'app-espacio-tallerista',
  imports: [Enlace, EstadoDatos, PapelPlegado, NgTemplateOutlet],
  templateUrl: './espacio-tallerista.html',
  styleUrl: './espacio-tallerista.css',
})
export class EspacioTallerista {
  protected readonly t = traducir(talleres).tallerista;
  protected readonly etiquetas = traducir(comun).taller;
  protected readonly abierto = evento.inscripcionesAbiertas;
  protected readonly conApi = !!apiUrl();
  protected readonly niveles: NivelTaller[] = ['P', 'I', 'A'];
  protected readonly publicos: PublicoTaller[] = ['A', 'N'];
  protected readonly oculto = ESTADO_OCULTO;
  protected readonly etiquetasCerrado = traducir(comun).estados.cerradoTitulo;

  // búsqueda por correo
  protected readonly correo = signal('');
  protected readonly errorCorreo = signal('');
  protected readonly buscando = signal(false);
  protected readonly personas = signal<Persona[] | null>(null);
  protected readonly errorBusqueda = signal<{ estado: 'error' | 'cerrado'; mensaje: string } | null>(null);

  // panel abierto y formulario
  protected readonly panel = signal<Panel | null>(null);
  protected readonly datos = signal<DatosTaller>(tallerVacio());
  protected readonly errores = signal<ErroresTaller>({});
  protected readonly errorEnvio = signal('');
  protected readonly guardando = signal(false);
  /** Aviso breve tras guardar/retirar (aria-live). */
  protected readonly aviso = signal('');

  // foto
  protected readonly archivo = signal<File | null>(null);
  protected readonly vistaPrevia = signal<string | null>(null);
  protected readonly errorFoto = signal('');

  // socios
  protected readonly socios = new DatosVivos<Socio[]>(() => api<Socio[]>('GET', 'partners'));
  protected readonly elegidos = signal<ReadonlySet<number>>(new Set());

  // retirar
  protected readonly porRetirar = signal<{ persona: number; taller: TallerPropioApi } | null>(null);
  private readonly confirmacion = viewChild.required<ElementRef<HTMLDialogElement>>('confirmacion');

  protected readonly textoRetiro = computed(() =>
    rellenar(this.t.confirmarRetiro.texto, { nombre: this.porRetirar()?.taller.name ?? '' }));

  private readonly host = inject<ElementRef<HTMLElement>>(ElementRef);

  constructor() {
    inject(DestroyRef).onDestroy(() => this.soltarVistaPrevia());
  }

  protected talleresDe(p: Persona): string {
    return rellenar(this.t.talleresDe, { nombre: p.name });
  }

  protected visibles(p: Persona): TallerPropioApi[] {
    return p.workshops.filter((w) => w.status !== ESTADO_RETIRADO);
  }

  protected duracion(minutos: number): string {
    return rellenar(this.etiquetas.minutos, { n: minutos });
  }

  protected abiertoEn(tipo: Panel['tipo'], persona: number, taller?: number): boolean {
    const p = this.panel();
    return !!p && p.tipo === tipo && p.persona === persona && (taller === undefined || ('taller' in p && p.taller === taller));
  }

  /* ---------- búsqueda ---------- */

  protected async buscar(): Promise<void> {
    const correo = this.correo().trim();
    this.errorCorreo.set('');
    this.errorBusqueda.set(null);
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(correo)) {
      this.errorCorreo.set(this.t.correoInvalido);
      return;
    }
    this.buscando.set(true);
    try {
      this.personas.set(await api<Persona[]>('POST', 'workshops/by-email', { email: correo }));
      this.cerrarPanel();
    } catch (e) {
      if (e instanceof ErrorApi && e.fields['email']) this.errorCorreo.set(e.fields['email']);
      else {
        const { estado, mensaje } = estadoDeError(e);
        this.errorBusqueda.set({ estado: estado === 'cerrado' ? 'cerrado' : 'error', mensaje });
      }
    } finally {
      this.buscando.set(false);
    }
  }

  protected otroCorreo(): void {
    this.personas.set(null);
    this.correo.set('');
    this.cerrarPanel();
    this.enfocar('#tallerista-correo');
  }

  /* ---------- proponer / editar ---------- */

  protected nuevo(persona: number): void {
    this.abrirFormulario({ tipo: 'nuevo', persona }, tallerVacio());
  }

  protected editar(persona: number, w: TallerPropioApi): void {
    this.abrirFormulario({ tipo: 'editar', persona, taller: w.id }, {
      name: w.name, level: w.level, public: w.public, duration: String(w.duration), observations: w.observations ?? '',
    });
  }

  protected poner<K extends CampoTaller>(campo: K, valor: DatosTaller[K]): void {
    this.datos.update((d) => ({ ...d, [campo]: valor }));
    if (this.errores()[campo]) this.errores.update(({ [campo]: _, ...resto }) => resto);
  }

  protected texto(e: Event): string {
    return (e.target as HTMLInputElement).value;
  }

  protected async guardar(): Promise<void> {
    const panel = this.panel();
    if (!panel || (panel.tipo !== 'nuevo' && panel.tipo !== 'editar') || this.guardando()) return;
    const errores = validarTaller(this.datos(), this.t.formulario.errores);
    this.errores.set(errores);
    this.errorEnvio.set('');
    const primero = Object.keys(errores)[0];
    if (primero) return this.enfocar(`#taller-${primero}`);

    this.guardando.set(true);
    const envio = construirTaller(this.datos());
    try {
      if (panel.tipo === 'nuevo') {
        const creados = await api<TallerPropioApi[]>('POST', `attendees/${panel.persona}/workshops`, { workshops: [envio] });
        this.cambiarPersona(panel.persona, (ws) => [...ws, ...creados]);
      } else {
        const editado = await api<TallerPropioApi>('PUT', `workshops/${panel.taller}`, envio);
        this.cambiarTaller(panel.persona, panel.taller, () => editado);
      }
      this.cerrarPanel();
      this.aviso.set(this.t.formulario.guardado);
    } catch (e) {
      this.mostrarErrorServidor(e);
    } finally {
      this.guardando.set(false);
    }
  }

  /** 422: cada mensaje junto a su campo (`workshops.0.name` o `name`); lo escrito se conserva. */
  private mostrarErrorServidor(e: unknown): void {
    if (e instanceof ErrorApi) {
      const porCampo: ErroresTaller = {};
      for (const [campo, mensaje] of Object.entries(e.fields)) {
        const nombre = campo.split('.').at(-1) as CampoTaller;
        if (nombre in tallerVacio()) porCampo[nombre] = mensaje;
      }
      this.errores.set(porCampo);
      this.errorEnvio.set(Object.keys(porCampo).length ? '' : e.message);
    } else {
      this.errorEnvio.set(estadoDeError(e).mensaje || traducir(comun).estados.errorTexto);
    }
  }

  /* ---------- foto ---------- */

  protected abrirFoto(persona: number, taller: number): void {
    this.cerrarPanel();
    this.panel.set({ tipo: 'foto', persona, taller });
  }

  protected elegirFoto(e: Event): void {
    const entrada = e.target as HTMLInputElement;
    const archivo = entrada.files?.[0] ?? null;
    this.soltarVistaPrevia();
    this.errorFoto.set('');
    this.archivo.set(null);
    if (!archivo) return;
    const problema = validarImagen(archivo);
    if (problema) {
      this.errorFoto.set(this.t.imagen[problema]);
      entrada.value = '';
      return;
    }
    this.archivo.set(archivo);
    this.vistaPrevia.set(typeof URL.createObjectURL === 'function' ? URL.createObjectURL(archivo) : null);
  }

  protected async subirFoto(): Promise<void> {
    const panel = this.panel();
    const archivo = this.archivo();
    if (panel?.tipo !== 'foto' || !archivo || this.guardando()) return;
    const cuerpo = new FormData();
    cuerpo.append('image', archivo);
    this.guardando.set(true);
    this.errorFoto.set('');
    try {
      const { image } = await api<{ image: string }>('POST', `workshops/${panel.taller}/image`, cuerpo);
      this.cambiarTaller(panel.persona, panel.taller, (w) => ({ ...w, image }));
      this.cerrarPanel();
      this.aviso.set(this.t.imagen.lista);
    } catch (e) {
      // la foto anterior se conserva: solo se cambia con una respuesta correcta
      this.errorFoto.set(e instanceof ErrorApi ? (e.fields['image'] ?? e.message) : estadoDeError(e).mensaje || traducir(comun).estados.errorTexto);
    } finally {
      this.guardando.set(false);
    }
  }

  /* ---------- socios ---------- */

  protected abrirSocios(persona: number, w: TallerPropioApi): void {
    this.cerrarPanel();
    this.panel.set({ tipo: 'socios', persona, taller: w.id });
    this.elegidos.set(new Set(w.partners.map((s) => s.id)));
    if (this.socios.estado() !== 'listo') this.socios.cargar();
  }

  protected marcarSocio(id: number, marcado: boolean): void {
    this.elegidos.update((s) => {
      const nuevo = new Set(s);
      if (marcado) nuevo.add(id); else nuevo.delete(id);
      return nuevo;
    });
  }

  protected marcado(e: Event): boolean {
    return (e.target as HTMLInputElement).checked;
  }

  protected async guardarSocios(): Promise<void> {
    const panel = this.panel();
    if (panel?.tipo !== 'socios' || this.guardando()) return;
    this.guardando.set(true);
    this.errorEnvio.set('');
    try {
      const partners = await api<Socio[]>('PUT', `workshops/${panel.taller}/partners`, { partner_ids: [...this.elegidos()] });
      this.cambiarTaller(panel.persona, panel.taller, (w) => ({ ...w, partners }));
      this.cerrarPanel();
      this.aviso.set(this.t.sociosPanel.guardados);
    } catch (e) {
      this.errorEnvio.set(e instanceof ErrorApi ? e.message : estadoDeError(e).mensaje || traducir(comun).estados.errorTexto);
    } finally {
      this.guardando.set(false);
    }
  }

  /* ---------- retirar ---------- */

  protected pedirRetiro(persona: number, taller: TallerPropioApi): void {
    this.porRetirar.set({ persona, taller });
    this.errorEnvio.set('');
    const d = this.confirmacion().nativeElement;
    // jsdom (pruebas) no implementa showModal()
    if (typeof d.showModal === 'function') d.showModal();
    else d.setAttribute('open', '');
  }

  protected cerrarConfirmacion(): void {
    const d = this.confirmacion().nativeElement;
    if (typeof d.close === 'function') d.close();
    else d.removeAttribute('open');
  }

  protected async retirar(): Promise<void> {
    const pedido = this.porRetirar();
    if (!pedido || this.guardando()) return;
    this.guardando.set(true);
    try {
      await api<void>('DELETE', `workshops/${pedido.taller.id}`);
      this.cambiarPersona(pedido.persona, (ws) => ws.filter((w) => w.id !== pedido.taller.id));
      this.cerrarConfirmacion();
      this.porRetirar.set(null);
      this.aviso.set(this.t.confirmarRetiro.retirado);
    } catch (e) {
      this.errorEnvio.set(e instanceof ErrorApi ? e.message : estadoDeError(e).mensaje || traducir(comun).estados.errorTexto);
    } finally {
      this.guardando.set(false);
    }
  }

  /* ---------- ayudas ---------- */

  protected cerrarPanel(): void {
    this.panel.set(null);
    this.errores.set({});
    this.errorEnvio.set('');
    this.errorFoto.set('');
    this.archivo.set(null);
    this.soltarVistaPrevia();
  }

  private abrirFormulario(panel: Panel, datos: DatosTaller): void {
    this.cerrarPanel();
    this.aviso.set('');
    this.datos.set(datos);
    this.panel.set(panel);
    this.enfocar('#taller-name');
  }

  private cambiarPersona(id: number, cambio: (ws: TallerPropioApi[]) => TallerPropioApi[]): void {
    this.personas.update((ps) => ps?.map((p) => (p.id === id ? { ...p, workshops: cambio(p.workshops) } : p)) ?? ps);
  }

  private cambiarTaller(persona: number, taller: number, cambio: (w: TallerPropioApi) => TallerPropioApi): void {
    this.cambiarPersona(persona, (ws) => ws.map((w) => (w.id === taller ? cambio(w) : w)));
  }

  private soltarVistaPrevia(): void {
    const url = this.vistaPrevia();
    if (url && typeof URL.revokeObjectURL === 'function') URL.revokeObjectURL(url);
    this.vistaPrevia.set(null);
  }

  private enfocar(selector: string): void {
    setTimeout(() => this.host.nativeElement.querySelector<HTMLElement>(selector)?.focus());
  }
}
