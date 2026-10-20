import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { Doblez } from '@components/doblez/doblez';
import { VistaFlotante } from '@components/vista-flotante/vista-flotante';
import { Cabecera } from '@components/cabecera/cabecera';
import { Leyenda } from '@components/leyenda/leyenda';
import { Pie } from '@components/pie/pie';

/* ============ MARCO COMÚN ============
   Cabecera, leyenda y pie se comparten; cada página (paginas/) va en el outlet.
   Rutas en app.routes.ts; todas se prerenderizan (una carpeta por ruta en dist/). */

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, Cabecera, VistaFlotante, Doblez, Leyenda, Pie],
  templateUrl: './app.html',
})
export class App {}
