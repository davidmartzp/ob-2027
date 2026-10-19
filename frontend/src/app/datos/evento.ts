import { Enlace, Red, TextoConEnfasis } from './modelos';

/* ============ DATOS DEL EVENTO ============ */

export const EVENTO = {
  sede: 'Liceo Francés Louis Pasteur',
  ciudad: 'Bogotá, Colombia',
  direccion: 'Cl. 87 #7-77, Barrio La Cabrera',
  mapaUrl: 'https://maps.google.com/?q=Liceo+Franc%C3%A9s+Louis+Pasteur+Bogot%C3%A1',
  fecha: $localize`:@@evento.fecha:04 al 06 de junio`,
  fechaLarga: $localize`:@@evento.fechaLarga:4 al 6 de junio de 2027 — Bogotá`,
  telefono: { texto: '+57 315 245 9839', href: 'tel:+573152459839' },
  correos: ['inscripciones@origamibogota.com'],
  correoInscripciones: 'mailto:inscripciones@origamibogota.com',
};

/** Interruptor de inscripciones: con false se ocultan (sin borrarse) los botones y
 *  enlaces «Inscríbete» del menú, el menú móvil, el hero y el footer. */
export const INSCRIPCIONES_ABIERTAS = false;

/** Interruptor del menú superior: con false se ocultan (sin borrarse) los enlaces del menú
 *  y, en móvil, el botón de hamburguesa con su panel. El logo se mantiene. */
export const MOSTRAR_MENU = true;

/** Secciones visibles: con false la sección (y su doblez) se oculta sin borrarse,
 *  y los enlaces que apuntan a ella (#id) dejan de mostrarse. */
export const SECCIONES_VISIBLES = {
  comunidad: true,
  itinerario: true,
  actividades: true,
  tallerista: true,
  patrocinadores: true,
};

/** ¿Apunta este enlace a una sección oculta? Entiende anclas (#itinerario) y
 *  páginas (actividades, actividades#chocogami): la página comparte nombre con su sección. */
export function enlaceVisible(href: string): boolean {
  const id = href.startsWith('#') ? href.slice(1) : href.split('#')[0];
  return !(id in SECCIONES_VISIBLES) || SECCIONES_VISIBLES[id as keyof typeof SECCIONES_VISIBLES];
}

/** Redes sociales: href «#» = pendiente de URL real. Las URL externas abren en otra pestaña. */
export const REDES: Red[] = [
  { red: 'facebook', nombre: 'Facebook', href: 'https://www.facebook.com/origamibogota' },
  { red: 'whatsapp', nombre: 'WhatsApp', href: 'https://api.whatsapp.com/send?phone=573152459839&text=Hola,%20quiero%20solicitar%20m%C3%A1s%20informaci%C3%B3n%20sobre%20el%20evento' },
  { red: 'instagram', nombre: 'Instagram', href: 'https://www.instagram.com/congresoorigamibogota/' },
];

/* ============ NAVEGACIÓN ============
   «#seccion» = ancla de la home (con <base href> funciona igual desde cualquier página);
   «actividades» / «actividades#id» = página propia (relativa al <base href>, nunca con «/» inicial). */

/** Cada destino del menú, una sola vez; las listas de abajo los combinan. */
export const ENLACES = {
  inicio: { texto: $localize`:@@nav.inicio:Inicio`, href: '#inicio' },
  evento: { texto: $localize`:@@nav.evento:El evento`, href: '#acerca' },
  comunidad: { texto: $localize`:@@nav.comunidad:La comunidad`, href: '#comunidad' },
  itinerario: { texto: $localize`:@@nav.itinerario:Itinerario`, href: '#itinerario' },
  recursos: { texto: $localize`:@@nav.recursos:Recursos y cronograma`, href: '#itinerario' },
  invitados: { texto: $localize`:@@nav.invitados:Invitados`, href: '#invitados' },
  actividades: { texto: $localize`:@@nav.actividades:Actividades`, href: 'actividades' },
  talleres: { texto: $localize`:@@nav.talleres:Talleres`, href: 'actividades#talleres' },
  exposicion: { texto: $localize`:@@nav.exposicion:Exposición`, href: 'actividades#exposicion' },
  patrocinadores: { texto: $localize`:@@nav.patrocinadores:Patrocinadores`, href: '#patrocinadores' },
  contacto: { texto: $localize`:@@nav.contacto:Contacto e inscripciones`, href: '#inscribete' },
  tallerista: { texto: $localize`:@@nav.tallerista:Sé tallerista`, href: '#tallerista' },
} satisfies Record<string, Enlace>;

const { inicio, evento, comunidad, itinerario, recursos, invitados, actividades, talleres, exposicion, patrocinadores, contacto, tallerista } = ENLACES;

export const NAV_PRINCIPAL: Enlace[] = [evento, comunidad];

/** Desplegable «El congreso»: toda entrada nueva del menú va aquí y en NAV_MOVIL. */
export const NAV_CONGRESO: Enlace[] = [itinerario, recursos, invitados, actividades, talleres, exposicion, patrocinadores, contacto];

export const NAV_MOVIL: Enlace[] = [evento, comunidad, itinerario, invitados, actividades, patrocinadores, tallerista];

export const NAV_PIE: Enlace[] = [inicio, itinerario, invitados, talleres];

/* ============ MARQUEE ============ */

export const FRASES_MARQUEE: TextoConEnfasis[] = [
  { antes: $localize`:@@marquee.juntos.antes:Plegamos `, enfasis: $localize`:@@marquee.juntos.enfasis:juntos` },
  { antes: $localize`:@@marquee.anos:16 años de comunidad` },
  { antes: $localize`:@@marquee.fecha:4 al 6 de junio · Bogotá · 2.600 msnm` },
  { antes: $localize`:@@marquee.tinto.antes:Ruana, papel y `, enfasis: $localize`:@@marquee.tinto.enfasis:tinto` },
  { antes: $localize`:@@marquee.finde:El finde más plegado del año` },
];

/* ============ PATROCINADORES ============ */

export const PATROCINADORES: string[] = [
  'Liceo Francés Louis Pasteur',
  'Pliegues Origami',
  'evoforma PLUS',
  'nessan',
  'ImpresionArte',
  'Papelería San Sebastián',
  'duopapel',
  'Origamística',
  $localize`:@@patrocinadores.embajada:Embajada del Japón en Colombia`,
];
