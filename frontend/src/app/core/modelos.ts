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

/* ---------- datos vivos del backend (core/api.ts) ---------- */

/** Nivel de un taller: Principiante, Intermedio, Avanzado (etiquetas en comun.json → taller.niveles). */
export type NivelTaller = 'P' | 'I' | 'A';
/** Público de un taller: Adultos o Niños (comun.json → taller.publicos). */
export type PublicoTaller = 'A' | 'N';
export type DiaCongreso = 'Saturday' | 'Sunday';

/** Socio comercial de un taller (GET /partners y dentro de cada taller). */
export interface Socio {
  id: number;
  name: string;
  logo: string | null;
  whatsapp: string;
  message: string;
  whatsapp_url: string;
}

/** Un taller listo para pintar en FichaTaller, venga de la galería, del programa o de los talleres del día
 *  (core/talleres.ts lo arma desde cada respuesta del backend). */
export interface Taller {
  id: number;
  nombre: string;
  nivel: NivelTaller;
  publico: PublicoTaller;
  duracion: number;
  observaciones: string | null;
  foto: string | null;
  /** Nombre y primer apellido; nunca datos de contacto. */
  tallerista: string | null;
  socios: Socio[];
  /** Solo en el programa y en los talleres del día. */
  evento?: { id: number; dia: DiaCongreso; inicio: string; fin: string; salon: string | null };
  /** Cupos restantes (talleres del día). */
  cupos?: number;
}
