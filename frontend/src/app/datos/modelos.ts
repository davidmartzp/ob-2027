/* ============ MODELOS DE CONTENIDO ============ */

/** Texto con una palabra en itálica de acento: «antes <em>enfasis</em> despues». */
export interface TextoConEnfasis {
  antes: string;
  enfasis?: string;
  despues?: string;
}

/** Símbolos del vocabulario del diagrama (ver componente Simbolo y la Leyenda). */
export type TipoSimbolo = 'valle' | 'montana' | 'doblar';

/** Figuras de papel dibujadas (componente Figura): hoy, el estado vacío del buscador de actividades. */
export type TipoFigura = 'grulla' | 'rana' | 'zorro' | 'pez' | 'tigre';


export type RedSocial = 'facebook' | 'whatsapp' | 'instagram';

export interface Enlace {
  texto: string;
  href: string;
}

export interface Red {
  red: RedSocial;
  nombre: string;
  /** Pendiente de URL real mientras sea «#». */
  href: string;
}

export interface Invitado {
  id: string;
  numero: string;
  origen: 'Internacional' | 'Nacional';
  nombre: TextoConEnfasis;
  rol: string;
  bio: string;
  /** Mientras sea provisional se muestra la nota ✳ y el retrato es una escultura de papel. */
  provisional: boolean;
  foto: string;
  fotoAlt: string;
  /** foto = foto real (completa) · papel = retrato provisional de papel (cuadrado) · logo = logo centrado sobre papel */
  retrato: 'foto' | 'papel' | 'logo';
  /** Enlace opcional bajo la bio: Instagram del invitado o correo de la convocatoria. */
  enlace?: { tipo: 'instagram' | 'correo'; texto: string; href: string };
}

/** Filtros de la página de actividades (etiquetas en CATEGORIAS, datos/actividades.ts). */
export type CategoriaActividad = 'convocatorias' | 'talleres' | 'retos' | 'comunidad' | 'bogota';

export interface Actividad {
  /** Slug: ancla de la ficha en la página (actividades#chocogami). */
  id: string;
  titulo: TextoConEnfasis;
  /** Una línea para las filas (home y cabecera de la ficha). */
  resumen: string;
  /** Texto completo de la ficha, un párrafo por elemento. */
  parrafos: string[];
  categoria: CategoriaActividad;
  /** Foto real (assets/…). Sin foto se muestra el logo de Amigos Plegadores. */
  foto?: string;
  /** Aviso ✳ visible: actividad opcional, fechas por confirmar… */
  nota?: string;
  /** Documentos o contacto (externos abren en otra pestaña). */
  enlaces?: Enlace[];
}
