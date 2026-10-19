import { Component } from '@angular/core';
import { Separador } from '../../compartido/separador';
import { Reveal } from '../../compartido/reveal';

/* ============ TALLERISTA ============ */

@Component({
  selector: 'app-tallerista',
  imports: [Separador, Reveal],
  template: `
    <section class="tallerista" id="tallerista">
      <div class="inner">
        <div>
          <app-separador montana appReveal frase="¡tu modelo puede ser el próximo paso!" i18n-frase="@@tallerista.frase"/>
          <h2 class="titulo" appReveal="1" i18n="@@tallerista.titulo">¡Comparte tu pasión!<br><em>Sé tallerista</em> en nuestro evento</h2>
          <p appReveal="2" i18n="@@tallerista.texto">Buscamos mentes creativas como la tuya para inspirar y enseñar en nuestro próximo evento. Si tienes un modelo de origami para enseñar que quieras compartir, ¡queremos conocer tu propuesta!</p>
          <a class="btn btn-noche" appReveal="3" href="#" i18n="@@tallerista.boton">Proponer un taller ahora</a>
        </div>
        <figure class="foto" appReveal="2">
          <img src="assets/taller-manos.png" alt="Manos de varias personas plegando papel juntas en una mesa de taller" i18n-alt="@@tallerista.fotoAlt">
        </figure>
      </div>
    </section>
  `,
  styles: `
    :host{ display:block; }
    .tallerista{
      background:var(--ruana); color:var(--tinta); overflow:hidden;
    }
    .tallerista .inner{ display:grid; grid-template-columns:1fr .85fr; gap:clamp(30px,5vw,80px); align-items:center; }
    .tallerista h2.titulo{ color:var(--tinta); }
    .tallerista h2.titulo em{ color:var(--paramo); }
    .tallerista p{ color:rgba(39,37,27,.82); max-width:48ch; margin-top:18px; }
    .tallerista .foto{
      transform:rotate(2.5deg); border:6px solid #fff;
      box-shadow:16px 16px 0 var(--paramo);
    }
    .tallerista .foto img{ aspect-ratio:4/3; object-fit:cover; }
    .tallerista .btn.btn-noche{ background:var(--paramo); border-color:var(--paramo); color:var(--papel); margin-top:30px; }
    .tallerista .btn.btn-noche:hover{ background:var(--papel); color:var(--paramo); border-color:var(--papel); }
    .tallerista .separador{ color:var(--tinta); --color-anotacion:var(--paramo); }

    @media (max-width: 1020px){
      .tallerista .inner{ grid-template-columns:1fr; }
    }
    @media (max-width: 760px){
      .tallerista img{ transform:rotate(0); box-shadow:10px 10px 0 var(--paramo); }
    }
  `,
})
export class Tallerista {}
