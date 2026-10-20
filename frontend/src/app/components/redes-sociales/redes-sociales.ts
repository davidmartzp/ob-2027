import { booleanAttribute, Component, input } from '@angular/core';
import evento from '@contenido/evento.json';
import { Enlace } from '@core/enlace';
import { IconoRed } from '@components/icono-red/icono-red';
import { Red } from '@core/modelos';

/* ============ REDES SOCIALES ============
   Botones redondos con el icono de cada red (lista «redes» de contenido/evento.json).
   Uso: <app-redes-sociales/> sobre papel · <app-redes-sociales oscuro/> sobre fondos oscuros. */

@Component({
  selector: 'app-redes-sociales',
  imports: [Enlace, IconoRed],
  host: { '[class.oscuro]': 'oscuro()' },
  template: `
    @for (r of redes; track r.red) {
      <a [appEnlace]="r.href" [attr.aria-label]="r.nombre"><svg [appIconoRed]="r.red"></svg></a>
    }
  `,
  styles: `
    :host{ display:flex; gap:10px; margin-top:18px; }
    a{
      width:42px; height:42px; border-radius:50%; border:1.5px solid var(--tinta-30);
      display:grid; place-items:center; text-decoration:none; transition:.25s;
    }
    a:hover{ border-color:var(--miel); background:var(--ruana); color:var(--tinta); transform:translateY(-3px); }
    svg{ width:17px; height:17px; }
    :host(.oscuro) a{ border-color:color-mix(in srgb, var(--paramo-txt) 30%, transparent); }
  `,
})
export class RedesSociales {
  readonly oscuro = input(false, { transform: booleanAttribute });
  protected readonly redes: Red[] = evento.redes as Red[];
}
