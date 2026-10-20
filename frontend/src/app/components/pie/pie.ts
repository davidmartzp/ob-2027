import { Component } from '@angular/core';
import { Menu } from '@components/menu/menu';
import { RedesSociales } from '@components/redes-sociales/redes-sociales';
import comun from '@contenido/comun.json';
import evento from '@contenido/evento.json';
import pie from '@contenido/pie.json';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';

/* ============ FOOTER — contacto e inscripciones (#inscribete) ============
   Contacto y sede: contenido/evento.json · enlaces rápidos: lista «pie» de contenido/menu.json. */

@Component({
  selector: 'app-pie',
  imports: [Enlace, Menu, RedesSociales],
  templateUrl: './pie.html',
  styleUrl: './pie.css',
})
export class Pie {
  protected readonly t = traducir(pie);
  protected readonly comun = traducir(comun);
  protected readonly evento = traducir(evento);
}
