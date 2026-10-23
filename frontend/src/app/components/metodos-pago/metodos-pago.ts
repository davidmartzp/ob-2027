import { Component, inject } from '@angular/core';
import { AvisoPagoServicio } from '@components/aviso-pago/aviso-pago';
import inscripciones from '@contenido/inscripciones.json';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import { MetodoPago } from '@core/modelos';
import { Reveal } from '@core/reveal';

/* ============ MÉTODOS DE PAGO ============
   Lista en contenido/inscripciones.json → metodosPago. Un enlace con "avisar": true
   (la tienda) pasa antes por el aviso de pago; los demás (WhatsApp) abren directo. */

@Component({
  selector: 'app-metodos-pago',
  imports: [Enlace, Reveal],
  template: `
    <div class="metodos">
      @for (m of lista; track m.id; let i = $index) {
        <article class="metodo" [appReveal]="(i % 4) || ''">
          <span class="num">{{ i + 1 }}</span>
          <h4>{{ m.titulo }}</h4>
          <p>{{ m.descripcion }}</p>
          @if (m.enlace; as e) {
            @if (e.avisar) {
              <a class="btn btn-linea" [href]="e.url" (click)="$event.preventDefault(); aviso.pedir(e.url)">{{ e.texto }} ↗</a>
            } @else {
              <a class="btn btn-linea" [appEnlace]="e.url">{{ e.texto }} ↗</a>
            }
          }
        </article>
      }
    </div>
    <p class="nota">{{ t.nota }}</p>
  `,
  styles: `
    :host{ display:block; }
    .metodos{ display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:clamp(16px,2.4vw,26px); }
    .metodo{ border:1.5px dashed var(--tinta-30); background:var(--papel); padding:22px; display:grid; gap:8px; align-content:start; }
    .num{ font-family:var(--mono); font-size:12px; letter-spacing:.16em; color:var(--miel); }
    h4{ font-family:var(--serif); font-weight:520; font-size:22px; line-height:1.15; }
    p{ color:var(--tinta-80); font-size:calc(var(--fs-texto) - 1px); }
    .btn{ justify-self:start; margin-top:6px; }
    .nota{ margin-top:clamp(22px,3vw,32px); border-left:4px solid var(--ruana); padding:6px 0 6px 16px; max-width:72ch; color:var(--tinta-80); }
  `,
})
export class MetodosPago {
  protected readonly t = traducir(inscripciones.metodosPago);
  protected readonly lista = this.t.lista as MetodoPago[];
  protected readonly aviso = inject(AvisoPagoServicio);
}
