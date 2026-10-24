import recursos from '@contenido/recursos.json';
import { montarPagina } from '../../../pruebas';

describe('página de recursos', () => {
  it('lista cada documento con su enlace y el botón según el tipo', async () => {
    const el = await montarPagina('/recursos');
    const filas = [...el.querySelectorAll<HTMLLIElement>('.piezas > li')];
    expect(filas.map((li) => li.id)).toEqual(recursos.documentos.map((d) => d.id));
    for (const [i, d] of recursos.documentos.entries()) {
      const a = filas[i].querySelector<HTMLAnchorElement>('a.btn')!;
      expect(a.getAttribute('href')).toBe(d.url);
      expect(a.getAttribute('target')).toBe('_blank');
      expect(a.getAttribute('rel')).toBe('noopener');
      expect(a.textContent).toContain(d.tipo === 'gdoc' ? 'Abrir' : 'Descargar');
      expect(!!filas[i].querySelector('.pend-nota'), d.id).toBe(!!d.porConfirmar);
    }
  });

  it('embebe el cronograma con el visor y deja visible el enlace alternativo', async () => {
    const el = await montarPagina('/recursos');
    const iframe = el.querySelector<HTMLIFrameElement>('.visor iframe')!;
    expect(iframe.getAttribute('src')).toBe(recursos.cronograma.visorUrl);
    expect(iframe.getAttribute('loading')).toBe('lazy');
    expect(el.querySelector('.alternativa a')?.getAttribute('href')).toBe(recursos.cronograma.url);
  });

  it('en inglés, con sus textos', async () => {
    const el = await montarPagina('/en/recursos');
    expect(el.querySelector('.piezas h3')?.textContent).toContain('Participant guide');
    expect(el.querySelector('.alternativa a')?.textContent).toContain('Open schedule');
  });

  it('el menú y el itinerario de la home llevan a /recursos', async () => {
    const el = await montarPagina('/');
    expect(el.querySelector('.itinerario a.recursos-link')?.getAttribute('href')).toBe('recursos');
    expect([...el.querySelectorAll('app-menu a')].some((a) => a.getAttribute('href') === 'recursos')).toBe(true);
  });

  it('en inglés, el enlace del itinerario se queda en inglés', async () => {
    const el = await montarPagina('/en/');
    expect(el.querySelector('.itinerario a.recursos-link')?.getAttribute('href')).toBe('en/recursos');
  });
});
