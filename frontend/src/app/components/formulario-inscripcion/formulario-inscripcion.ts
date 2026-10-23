import { Component, computed, ElementRef, inject, signal } from '@angular/core';
import inscripciones from '@contenido/inscripciones.json';
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
    return this.errores().find((e) => e.campo === campo)?.mensaje ?? '';
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
    try {
      const respuesta = await enviarInscripcion(construirEnvio(this.datos()));
      this.correoEnviado.set(this.datos().email.trim());
      this.simulado.set(respuesta.simulado);
      this.estado.set('enviado');
      this.enfocar('.exito h3');
    } catch {
      this.errorEnvio.set(true);
      this.estado.set('editando');
    }
  }

  protected nuevaInscripcion(): void {
    this.datos.set(datosVacios());
    this.intentados.set(new Set());
    this.paso.set(1);
    this.estado.set('editando');
    this.enfocar('.paso-titulo');
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
      el.scrollIntoView({ block: 'center', behavior: 'smooth' });
    });
  }
}
