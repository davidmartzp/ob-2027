import { Component } from '@angular/core';
import { traducir } from '@core/idioma';
import { Separador } from '@components/separador/separador';
import { Reveal } from '@core/reveal';
import { InvitadoCard } from '@components/invitado-card/invitado-card';
import invitados from '@contenido/invitados.json';
import { Invitado } from '@core/modelos';

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
          <h2 class="titulo rico" appReveal="1" [innerHTML]="t.titulo"></h2>
          @if (hayProvisionales) {
            <span class="aviso" appReveal="2">{{ t.aviso }}</span>
          }
        </div>
        <p class="lema" appReveal="2">{{ t.lema }}</p>
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
  private readonly contenido = traducir(invitados);
  protected readonly t = this.contenido.seccion;
  protected readonly invitados = this.contenido.lista as Invitado[];
  protected readonly hayProvisionales = this.invitados.some((i) => i.provisional);
}
