import { Component } from '@angular/core';
import contenido from '@contenido/no-encontrada.json';
import { Enlace } from '@core/enlace';
import { traducir } from '@core/idioma';
import { fijarMeta } from '@core/meta';
import { enlaceVisible } from '@core/navegacion';

/* ============ PÁGINA: NO ENCONTRADA (404) ============
   Pantalla sola, sin hero: una hoja «desdoblada» con su línea de valle, el mensaje del
   sitio anterior y los enlaces para volver. La ruta `**` (y `en/**`) la muestra sin
   redirigir; se prerenderiza en /404/ y /en/404/ y el hosting la sirve con estado 404
   (public/.htaccess → ErrorDocument). Contenido: contenido/no-encontrada.json. */

@Component({
  selector: 'app-no-encontrada',
  imports: [Enlace],
  templateUrl: './no-encontrada.html',
  styleUrl: './no-encontrada.css',
})
export class NoEncontrada {
  protected readonly t = traducir(contenido);
  /** Sin los enlaces a páginas apagadas en evento.secciones. */
  protected readonly enlaces = this.t.enlaces.filter((e) => e.href === './' || enlaceVisible(e.href));

  constructor() {
    fijarMeta('no-encontrada');
  }
}
