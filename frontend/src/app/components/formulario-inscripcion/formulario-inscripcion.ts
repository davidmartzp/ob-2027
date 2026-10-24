import { Component, computed, ElementRef, inject, signal } from '@angular/core';
import inscripciones from '@contenido/inscripciones.json';
import { ErrorApi } from '@core/api';
import { traducir } from '@core/idioma';
import {
  CampoInscripcion, construirEnvio, DatosInscripcion, datosVacios, enviarInscripcion,
  ErrorCampo, requiereComprobante, TOTAL_PASOS, validarPaso,
} from '@core/registro';

/* ============ FORMULARIO DE INSCRIPCIÓN (wizard de 5 pasos) ============
   Personal → Contacto → Pago → Participación → Opciones. La lógica (validación,
   campos que dependen de otros, envío) está en core/registro.ts; los textos y
   las opciones, en contenido/inscripciones.json → formulario.
   Avanzar exige que el paso sea válido; volver a un paso anterior, no.
   Los errores salen junto a cada campo y resumidos arriba (role="alert"). */

type Estado = 'editando' | 'enviando' | 'enviado';

/** Paso del wizard en el que está cada campo que puede rechazar el servicio (fields del 422). */
const PASO_DE_CAMPO: Partial<Record<string, number>> = {
  name: 1, lastname: 1, age: 1, idname: 1,
  country: 2, city: 2, email: 2, phone: 2,
  paymethod: 3, paydate: 3, receipt: 3,
  days: 4, companion: 4,
  info: 5, medium: 5, othermedium: 5, origamigroup: 5, cardgroup: 5, chiva_companions: 5,
};
/** Nombre del campo del servicio → campo del formulario, cuando no coinciden. */
const CAMPO_DEL_SERVICIO: Partial<Record<string, CampoInscripcion>> = {
  medium: 'info', othermedium: 'otherInfo', cardgroup: 'cardGroup',
};

@Component({
  selector: 'app-formulario-inscripcion',
  templateUrl: './formulario-inscripcion.html',
  styleUrl: './formulario-inscripcion.css',
})
export class FormularioInscripcion {
  private readonly contenido = traducir(inscripciones);
  protected readonly t = this.contenido.formulario;
  protected readonly paises = this.contenido.paises;
  protected readonly total = TOTAL_PASOS;
  protected readonly numeros = Array.from({ length: TOTAL_PASOS }, (_, i) => i + 1);

  protected readonly datos = signal<DatosInscripcion>(datosVacios());
  protected readonly paso = signal(1);
  protected readonly estado = signal<Estado>('editando');
  protected readonly correoEnviado = signal('');
  protected readonly simulado = signal(false);
  protected readonly errorEnvio = signal(false);
  /** Mensaje del servicio cuando lo trae (si no, el genérico de inscripciones.json). */
  protected readonly mensajeEnvio = signal('');
  /** Número de participante devuelto por el servicio (null = aún no le corresponde). */
  protected readonly numero = signal<number | null>(null);
  /** Errores por campo que devolvió el servicio (422); se borran al corregir el campo. */
  private readonly erroresServidor = signal<Partial<Record<CampoInscripcion, string>>>({});
  /** Pasos en los que ya se intentó avanzar: solo ahí se muestran los errores (y se recalculan al escribir). */
  private readonly intentados = signal<ReadonlySet<number>>(new Set());

  protected readonly errores = computed<ErrorCampo[]>(() =>
    this.intentados().has(this.paso()) ? validarPaso(this.paso(), this.datos(), this.t) : []);
  protected readonly conComprobante = computed(() => requiereComprobante(this.datos().paymethod));
  protected readonly notaPago = computed(() => {
    const tipo = this.t.tiposPago.find((p) => p.valor === this.datos().paymethod);
    return tipo && 'nota' in tipo ? tipo.nota : '';
  });
  protected readonly pasoDe = computed(() =>
    this.t.pasoDe.replace('{n}', String(this.paso())).replace('{total}', String(this.total)));

  private readonly host = inject<ElementRef<HTMLElement>>(ElementRef);

  /** Escribe un campo y vacía los que dependen de él (como el formulario original). */
  protected poner<K extends CampoInscripcion>(campo: K, valor: DatosInscripcion[K]): void {
    if (this.erroresServidor()[campo]) this.erroresServidor.update(({ [campo]: _, ...resto }) => resto as typeof resto);
    this.datos.update((d) => {
      const nuevo = { ...d, [campo]: valor };
      if (campo === 'info' && valor !== 'Otro') nuevo.otherInfo = '';
      if (campo === 'group' && !valor) nuevo.origamigroup = '';
      if (campo === 'cards' && !valor) nuevo.cardGroup = '';
      if (campo === 'go_to_chiva') { nuevo.chiva_accompanied = null; nuevo.chiva_companions = ''; }
      if (campo === 'chiva_accompanied' && !valor) nuevo.chiva_companions = '';
      return nuevo;
    });
  }

  /** Solo dígitos y como mucho `max` (edad: 2; teléfono: 15). */
  protected ponerDigitos(campo: 'age' | 'phone', evento: Event, max: number): void {
    const entrada = evento.target as HTMLInputElement;
    const limpio = entrada.value.replace(/\D/g, '').slice(0, max);
    entrada.value = limpio;
    this.poner(campo, limpio);
  }

  protected texto(evento: Event): string {
    return (evento.target as HTMLInputElement | HTMLSelectElement).value;
  }

  protected marcado(evento: Event): boolean {
    return (evento.target as HTMLInputElement).checked;
  }

  protected error(campo: CampoInscripcion): string {
    return this.errores().find((e) => e.campo === campo)?.mensaje ?? this.erroresServidor()[campo] ?? '';
  }

  protected siguiente(): void {
    if (this.validar(this.paso())) this.mostrar(this.paso() + 1);
  }

  protected anterior(): void {
    if (this.paso() > 1) this.mostrar(this.paso() - 1);
  }

  /** Volver siempre se puede; avanzar exige que los pasos intermedios sean válidos. */
  protected irA(destino: number): void {
    if (destino <= this.paso()) return this.mostrar(destino);
    for (let p = this.paso(); p < destino; p++) {
      if (!this.validar(p)) return;
    }
    this.mostrar(destino);
  }

  protected async enviar(): Promise<void> {
    if (this.estado() === 'enviando') return;
    for (let p = 1; p <= this.total; p++) {
      if (!this.validar(p)) return;
    }
    this.estado.set('enviando');
    this.errorEnvio.set(false);
    this.mensajeEnvio.set('');
    this.erroresServidor.set({});
    try {
      const respuesta = await enviarInscripcion(construirEnvio(this.datos()));
      this.correoEnviado.set(this.datos().email.trim());
      this.simulado.set(respuesta.simulado);
      this.numero.set(respuesta.code);
      this.estado.set('enviado');
      this.enfocar('.exito h3');
    } catch (e) {
      this.errorEnvio.set(true);
      this.estado.set('editando');
      if (e instanceof ErrorApi) this.mostrarErroresServidor(e);
    }
  }

  protected nuevaInscripcion(): void {
    this.datos.set(datosVacios());
    this.intentados.set(new Set());
    this.erroresServidor.set({});
    this.numero.set(null);
    this.paso.set(1);
    this.estado.set('editando');
    this.enfocar('.paso-titulo');
  }

  /** 422 del servicio: cada mensaje junto a su campo y el wizard en el primer paso con errores; lo escrito se conserva. */
  private mostrarErroresServidor(e: ErrorApi): void {
    this.mensajeEnvio.set(e.message);
    const porCampo: Partial<Record<CampoInscripcion, string>> = {};
    let primero: { campo: CampoInscripcion; paso: number } | null = null;
    for (const [servicio, mensaje] of Object.entries(e.fields)) {
      const campo = (CAMPO_DEL_SERVICIO[servicio] ?? servicio) as CampoInscripcion;
      const paso = PASO_DE_CAMPO[servicio];
      if (!paso) continue;
      porCampo[campo] = mensaje;
      if (!primero || paso < primero.paso) primero = { campo, paso };
    }
    this.erroresServidor.set(porCampo);
    if (primero) {
      this.paso.set(primero.paso);
      this.enfocar(`#insc-${primero.campo}`);
    }
  }

  /** Marca el paso como intentado; si tiene errores, se queda en él y enfoca el primer campo. */
  private validar(p: number): boolean {
    this.intentados.update((s) => new Set(s).add(p));
    const errores = validarPaso(p, this.datos(), this.t);
    if (!errores.length) return true;
    this.paso.set(p);
    this.enfocar(`#insc-${errores[0].campo}`);
    return false;
  }

  private mostrar(p: number): void {
    this.paso.set(p);
    this.enfocar('.paso-titulo');
  }

  /** Tras repintar: lleva el foco (y la vista) al elemento. */
  private enfocar(selector: string): void {
    setTimeout(() => {
      const el = this.host.nativeElement.querySelector<HTMLElement>(selector);
      if (!el) return;
      if (!el.hasAttribute('tabindex') && !/^(INPUT|SELECT|BUTTON|TEXTAREA)$/.test(el.tagName)) el.setAttribute('tabindex', '-1');
      el.focus({ preventScroll: true });
      el.scrollIntoView?.({ block: 'center', behavior: 'smooth' }); // jsdom (pruebas) no lo tiene
    });
  }
}
