import { Component, input } from '@angular/core';
import { Enlace } from '../../compartido/enlace';
import { IconoRed } from '../../compartido/icono-red';
import { Invitado } from '../../datos/modelos';

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
      <span class="num">{{ invitado().numero }} <span class="etiqueta">@if (invitado().origen === 'Internacional') {<ng-container i18n="@@invitados.origen.internacional">Internacional</ng-container>} @else {<ng-container i18n="@@invitados.origen.nacional">Nacional</ng-container>}</span></span>
      <h3 class="nombre">{{ invitado().nombre.antes }}@if (invitado().nombre.enfasis) {<em>{{ invitado().nombre.enfasis }}</em>}{{ invitado().nombre.despues }}</h3>
      <span class="rol">{{ invitado().rol }}</span>
      <p class="bio">
        {{ invitado().bio }}
        @if (invitado().provisional) {
          <b class="pend-nota" i18n="@@invitados.bioProvisional">✳ biografía provisional — se actualiza al confirmar el nombre.</b>
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
}
