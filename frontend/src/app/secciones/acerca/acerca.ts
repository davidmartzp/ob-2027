import { Component } from '@angular/core';
import { Separador } from '../../compartido/separador';
import { Reveal } from '../../compartido/reveal';

/* ============ ACERCA ============ */

@Component({
  selector: 'app-acerca',
  imports: [Separador, Reveal],
  templateUrl: './acerca.html',
  styleUrl: './acerca.css',
})
export class Acerca {}
