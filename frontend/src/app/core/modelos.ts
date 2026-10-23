/* ============ MODELOS DE CONTENIDO ============
   La forma de las listas de src/contenido/*.json, ya traducidas (core/idioma.ts).
   Los textos marcados como «HTML» admiten <em>, <b>, <mark>, <br> y se pintan con
   [innerHTML] sobre un elemento con class="rico" (estilos en styles.css). */

/** Símbolos del vocabulario del diagrama (ver componente Simbolo y la Leyenda). */
export type TipoSimbolo = 'valle' | 'montana' | 'doblar';

export type RedSocial = 'facebook' | 'whatsapp' | 'instagram';

export interface Enlace {
  texto: string;
  href: string;
}

/** Patrocinador (contenido/patrocinadores.json → lista). Sin url, se muestra solo el nombre. */
export interface Patrocinador {
  nombre: string;
  url?: string;
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
  origen: 'internacional' | 'nacional';
  /** HTML: el apellido o la palabra de acento va en <em>. */
  nombre: string;
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

/** Filtros de la página de actividades (etiquetas en contenido/actividades.json → categorias). */
export type CategoriaActividad = 'convocatorias' | 'talleres' | 'retos' | 'comunidad' | 'bogota';

export interface Actividad {
  /** Slug: ancla de la ficha en la página (actividades#chocogami). */
  id: string;
  /** HTML: la palabra de acento va en <em>. */
  titulo: string;
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

export interface MomentoItinerario {
  momento: string;
  parrafos: string[];
}

/** Método de pago de la página de inscripciones (contenido/inscripciones.json → metodosPago.lista). */
export interface MetodoPago {
  id: string;
  titulo: string;
  descripcion: string;
  /** avisar: true = pasa antes por el aviso de pago (la tienda en línea). */
  enlace?: { url: string; texto: string; avisar?: boolean };
}
