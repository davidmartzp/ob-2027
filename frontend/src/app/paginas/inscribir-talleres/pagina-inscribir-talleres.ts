import { afterNextRender, Component, computed, ElementRef, inject, signal, viewChild } from '@angular/core';
import { Doblez } from '@components/doblez/doblez';
import { EstadoDatos } from '@components/estado-datos/estado-datos';
import { FichaTaller } from '@components/ficha-taller/ficha-taller';
import { PaginaInterna } from '@components/pagina-interna/pagina-interna';
import { Separador } from '@components/separador/separador';
import comun from '@contenido/comun.json';
import evento from '@contenido/evento.json';
import contenido from '@contenido/inscribir-talleres.json';
import { api, apiUrl, ErrorApi } from '@core/api';
import { DatosVivos, estadoDeError } from '@core/datos-vivos';
import { Enlace } from '@core/enlace';
import { rellenar, traducir } from '@core/idioma';
import { fijarMeta } from '@core/meta';
import { Taller } from '@core/modelos';
import { seccionVisible } from '@core/navegacion';
import { Reveal } from '@core/reveal';
import { desdeActivo, EventoActivoApi } from '@core/talleres';
import {
  chocaCon, FiltrosDia, filtrarDia, guardarSesion, leerSesion, porFranja, SesionAsistente,
} from './talleres-del-dia';

/* ============ PÁGINA: INSCRIBIR TALLERES (el día del congreso) ============
   Paso I · correo + número → POST /attendees/verify-code (el backend decide el turno y
   responde con su mensaje). La sesión se recuerda en sessionStorage (id, nombre, número)
   hasta «Salir»: es comodidad, no da permisos (los asistentes no inician sesión).
   Paso II · GET /events/active por franja horaria, con filtros y el estado de cada taller.
   Paso III · «mis talleres» (GET /attendees/{id}/events), inscribirse (POST) y cancelar
   (DELETE) con confirmación. Un 403 con logout (ventana cerrada) borra la sesión local.
   Con evento.inscripcionTalleresAbierta en false solo explica cuándo abre.
   Textos: contenido/inscribir-talleres.json. */

interface Verificacion {
  valid: boolean;
  message: string;
  id?: number;
  code?: string | number;
  name?: string;
}

/** Una confirmación pendiente: inscribirse o cancelar un taller. */
type Pendiente = { tipo: 'inscribir' | 'cancelar'; taller: Taller };

@Component({
  selector: 'app-pagina-inscribir-talleres',
  imports: [PaginaInterna, Doblez, Enlace, Reveal, Separador, EstadoDatos, FichaTaller],
  templateUrl: './pagina-inscribir-talleres.html',
  styleUrl: './pagina-inscribir-talleres.css',
})
export class PaginaInscribirTalleres {
  protected readonly t = traducir(contenido);
  private readonly etiquetas = traducir(comun);
  protected readonly abierta = evento.inscripcionTalleresAbierta;
  protected readonly conApi = !!apiUrl();
  protected readonly textoCerrado = rellenar(this.t.pagina.cerrado, { fecha: traducir(evento).fecha });
  protected readonly programaVisible = seccionVisible('booklet');

  // Paso I
  protected readonly sesion = signal<SesionAsistente | null>(null);
  protected readonly correo = signal('');
  protected readonly numero = signal('');
  protected readonly verificando = signal(false);
  /** Mensaje del backend (turno, día, datos incorrectos, ventana cerrada). */
  protected readonly mensajeAcceso = signal('');
  protected readonly mensajeTurno = signal('');
  protected readonly recordatorio = signal('');

  // Paso II y III
  protected readonly activos = new DatosVivos<Taller[]>(async () =>
    (await api<EventoActivoApi[]>('GET', 'events/active')).map(desdeActivo));
  protected readonly misEventos = signal<ReadonlySet<number>>(new Set());
  protected readonly filtros = signal<FiltrosDia>({ salon: null, hora: null, tallerista: null });
  /** Mensaje del backend sobre un taller (choque, sin cupo, edad), junto a su tarjeta. */
  protected readonly errorTaller = signal<{ evento: number; mensaje: string } | null>(null);
  protected readonly aviso = signal('');
  protected readonly ocupado = signal(false);
  protected readonly pendiente = signal<Pendiente | null>(null);

  private readonly todos = computed(() => this.activos.datos() ?? []);
  protected readonly mios = computed(() => this.todos().filter((t) => this.misEventos().has(t.evento!.id)));
  protected readonly salones = computed(() => opciones(this.todos().map((t) => t.evento?.salon)));
  protected readonly horas = computed(() => opciones(this.todos().map((t) => t.evento?.inicio)));
  protected readonly talleristas = computed(() => opciones(this.todos().map((t) => t.tallerista)));
  protected readonly franjas = computed(() => porFranja(filtrarDia(this.todos(), this.filtros())));

  protected readonly textoPendiente = computed(() => {
    const p = this.pendiente();
    if (!p) return '';
    const e = p.taller.evento!;
    return p.tipo === 'inscribir'
      ? rellenar(this.t.hoy.confirmar.texto, { nombre: p.taller.nombre, horario: `${this.etiquetas.taller.dias[e.dia]} ${e.inicio}–${e.fin}${e.salon ? ' · ' + e.salon : ''}` })
      : rellenar(this.t.mios.confirmar.texto, { nombre: p.taller.nombre });
  });

  private readonly dialogo = viewChild.required<ElementRef<HTMLDialogElement>>('dialogo');
  private readonly host = inject<ElementRef<HTMLElement>>(ElementRef);
  private almacen?: Storage;

  constructor() {
    fijarMeta('inscribir-talleres');
    afterNextRender(() => {
      try { this.almacen = sessionStorage; } catch { /* almacenamiento bloqueado */ }
      const guardada = leerSesion(this.almacen);
      if (guardada && this.abierta && this.conApi) this.entrar(guardada);
    });
  }

  protected hola(): string {
    return rellenar(this.t.acceso.hola, { nombre: this.sesion()?.nombre.split(' ')[0] ?? '' });
  }

  protected numeroDe(): string {
    return rellenar(this.t.acceso.numeroDe, { numero: this.sesion()?.numero ?? '' });
  }

  protected texto(e: Event): string {
    return (e.target as HTMLInputElement | HTMLSelectElement).value;
  }

  /* ---------- Paso I ---------- */

  protected async verificar(): Promise<void> {
    if (this.verificando()) return;
    this.mensajeAcceso.set('');
    this.recordatorio.set('');
    const email = this.correo().trim();
    const code = this.numero().trim();
    if (!email || !code) return this.mensajeAcceso.set(this.t.acceso.faltan);
    this.verificando.set(true);
    try {
      const r = await api<Verificacion>('POST', 'attendees/verify-code', { email, code });
      if (!r.valid || r.id === undefined) return this.mensajeAcceso.set(r.message);
      this.mensajeTurno.set(r.message);
      const s: SesionAsistente = { id: r.id, nombre: r.name ?? '', numero: String(r.code ?? code) };
      guardarSesion(this.almacen, s);
      await this.entrar(s);
      this.enfocar('#paso-hoy h2');
    } catch (e) {
      this.mensajeAcceso.set(estadoDeError(e).mensaje || this.etiquetas.estados.errorTexto);
    } finally {
      this.verificando.set(false);
    }
  }

  protected async recordar(): Promise<void> {
    const email = this.correo().trim();
    this.mensajeAcceso.set('');
    if (!email) return this.recordatorio.set(this.t.acceso.recordarTexto);
    try {
      const r = await api<{ message: string }>('POST', 'attendees/remind-code', { email });
      this.recordatorio.set(r.message);
    } catch (e) {
      this.recordatorio.set(estadoDeError(e).mensaje || this.etiquetas.estados.errorTexto);
    }
  }

  protected salir(mensaje = ''): void {
    guardarSesion(this.almacen, null);
    this.sesion.set(null);
    this.misEventos.set(new Set());
    this.mensajeTurno.set('');
    this.numero.set('');
    this.mensajeAcceso.set(mensaje);
  }

  private async entrar(s: SesionAsistente): Promise<void> {
    this.sesion.set(s);
    await Promise.all([this.activos.cargar(), this.cargarMios()]);
    const e = this.activos.error();
    if (e instanceof ErrorApi && e.logout) this.salir(e.message);
  }

  private async cargarMios(): Promise<void> {
    const s = this.sesion();
    if (!s) return;
    try {
      this.misEventos.set(new Set(await api<number[]>('GET', `attendees/${s.id}/events`)));
    } catch (e) {
      if (e instanceof ErrorApi && e.logout) this.salir(e.message);
    }
  }

  /* ---------- Paso II y III ---------- */

  protected poner(campo: keyof FiltrosDia, valor: string): void {
    this.filtros.update((f) => ({ ...f, [campo]: valor || null }));
  }

  protected estado(t: Taller): 'inscrito' | 'lleno' | 'choca' | 'libre' {
    if (this.misEventos().has(t.evento!.id)) return 'inscrito';
    if ((t.cupos ?? 0) <= 0) return 'lleno';
    return chocaCon(t, this.mios()) ? 'choca' : 'libre';
  }

  protected textoChoque(t: Taller): string {
    return rellenar(this.t.hoy.choca, { nombre: chocaCon(t, this.mios())?.nombre ?? '' });
  }

  protected cancelarAria(t: Taller): string {
    return rellenar(this.t.mios.cancelarAria, { nombre: t.nombre });
  }

  protected pedir(tipo: Pendiente['tipo'], taller: Taller): void {
    this.errorTaller.set(null);
    this.pendiente.set({ tipo, taller });
    const d = this.dialogo().nativeElement;
    // jsdom (pruebas) no implementa showModal()
    if (typeof d.showModal === 'function') d.showModal();
    else d.setAttribute('open', '');
  }

  protected cerrarDialogo(): void {
    const d = this.dialogo().nativeElement;
    if (typeof d.close === 'function') d.close();
    else d.removeAttribute('open');
  }

  protected async confirmar(): Promise<void> {
    const p = this.pendiente();
    const s = this.sesion();
    if (!p || !s || this.ocupado()) return;
    const evento = p.taller.evento!.id;
    this.ocupado.set(true);
    this.cerrarDialogo();
    try {
      if (p.tipo === 'inscribir') {
        await api('POST', `attendees/${s.id}/events`, { id_event: evento });
        this.aviso.set(rellenar(this.t.mios.inscritoAviso, { nombre: p.taller.nombre }));
      } else {
        await api('DELETE', `attendees/${s.id}/events/${evento}`);
        this.aviso.set(rellenar(this.t.mios.canceladoAviso, { nombre: p.taller.nombre }));
      }
      // cupos y «mis talleres» frescos (un taller en dos partes cambia las dos)
      await Promise.all([this.activos.cargar(), this.cargarMios()]);
    } catch (e) {
      if (e instanceof ErrorApi && e.logout) return this.salir(e.message);
      const mensaje = e instanceof ErrorApi ? e.fields['id_event'] ?? e.message : estadoDeError(e).mensaje || this.etiquetas.estados.errorTexto;
      this.errorTaller.set({ evento, mensaje });
    } finally {
      this.ocupado.set(false);
      this.pendiente.set(null);
    }
  }

  private enfocar(selector: string): void {
    setTimeout(() => {
      const el = this.host.nativeElement.querySelector<HTMLElement>(selector);
      if (!el) return;
      el.setAttribute('tabindex', '-1');
      el.focus();
    });
  }
}

function opciones(valores: (string | null | undefined)[]): string[] {
  return [...new Set(valores.filter((v): v is string => !!v))].sort((a, b) => a.localeCompare(b, 'es', { numeric: true }));
}
