import { Component, computed, ElementRef, input, signal, viewChild } from '@angular/core';
import { rellenar } from '@core/idioma';

/* ============ GALERÍA DE FOTOS ============
   Cuadrícula de miniaturas (loading="lazy") y vista ampliada en un <dialog> nativo:
   flechas ← → (teclado y botones) para pasar de foto, Escape o × para cerrar
   (el navegador atrapa el foco y lo devuelve a la miniatura). La foto grande solo se
   pide al abrirla. Textos de los botones por entrada (cada página los trae en su JSON).
   Uso: <app-galeria-fotos [fotos]="fotos" [textos]="t.parche"/> */

export interface FotoGaleria {
  mini: string;
  grande: string;
  alt: string;
}

export interface TextosGaleria {
  abrir: string;
  cerrar: string;
  anterior: string;
  siguiente: string;
  contador: string;
  dialogoAria: string;
}

@Component({
  selector: 'app-galeria-fotos',
  templateUrl: './galeria-fotos.html',
  styleUrl: './galeria-fotos.css',
})
export class GaleriaFotos {
  readonly fotos = input.required<FotoGaleria[]>();
  readonly textos = input.required<TextosGaleria>();

  protected readonly actual = signal(0);
  /** La foto grande solo se pinta (y se descarga) con la vista abierta. */
  protected readonly abierta = signal(false);
  protected readonly foto = computed(() => this.fotos()[this.actual()]);
  protected readonly contador = computed(() => rellenar(this.textos().contador, { n: this.actual() + 1, total: this.fotos().length }));

  private readonly dialogo = viewChild.required<ElementRef<HTMLDialogElement>>('dialogo');

  protected etiqueta(f: FotoGaleria): string {
    return rellenar(this.textos().abrir, { alt: f.alt });
  }

  abrir(i: number): void {
    this.actual.set(i);
    this.abierta.set(true);
    const d = this.dialogo().nativeElement;
    if (d.open) return;
    // jsdom (pruebas) no implementa showModal()
    if (typeof d.showModal === 'function') d.showModal();
    else d.setAttribute('open', '');
  }

  cerrar(): void {
    const d = this.dialogo().nativeElement;
    if (typeof d.close === 'function') d.close();
    else d.removeAttribute('open');
    this.abierta.set(false);
  }

  /** Pasa de foto; da la vuelta al llegar a un extremo. */
  mover(paso: number): void {
    const total = this.fotos().length;
    this.actual.update((i) => (i + paso + total) % total);
  }

  protected alTeclear(e: KeyboardEvent): void {
    if (e.key === 'ArrowRight') { e.preventDefault(); this.mover(1); }
    if (e.key === 'ArrowLeft') { e.preventDefault(); this.mover(-1); }
  }

  /** Clic en el fondo oscuro (fuera de la hoja) cierra. */
  protected alHacerClic(e: MouseEvent): void {
    if (e.target === this.dialogo().nativeElement) this.cerrar();
  }
}
