import { booleanAttribute, Component, input } from '@angular/core';

/* ============ SEPARADOR DE SECCIÓN ============
   Línea de pliegue cotada —valle (guiones) o montaña (guion-punto)— con una
   frase a mano opcional justo debajo. Alternar valle/montaña por la página;
   máximo una frase a mano por sección. Las frases a mano son rolas y van SOLO en
   español, también en /en/ (en el JSON, un texto sin { es, en }; aquí, lang="es").
   Uso: <app-separador/> · <app-separador montana frase="aquí nadie dobla solo ✳"/>
   Color de la frase: variable CSS --color-anotacion (por defecto, miel). */

@Component({
  selector: 'app-separador',
  host: {
    class: 'separador',
    '[class.montana]': 'montana()',
  },
  template: `
    <span class="linea" aria-hidden="true"></span>
    @if (frase()) {
      <span class="anotacion" lang="es">{{ frase() }}</span>
    }
  `,
})
export class Separador {
  readonly montana = input(false, { transform: booleanAttribute });
  readonly frase = input<string>();
}
