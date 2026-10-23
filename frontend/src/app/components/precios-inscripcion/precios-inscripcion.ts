import { Component, inject } from '@angular/core';
import { AvisoPagoServicio } from '@components/aviso-pago/aviso-pago';
import inscripciones from '@contenido/inscripciones.json';
import { traducir } from '@core/idioma';
import { Reveal } from '@core/reveal';

/* ============ PRECIOS DE INSCRIPCIÓN (Paso I) ============
   Etapas de precio (contenido/inscripciones.json → precios.fases) y opciones por día.
   Como en el sitio anterior, una etapa deja de mostrarse al pasar su fechaLimite;
   mientras "porConfirmar" sea true se muestran todas con el aviso ✳ (son de la edición pasada).
   Comprar pasa por el aviso de pago (hay que volver a registrarse). */

const formato = new Intl.NumberFormat('es-CO');

@Component({
  selector: 'app-precios-inscripcion',
  imports: [Reveal],
  styleUrl: './precios-inscripcion.css',
  template: `
    @if (t.porConfirmar) {
      <p class="pend-nota aviso-precios">{{ t.aviso }}</p>
    }
    <div class="fases">
      @for (fase of fases; track fase.fechaLimite) {
        <article class="fase" [class.destacada]="fase.insignia" appReveal>
          @if (fase.insignia) {
            <span class="insignia">{{ fase.insignia }}</span>
          }
          @if (fase.nombre) {
            <h4>{{ fase.nombre }}</h4>
          }
          <p class="precio"><span class="moneda">{{ t.moneda }}</span> {{ precio(fase.precio) }}</p>
          <p class="hasta">{{ fase.hasta }}</p>
          <ul>
            @for (item of fase.incluye; track item) {
              <li>{{ item }}</li>
            }
          </ul>
        </article>
      }
    </div>
    <p class="comprar">
      <a class="btn btn-naranja" [href]="t.tiendaUrl" (click)="pagar($event, t.tiendaUrl)">{{ t.comprar }} ↗</a>
    </p>

    <h3 class="por-dia-titulo">{{ t.porDiaTitulo }}</h3>
    <div class="por-dia">
      @for (dia of t.porDia; track dia.url) {
        <article class="dia" appReveal>
          <p class="dia-nombre"><b>{{ dia.dia }}</b> <span>{{ dia.fecha }}</span></p>
          <p class="precio"><span class="moneda">{{ t.moneda }}</span> {{ precio(dia.precio) }}</p>
          <p class="descripcion">{{ dia.descripcion }}</p>
          <a class="btn btn-linea" [href]="dia.url" (click)="pagar($event, dia.url)">{{ t.seleccionar }} ↗</a>
        </article>
      }
    </div>
  `,
})
export class PreciosInscripcion {
  protected readonly t = traducir(inscripciones.precios);
  private readonly aviso = inject(AvisoPagoServicio);

  /** Las etapas vigentes (o todas, si los precios están por confirmar). */
  protected readonly fases = this.t.fases.filter((f) => this.t.porConfirmar || new Date(f.fechaLimite) >= new Date());

  protected precio(valor: number): string {
    return formato.format(valor);
  }

  protected pagar(evento: Event, url: string): void {
    evento.preventDefault();
    this.aviso.pedir(url);
  }
}
