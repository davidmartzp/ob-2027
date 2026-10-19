// ============ VISTA PREVIA DEL BUILD DE PRODUCCIÓN ============
// Uso: npm run preview  (compila con `ng build` y sirve dist/frontend/browser)

import { servir } from './servidor.mjs';

servir({
  raiz: new URL('../dist/frontend/browser/', import.meta.url).pathname,
  puerto: Number(process.env.PORT ?? 4200),
});
