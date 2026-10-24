import { Component, ElementRef, input, signal, viewChild } from '@angular/core';
import { Enlace } from '@core/enlace';
import { IconoRed } from '@components/icono-red/icono-red';
import { PapelPlegado } from '@components/papel-plegado/papel-plegado';
import { rellenar, sinEtiquetas, traducir } from '@core/idioma';
import { Invitado } from '@core/modelos';
import invitados from '@contenido/invitados.json';

/* ============ TARJETA DE INVITADO ============
   Se monta sobre el propio <article> para que :nth-child(even)
   alterne la foto a la derecha entre tarjetas hermanas.
   Con «modelos» (invitados.json, hasta 4), una tercera columna a la derecha con las
   fotos de sus modelos apiladas, foto sobre foto —solo en escritorio, >1200px—: la que tiene el
   cursor (o el foco) pasa al frente y, al pulsarla, se amplía en un <dialog>. La última de
   la lista queda encima de la pila. En tableta y móvil (≤1200px) es un mosaico bajo la
   bio (4 en fila / 2 × 2) que se toca para ampliar. Un modelo sin foto muestra el aviso ✳.
   Uso: <article appInvitadoCard [invitado]="inv"></article> */

@Component({
  selector: 'article[appInvitadoCard]',
  host: { class: 'invitado-card', '[class.con-modelos]': '!!invitado().modelos?.length' },
  imports: [Enlace, IconoRed, PapelPlegado],
  styleUrl: './invitado-card.css',
  template: `
    <figure class="inv-foto">
      <img [src]="invitado().foto" [alt]="invitado().fotoAlt" [class.foto-real]="invitado().retrato === 'foto'" [class.logo]="invitado().retrato === 'logo'">
    </figure>
    <div class="inv-info">
      <span class="origen">{{ origen[invitado().origen] }}</span>
      <h3 class="nombre rico" [innerHTML]="invitado().nombre"></h3>
      <span class="rol">{{ invitado().rol }}</span>
      <p class="bio">
        {{ invitado().bio }}
        @if (invitado().provisional) {
          <b class="pend-nota">{{ t.bioProvisional }}</b>
        }
      </p>
      @if (invitado().enlace; as e) {
        <a class="enlace" [appEnlace]="e.href">
          @if (e.tipo === 'instagram') { <svg appIconoRed="instagram"></svg> } @else { <span aria-hidden="true">✉</span> }
          {{ e.texto }}
        </a>
      }
    </div>
    @if (invitado().modelos?.length) {
      <!-- solo en tableta y móvil (sin hover): invita a tocar -->
      <p class="modelos-rotulo" aria-hidden="true">{{ t.modelosRotulo }}</p>
      <ul class="modelos" [attr.aria-label]="t.modelosAria">
        @for (m of invitado().modelos!.slice(0, 4); track $index) {
          <li>
            @if (m.foto; as foto) {
              <button type="button" aria-haspopup="dialog" [attr.aria-label]="ampliarAria(m.alt)" (click)="ampliar(foto, m.alt, m.nombre)">
                <img [src]="foto" [alt]="m.alt ?? ''" loading="lazy" decoding="async">
              </button>
            } @else {
              <span class="pendiente"><app-papel-plegado/><span class="pend-nota">{{ t.modeloPendiente }}</span></span>
            }
          </li>
        }
      </ul>
      <dialog #zoom class="zoom" (click)="alHacerClic($event)" [attr.aria-label]="ampliado()?.alt">
        <button type="button" class="cerrar" [attr.aria-label]="t.cerrar" (click)="cerrar()">×</button>
        @if (ampliado(); as a) {
          <figure>
            <img [src]="a.foto" [alt]="a.alt">
            <!-- ficha de la obra, en la esquina inferior derecha: título y autor -->
            @if (a.nombre) {
              <figcaption><b>{{ a.nombre }}</b><span>{{ autor() }}</span></figcaption>
            }
          </figure>
        }
      </dialog>
    }
  `,
})
export class InvitadoCard {
  readonly invitado = input.required<Invitado>();
  protected readonly t = traducir(invitados.tarjeta);
  protected readonly origen: Record<string, string> = this.t.origen;

  /** Modelo ampliado en el diálogo de zoom. */
  protected readonly ampliado = signal<{ foto: string; alt: string; nombre?: string } | null>(null);
  /** Autor de los modelos: el nombre del invitado sin el énfasis (<em>). */
  protected readonly autor = (): string => sinEtiquetas(this.invitado().nombre);
  private readonly zoom = viewChild<ElementRef<HTMLDialogElement>>('zoom');

  protected ampliarAria(alt?: string): string {
    return rellenar(this.t.ampliarModelo, { alt: alt ?? '' });
  }

  protected ampliar(foto: string, alt?: string, nombre?: string): void {
    this.ampliado.set({ foto, alt: alt ?? '', nombre });
    const d = this.zoom()?.nativeElement;
    if (!d || d.open) return;
    // jsdom (pruebas) no implementa showModal()
    if (typeof d.showModal === 'function') d.showModal();
    else d.setAttribute('open', '');
  }

  protected cerrar(): void {
    const d = this.zoom()?.nativeElement;
    if (!d) return;
    if (typeof d.close === 'function') d.close();
    else d.removeAttribute('open');
  }

  /** Clic en el fondo oscuro (fuera de la foto) cierra. */
  protected alHacerClic(e: MouseEvent): void {
    if (e.target === this.zoom()?.nativeElement) this.cerrar();
  }
}
