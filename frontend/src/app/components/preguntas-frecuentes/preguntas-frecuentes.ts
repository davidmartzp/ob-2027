import { Component } from '@angular/core';
import evento from '@contenido/evento.json';
import preguntas from '@contenido/preguntas.json';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';

/* ============ PREGUNTAS FRECUENTES ============
   Contenido en contenido/preguntas.json. Acordeón con <details> nativo (todo el texto
   sale en el prerender). El enlace de contacto es el WhatsApp de contenido/evento.json. */

@Component({
  selector: 'app-preguntas-frecuentes',
  imports: [Enlace],
  template: `
    <div class="lista">
      @for (p of t.items; track $index) {
        <details name="pregunta">
          <summary>{{ p.pregunta }}</summary>
          <p>{{ p.respuesta }}</p>
        </details>
      }
    </div>
    <p class="contacto">{{ t.contactoTexto }} <a [appEnlace]="whatsapp">{{ t.contactoEnlace }} ↗</a></p>
  `,
  styles: `
    :host{ display:block; }
    .lista{ border-top:1.5px solid var(--tinta); columns:2 380px; column-gap:clamp(24px,4vw,56px); }
    details{ border-bottom:1px dashed var(--tinta-30); break-inside:avoid; }
    summary{
      cursor:pointer; list-style:none; padding:16px 34px 16px 0; position:relative;
      font-family:var(--serif); font-weight:520; font-size:clamp(19px,1.8vw,22px); line-height:1.25;
    }
    summary::-webkit-details-marker{ display:none; }
    summary::after{ content:"+"; position:absolute; right:4px; top:14px; font-family:var(--mono); font-size:20px; color:var(--miel); transition:transform .25s var(--ease); }
    details[open] summary::after{ transform:rotate(45deg); }
    summary:focus-visible{ outline:2px dashed var(--miel); outline-offset:3px; }
    p{ color:var(--tinta-80); padding:0 0 18px; max-width:62ch; }
    .contacto{ margin-top:clamp(22px,3vw,32px); color:var(--tinta-80); }
    .contacto a{ color:var(--miel); font-weight:600; }
  `,
})
export class PreguntasFrecuentes {
  protected readonly t = traducir(preguntas);
  protected readonly whatsapp = evento.redes.find((r) => r.red === 'whatsapp')?.href ?? '#';
}
