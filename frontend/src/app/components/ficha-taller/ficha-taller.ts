import { Component, computed, input } from '@angular/core';
import comun from '@contenido/comun.json';
import { PapelPlegado } from '@components/papel-plegado/papel-plegado';
import { SociosTaller } from '@components/socios-taller/socios-taller';
import { rellenar, traducir } from '@core/idioma';
import { Taller } from '@core/modelos';

/* ============ FICHA DE TALLER ============
   La tarjeta de un taller, compartida por /talleres, /booklet e /inscribir-talleres:
   foto (o PapelPlegado si no tiene), chips de nivel y público, duración, tallerista y socios.
   Variantes:
   · galeria     — tarjeta compacta de la cuadrícula (la página la abre ampliada);
   · programa    — con día, hora, salón y observaciones (dentro del <details> del booklet);
   · inscripcion — como programa, con los cupos; las acciones van en <ng-content>.
   Nunca muestra inscritos ni datos de contacto.
   Uso: <app-ficha-taller [taller]="t" variante="programa"/> */

export type VarianteFicha = 'galeria' | 'programa' | 'inscripcion';

@Component({
  selector: 'app-ficha-taller',
  imports: [PapelPlegado, SociosTaller],
  templateUrl: './ficha-taller.html',
  styleUrl: './ficha-taller.css',
  host: { '[attr.data-variante]': 'variante()' },
})
export class FichaTaller {
  readonly taller = input.required<Taller>();
  readonly variante = input<VarianteFicha>('galeria');
  /** Nivel del título (h3 por defecto; h4 dentro de un bloque con h3). */
  readonly nivelTitulo = input<3 | 4>(3);

  protected readonly t = traducir(comun).taller;

  protected readonly nivel = computed(() => this.t.niveles[this.taller().nivel] ?? this.taller().nivel);
  protected readonly publico = computed(() => this.t.publicos[this.taller().publico] ?? this.taller().publico);
  protected readonly duracion = computed(() => rellenar(this.t.minutos, { n: this.taller().duracion }));
  protected readonly fotoAlt = computed(() => rellenar(this.t.fotoAlt, { nombre: this.taller().nombre }));
  protected readonly dia = computed(() => {
    const e = this.taller().evento;
    return e ? this.t.dias[e.dia] : '';
  });
  protected readonly cupos = computed(() => {
    const n = this.taller().cupos ?? 0;
    return n <= 0 ? this.t.sinCupos : n === 1 ? this.t.unCupo : rellenar(this.t.cupos, { n });
  });
}
