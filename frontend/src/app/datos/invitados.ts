import { Invitado } from './modelos';

/* ============ INVITADOS ============
   Numeración INV. 0X correlativa.
   Mientras estén «por confirmar», provisional: true y retrato de papel plegado. */

export const INVITADOS: Invitado[] = [
  {
    id: 'anice-claudeon',
    numero: 'INV. 01',
    origen: 'Internacional',
    nombre: { antes: 'Anicé ', enfasis: 'Claudeón' },
    rol: $localize`:@@invitados.anice.rol:Figuras complejas de una sola hoja · Amiens, Francia`,
    bio: $localize`:@@invitados.anice.bio:Nació en 1999 en Amiens, Francia. Descubrió el origami en 2010 y muy pronto se apasionó por crear sus propios diseños. Al principio se inspiró en el mundo animal: sus primeros modelos se destacaban por lo tiernos y expresivos. Con el tiempo, su trabajo evolucionó hacia figuras híbridas, influido por sus estudios de arte y diseño. Se especializa en plegar sujetos complejos a partir de una sola hoja y explora la «media ilusión» del movimiento, la textura y la ligereza del material. Su obra tiene reconocimiento internacional y lo ha llevado a exponer y enseñar en todo el mundo.`,
    provisional: false,
    foto: 'assets/anice-claudeon.jpg',
    fotoAlt: $localize`:@@invitados.anice.fotoAlt:Anicé Claudeón sonríe mientras pliega una figura de papel`,
    retrato: 'foto',
    enlace: { tipo: 'instagram', texto: '@anice_cldn', href: 'https://www.instagram.com/anice_cldn/' },
  },
  {
    id: 'convocatoria-nacional',
    numero: 'INV. 02',
    origen: 'Nacional',
    nombre: { antes: $localize`:@@invitados.convocatoria.nombre.antes:Convocatoria `, enfasis: $localize`:@@invitados.convocatoria.nombre.enfasis:abierta` },
    rol: $localize`:@@invitados.convocatoria.rol:Invitado nacional por convocatoria`,
    bio: $localize`:@@invitados.convocatoria.bio:Este puesto es para un plegador o una plegadora de Colombia. Si tiene un modelo propio, una técnica que domina o un taller que quiera compartir, postúlese: escríbanos contándonos quién es y qué le gustaría enseñar.`,
    provisional: false,
    foto: 'assets/logo-amigos-plegadores.png',
    fotoAlt: $localize`:@@comun.logoAlt:Logo de Amigos Plegadores`,
    retrato: 'logo',
    enlace: { tipo: 'correo', texto: 'inscripciones@origamibogota.com', href: 'mailto:inscripciones@origamibogota.com' },
  },
];
