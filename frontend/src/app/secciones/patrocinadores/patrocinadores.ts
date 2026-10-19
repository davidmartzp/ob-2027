import { Component } from '@angular/core';
import { PATROCINADORES } from '../../datos/evento';
import { Separador } from '../../compartido/separador';
import { Reveal } from '../../compartido/reveal';

/* ============ PATROCINADORES ============ */

@Component({
  selector: 'app-patrocinadores',
  imports: [Separador, Reveal],
  template: `
    <section class="patrocinadores" id="patrocinadores">
      <div class="centro">
        <app-separador class="centrado" appReveal/>
        <h2 class="titulo" appReveal="1" i18n="@@patrocinadores.titulo">Nuestros <em>patrocinadores</em></h2>
      </div>
      <div class="patros" appReveal="2">
        @for (p of patrocinadores; track p) {
          <span>{{ p }}</span>
        }
      </div>
    </section>
  `,
  styles: `
    :host{ display:block; }
    .patrocinadores{ padding-top:clamp(50px,7vw,90px); padding-bottom:clamp(50px,7vw,90px); }
    .patrocinadores h2.titulo{ font-size:clamp(30px,4vw,56px); }
    .patros{
      display:flex; flex-wrap:wrap; gap:18px 44px; align-items:center; justify-content:center;
      margin-top:36px;
    }
    .patros span{
      font-family:var(--serif); font-weight:480; font-size:clamp(17px,1.8vw,24px);
      color:var(--tinta-60); letter-spacing:.02em; transition:.25s; cursor:default;
      display:inline-flex; align-items:center; gap:14px;
    }
    .patros span:hover{ color:var(--miel); transform:translateY(-2px); }
    .patros span::before{ content:"✳"; font-size:.7em; color:var(--pajizo); }
  `,
})
export class Patrocinadores {
  protected readonly patrocinadores = PATROCINADORES;
}
