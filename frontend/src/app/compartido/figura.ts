import { Component, input } from '@angular/core';
import { TipoFigura } from '../datos/modelos';

/* ============ FIGURA DE PAPEL DE RESPALDO ============
   Se muestra en la vista flotante cuando una actividad no tiene foto. */

@Component({
  selector: 'svg[appFigura]',
  host: { viewBox: '0 0 120 120', fill: 'none' },
  template: `
    @switch (figura()) {
      @case ('grulla') { <svg:path d="M60 14 L96 60 L60 106 L24 60 Z" fill="#B4552D"/><svg:path d="M60 14 L60 106 M24 60 L96 60" stroke="#23211A" stroke-dasharray="4 4"/><svg:path d="M60 14 L78 60 L60 74 L42 60 Z" fill="#ECE7D8" stroke="#23211A"/> }
      @case ('rana') { <svg:path d="M20 90 L60 30 L100 90 Z" fill="#87947B" stroke="#23211A"/><svg:path d="M60 30 L60 90 M20 90 L60 66 L100 90" stroke="#23211A" stroke-dasharray="4 4" fill="none"/><svg:circle cx="45" cy="52" r="4" fill="#23211A"/><svg:circle cx="75" cy="52" r="4" fill="#23211A"/> }
      @case ('zorro') { <svg:path d="M24 30 L60 100 L96 30 L60 48 Z" fill="#C79A3B" stroke="#23211A"/><svg:path d="M24 30 L12 12 L40 24 M96 30 L108 12 L80 24" fill="#B4552D" stroke="#23211A"/><svg:path d="M60 48 L60 100" stroke="#23211A" stroke-dasharray="4 4"/> }
      @case ('pez') { <svg:path d="M14 60 L62 26 L106 60 L62 94 Z" fill="#B4552D" stroke="#23211A"/><svg:path d="M62 26 L62 94 M14 60 L106 60" stroke="#ECE7D8" stroke-dasharray="4 4" fill="none"/><svg:circle cx="38" cy="54" r="4" fill="#ECE7D8"/> }
      @case ('tigre') { <svg:path d="M60 16 L100 44 L88 100 L32 100 L20 44 Z" fill="#C79A3B" stroke="#23211A"/><svg:path d="M60 16 L60 100 M20 44 L100 44" stroke="#23211A" stroke-dasharray="4 4" fill="none"/><svg:path d="M40 62 L52 62 M68 62 L80 62" stroke="#23211A" stroke-width="3"/> }
    }
  `,
})
export class Figura {
  readonly figura = input<TipoFigura | null>(null, { alias: 'appFigura' });
}
