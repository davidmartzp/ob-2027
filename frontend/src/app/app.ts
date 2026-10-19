import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { Doblez } from './compartido/doblez';
import { VistaFlotante } from './efectos/vista-flotante';
import { Cabecera } from './secciones/cabecera/cabecera';
import { Leyenda } from './secciones/leyenda/leyenda';
import { Pie } from './secciones/pie/pie';

/* ============ MARCO COMÚN ============
   Cabecera, leyenda y pie se comparten; cada página (paginas/) va en el outlet.
   Rutas en app.routes.ts; todas se prerenderizan (una carpeta por ruta en dist/). */

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, Cabecera, VistaFlotante, Doblez, Leyenda, Pie],
  templateUrl: './app.html',
})
export class App {}
