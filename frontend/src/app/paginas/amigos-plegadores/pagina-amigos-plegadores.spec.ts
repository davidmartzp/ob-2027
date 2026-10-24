import amigos from '@contenido/amigos-plegadores.json';
import { esperar, montarPagina } from '../../../pruebas';

describe('página Amigos Plegadores', () => {
  it('cuatro pasos numerados I–IV con su texto en español', async () => {
    const el = await montarPagina('/amigos-plegadores');
    const pasos = [...el.querySelectorAll<HTMLElement>('section.paso')];
    expect(pasos.map((s) => s.id)).toEqual(['origen', 'reuniones', 'evento', 'chiva']);
    expect(pasos.map((s) => s.querySelector('.numeral')?.textContent?.trim())).toEqual(['Paso I', 'Paso II', 'Paso III', 'Paso IV']);
    expect(el.querySelector('#origen .prosa')?.textContent).toContain('parche de amigos');
  });

  it('en inglés, con su traducción', async () => {
    const el = await montarPagina('/en/amigos-plegadores');
    expect(el.querySelector('#origen h2')?.textContent).toContain('The origin');
    expect(el.querySelector('#chiva .prosa')?.textContent).toContain('party on wheels');
  });

  it('AseisManos enlaza a su sitio en otra pestaña', async () => {
    const el = await montarPagina('/amigos-plegadores');
    const a = el.querySelector<HTMLAnchorElement>('#reuniones .prosa a')!;
    expect(a.textContent).toBe('AseisManos');
    expect(a.getAttribute('href')).toBe('http://aseismanos.com.co/');
    expect(a.getAttribute('target')).toBe('_blank');
    expect(a.getAttribute('rel')).toBe('noopener');
  });

  it('galería: miniaturas perezosas, flechas del teclado y Escape', async () => {
    const el = await montarPagina('/amigos-plegadores');
    const miniaturas = [...el.querySelectorAll<HTMLImageElement>('.cuadricula img')];
    expect(miniaturas.length).toBe(amigos.parche.fotos.length);
    for (const img of miniaturas) {
      expect(img.getAttribute('loading')).toBe('lazy');
      expect(img.getAttribute('src')).toMatch(/-mini\.webp$/);
      expect(img.getAttribute('alt')?.length).toBeGreaterThan(10);
    }
    const dialogo = el.querySelector<HTMLDialogElement>('app-galeria-fotos dialog')!;
    expect(dialogo.querySelector('figure img')).toBeNull(); // la grande no se pide hasta abrir

    el.querySelectorAll<HTMLButtonElement>('.cuadricula button')[0].click();
    await esperar();
    expect(dialogo.hasAttribute('open')).toBe(true);
    expect(dialogo.querySelector('figure img')?.getAttribute('src')).toBe('assets/ap/mechas.webp');

    dialogo.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));
    await esperar();
    expect(dialogo.querySelector('figure img')?.getAttribute('src')).toBe('assets/ap/amigos2.webp');
    dialogo.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true }));
    dialogo.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true }));
    await esperar();
    expect(dialogo.querySelector('figure img')?.getAttribute('src')).toBe('assets/ap/cetsu.webp'); // da la vuelta
    expect(dialogo.querySelector('figcaption')?.textContent).toContain(`Foto ${amigos.parche.fotos.length} de`);

    // Escape: el navegador cierra el <dialog> y dispara «close» (jsdom no lo hace solo)
    dialogo.removeAttribute('open');
    dialogo.dispatchEvent(new Event('close'));
    await esperar();
    expect(dialogo.querySelector('figure img')).toBeNull();
  });

  it('el menú del congreso y el pie enlazan a la página', async () => {
    const el = await montarPagina('/');
    expect(el.querySelector('app-pie a[href="amigos-plegadores"]')).toBeTruthy();
    expect(el.querySelector('app-cabecera a[href="amigos-plegadores"]')).toBeTruthy();
  });
});
