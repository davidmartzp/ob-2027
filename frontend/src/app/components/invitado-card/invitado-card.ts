import { Component, input } from '@angular/core';
import { Enlace } from '@core/enlace';
import { IconoRed } from '@components/icono-red/icono-red';
import { traducir } from '@core/idioma';
import { Invitado } from '@core/modelos';
import invitados from '@contenido/invitados.json';

/* ============ TARJETA DE INVITADO ============
   Se monta sobre el propio <article> para que :nth-child(even)
   alterne la foto a la derecha entre tarjetas hermanas.
   Uso: <article appInvitadoCard [invitado]="inv"></article> */

@Component({
  selector: 'article[appInvitadoCard]',
  host: { class: 'invitado-card' },
  imports: [Enlace, IconoRed],
  styleUrl: './invitado-card.css',
  template: `
    <figure class="inv-foto">
      <img [src]="invitado().foto" [alt]="invitado().fotoAlt" [class.foto-real]="invitado().retrato === 'foto'" [class.logo]="invitado().retrato === 'logo'">
    </figure>
    <div class="inv-info">
      <span class="num">{{ invitado().numero }} <span class="etiqueta">{{ origen[invitado().origen] }}</span></span>
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
  `,
})
export class InvitadoCard {
  readonly invitado = input.required<Invitado>();
  protected readonly t = traducir(invitados.tarjeta);
  protected readonly origen: Record<string, string> = this.t.origen;
}
