import { Component } from '@angular/core';
import { ACTIVIDADES, DESTACADAS } from '../../datos/actividades';
import { Separador } from '../../compartido/separador';
import { Reveal } from '../../compartido/reveal';
import { ActividadItem } from './actividad-item';

/* ============ ACTIVIDADES (hero de sección) ============
   La home no lista todo: invita a la página de actividades (buscador y fichas)
   y destaca unas pocas (DESTACADAS en datos/actividades.ts). */

@Component({
  selector: 'app-actividades',
  imports: [Separador, Reveal, ActividadItem],
  template: `
    <section class="actividades" id="actividades">
      <div class="sec-head">
        <div>
          <app-separador appReveal frase="¡del chocogami a la chiva, hay pa' todos los gustos!" i18n-frase="@@actividades.frase"/>
          <h2 class="titulo" appReveal="1" i18n="@@actividades.titulo">Nuestras <em>actividades</em></h2>
        </div>
        <p class="lema" appReveal="2" i18n="@@actividades.lema">Navega por todo lo que tenemos preparado para ti</p>
      </div>

      <div class="cuerpo">
        <div>
          <p class="intro" appReveal i18n="@@actividades.intro">Del reto dulce del Chocogami a la rumba de la chiva: busca, filtra y encuentra tu pliegue del 4 al 6 de junio.</p>
          <div class="cta" appReveal="1">
            <a class="btn" href="actividades" i18n="@@actividades.cta">Explorar las actividades →</a>
          </div>
        </div>
        <div>
          <span class="mono destacadas-tag" appReveal i18n="@@actividades.destacadas">Destacadas —</span>
          <ul class="lista-actividades">
            @for (a of destacadas; track a.id) {
              <li appActividad [actividad]="a" [appReveal]="$index + 1"></li>
            }
          </ul>
        </div>
      </div>
    </section>
  `,
  styles: `
    :host{ display:block; }
    .actividades{ background:var(--frailejon); }
    .actividades h2.titulo em{ color:var(--paramo); }
    .actividades .separador{ color:var(--tinta); --color-anotacion:var(--paramo); }
    .cuerpo{ display:grid; grid-template-columns:.9fr 1.1fr; gap:clamp(30px,5vw,80px); align-items:start; }
    .intro{ color:var(--tinta-80); max-width:46ch; }
    .cta{ display:flex; flex-direction:column; align-items:flex-start; gap:14px; margin-top:28px; }
    .destacadas-tag{ font-size:12px; letter-spacing:.16em; text-transform:uppercase; color:var(--tinta-80); }
    .destacadas-tag{ display:block; margin-bottom:12px; }
    .lista-actividades{ list-style:none; border-top:1.5px solid var(--tinta); --color-enfasis:var(--paramo); }
    .lista-actividades li{ border-bottom:1.5px solid var(--tinta); transition:opacity .15s; }
    .lista-actividades:hover > li:not(:hover){ opacity:.3; }

    @media (max-width: 1020px){
      .cuerpo{ grid-template-columns:1fr; }
    }
  `,
})
export class Actividades {
  protected readonly destacadas = DESTACADAS.map((id) => ACTIVIDADES.find((a) => a.id === id)!);
}
