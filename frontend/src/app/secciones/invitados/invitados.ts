import { Component } from '@angular/core';
import { INVITADOS } from '../../datos/invitados';
import { Separador } from '../../compartido/separador';
import { Reveal } from '../../compartido/reveal';
import { InvitadoCard } from './invitado-card';

/* ============ INVITADOS ============ */

@Component({
  selector: 'app-invitados',
  imports: [Separador, Reveal, InvitadoCard],
  styleUrl: './invitados.css',
  template: `
    <section class="invitados" id="invitados">
      <div class="sec-head">
        <div>
          <app-separador montana appReveal/>
          <h2 class="titulo" appReveal="1" i18n="@@invitados.titulo">Nuestros <em>invitados</em></h2>
          @if (hayProvisionales) {
            <span class="aviso" appReveal="2" i18n="@@invitados.aviso">✳ listado provisional — nombres por confirmar</span>
          }
        </div>
        <p class="lema" appReveal="2" i18n="@@invitados.lema">Vienen a compartir con la comunidad, no solo a enseñar</p>
      </div>

      <div class="lista-invitados">
        @for (inv of invitados; track inv.id) {
          <article appInvitadoCard [invitado]="inv" [appReveal]="$index % 4 || ''"></article>
        }
      </div>
    </section>
  `,
})
export class Invitados {
  protected readonly invitados = INVITADOS;
  protected readonly hayProvisionales = INVITADOS.some((i) => i.provisional);
}
