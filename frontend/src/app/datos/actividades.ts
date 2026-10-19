import { Actividad, CategoriaActividad } from './modelos';

/* ============ ACTIVIDADES ============
   Fuente: originales/actividades_master_es_en.json del cliente (ES + EN; el inglés vive en
   src/locale/en/actividades.json). Al migrar se quitaron emojis y HTML: los enlaces
   van en `enlaces` y las notas «(Actividad opcional…)» en `nota`.
   La lista escala añadiendo objetos: categoría y, si hay, `foto` (assets/…);
   sin foto, la ficha y la vista flotante muestran el logo de Amigos Plegadores. */

export const CATEGORIAS: Record<CategoriaActividad, string> = {
  convocatorias: $localize`:@@actividades.categoria.convocatorias:Convocatorias`,
  talleres: $localize`:@@actividades.categoria.talleres:Talleres y encuentros`,
  retos: $localize`:@@actividades.categoria.retos:Retos y sorpresas`,
  comunidad: $localize`:@@actividades.categoria.comunidad:Comunidad`,
  bogota: $localize`:@@actividades.categoria.bogota:Bogotá y rumba`,
};

/** Respaldo de las actividades sin foto. */
export const LOGO_ACTIVIDAD = 'assets/logo-amigos-plegadores.png';

export const ACTIVIDADES: Actividad[] = [
  {
    id: 'publica-diagramas',
    titulo: { antes: $localize`:@@actividades.publica-diagramas.titulo.antes:Publica tus diagramas: deja tu `, enfasis: $localize`:@@actividades.publica-diagramas.titulo.enfasis:huella` },
    resumen: $localize`:@@actividades.publica-diagramas.resumen:Tus diagramas originales, publicados en el libro oficial del evento.`,
    parrafos: [
      $localize`:@@actividades.publica-diagramas.p1:¿Creaste un modelo de origami increíble? ¡Es tu oportunidad de verlo publicado! Comparte tus diagramas originales en nuestro libro "Páginas De Origami 2026". Llegarás a origamistas de todo el mundo y tu creación será parte del libro oficial del evento.`,
      $localize`:@@actividades.publica-diagramas.p2:¿Cómo participar? Fácil: descarga las Bases de la convocatoria (español o inglés), prepara tus diagramas y envíalos antes del 20 de mayo de 2026 a Camilo Torres: paginasdeorigami@gmail.com. ¡Anímate a compartir tu arte!`,
    ],
    categoria: 'convocatorias',
    nota: $localize`:@@actividades.publica-diagramas.nota:✳ Fechas de la edición 2026 — por confirmar para 2027.`,
    enlaces: [
      { texto: $localize`:@@actividades.publica-diagramas.enlace1:Bases de la convocatoria (español)`, href: 'https://origamibogota.com/descargas/basesdiagramasES.docx' },
      { texto: $localize`:@@actividades.publica-diagramas.enlace2:Bases de la convocatoria (inglés)`, href: 'https://origamibogota.com/descargas/basesdiagramasEN.docx' },
      { texto: $localize`:@@actividades.publica-diagramas.enlace3:Escribir a paginasdeorigami@gmail.com`, href: 'mailto:paginasdeorigami@gmail.com' },
    ],
  },
  {
    id: 'tallerista',
    titulo: { antes: $localize`:@@actividades.tallerista.titulo.antes:Comparte tu talento: ¡sé `, enfasis: $localize`:@@actividades.tallerista.titulo.enfasis:tallerista`, despues: $localize`:@@actividades.tallerista.titulo.despues:!` },
    resumen: $localize`:@@actividades.tallerista.resumen:Enseña tus modelos en las categorías básico, intermedio, avanzado o kids.`,
    parrafos: [
      $localize`:@@actividades.tallerista.p1:¿Te apasiona enseñar origami? ¡Este es tu momento! Inscríbete para ser tallerista en nuestras categorías (Básico, Intermedio, Avanzado, Kids) y comparte tu magia con otros plegadores. Al inscribirte, cuéntanos qué figuras geniales quieres enseñar, o si prefieres, ¡nos mandas los detalles más tarde! Anímate a inspirar a otros con tus pliegues.`,
    ],
    categoria: 'convocatorias',
  },
  {
    id: 'pines',
    titulo: { antes: $localize`:@@actividades.pines.titulo.antes:Rompe el hielo con pines `, enfasis: $localize`:@@actividades.pines.titulo.enfasis:plegados` },
    resumen: $localize`:@@actividades.pines.resumen:Dobla figuritas para llevar de prendedor y repártelas para hacer amigos.`,
    parrafos: [
      $localize`:@@actividades.pines.p1:¿Un poco tímido/a para empezar a charlar? ¡Tenemos la solución! Únete al intercambio de pines: dobla un montón de figuritas de origami chulas que se puedan usar como prendedor. Repártelas a quien quieras en la inauguración o durante todo el evento. ¡Es la excusa perfecta para conectar, hacer amigos y llevarte un recuerdo único!`,
    ],
    categoria: 'comunidad',
  },
  {
    id: 'chocogami',
    titulo: { antes: $localize`:@@actividades.chocogami.titulo.antes:Chocogami: ¡el reto más `, enfasis: $localize`:@@actividades.chocogami.titulo.enfasis:dulce`, despues: $localize`:@@actividades.chocogami.titulo.despues:!` },
    resumen: $localize`:@@actividades.chocogami.resumen:Pliega usando solo el empaque de un chocolate colombiano.`,
    parrafos: [
      $localize`:@@actividades.chocogami.p1:¡Prepárate para un desafío delicioso y creativo! En Chocogami, destapas un chocolate colombiano popular, descubres una foto secreta y... ¡a plegar! La regla de oro: solo puedes usar el empaque del chocolate. Demuestra tu ingenio, compite por premios divertidos o simplemente pásalo genial viendo las creaciones (¡y comiendo chocolate!). ¿Te atreves?`,
    ],
    categoria: 'retos',
    foto: 'assets/act-chocogami.jpg',
  },
  {
    id: 'tarjetas-atc',
    titulo: { antes: $localize`:@@actividades.tarjetas-atc.titulo.antes:Intercambio de `, enfasis: $localize`:@@actividades.tarjetas-atc.titulo.enfasis:tarjetas`, despues: $localize`:@@actividades.tarjetas-atc.titulo.despues: (ATC)` },
    resumen: $localize`:@@actividades.tarjetas-atc.resumen:Arma paquetes de tarjetas de origami e intercámbialos por una colección única.`,
    parrafos: [
      $localize`:@@actividades.tarjetas-atc.p1:¡Haz que tus ATC (Artist Trading Cards) sean inolvidables! Diseña y arma paquetes de tarjetas de origami (16 tarjetas por paquete, en grupos de 2). Al final, se intercambian paquetes de 16 o 32 tarjetas diferentes entre los participantes, ¡y todos se llevan una colección única! Además, participa en el concurso a la mejor tarjeta y deja que tu creatividad brille. Descarga las bases de la convocatoria y empieza a plegar conexiones llenas de arte.`,
    ],
    categoria: 'convocatorias',
    enlaces: [
      { texto: $localize`:@@actividades.tarjetas-atc.enlace1:Bases de la convocatoria ATC`, href: 'https://origamibogota.com/descargas/ATC.docx' },
    ],
  },
  {
    id: 'chiva',
    titulo: { antes: $localize`:@@actividades.chiva.titulo.antes:¡Súbete a la `, enfasis: $localize`:@@actividades.chiva.titulo.enfasis:chiva rumbera`, despues: $localize`:@@actividades.chiva.titulo.despues:!` },
    resumen: $localize`:@@actividades.chiva.resumen:Una noche de baile y rumba colombiana sobre ruedas, solo para adultos.`,
    parrafos: [
      $localize`:@@actividades.chiva.p1:¿Listos para una noche de pura fiesta colombiana? La Chiva Rumbera es nuestro autobús colorido y musical para una escapada nocturna llena de baile y diversión. Es una actividad extra (¡y solo para adultos!) perfecta para soltar el esqueleto después de un día de plegado. ¡No te la pierdas si quieres rumba de la buena!`,
    ],
    categoria: 'bogota',
    nota: $localize`:@@actividades.chiva.nota:✳ Actividad opcional, solo para adultos — consulta los detalles.`,
  },
  {
    id: 'encuentro-invitados',
    titulo: { antes: $localize`:@@actividades.encuentro-invitados.titulo.antes:Encuentro con los `, enfasis: $localize`:@@actividades.encuentro-invitados.titulo.enfasis:invitados` },
    resumen: $localize`:@@actividades.encuentro-invitados.resumen:Clases especiales para plegar un modelo directamente con los invitados.`,
    parrafos: [
      $localize`:@@actividades.encuentro-invitados.p1:¡Una oportunidad de oro! Tendremos clases especiales con nuestros invitados estrella (internacionales y nacionales). Imagina aprender sus secretos, escuchar sus historias y plegar un modelo especial directamente con ellos en un grupo grande. ¡Prepárate para una dosis concentrada de inspiración y conocimiento de los que más saben!`,
    ],
    categoria: 'talleres',
  },
  {
    id: 'talleres',
    titulo: { antes: $localize`:@@actividades.talleres.titulo.antes:Talleres para todos: ¡encuentra tu `, enfasis: $localize`:@@actividades.talleres.titulo.enfasis:pliegue`, despues: $localize`:@@actividades.talleres.titulo.despues:!` },
    resumen: $localize`:@@actividades.talleres.resumen:Talleres por niveles, de principiante a experto, con modelos de autor.`,
    parrafos: [
      $localize`:@@actividades.talleres.p1:¿Eres nuevo/a en el mundo del origami o ya eres súper pro plegando? ¡No importa! Nuestros talleres se dividen por niveles (Principiante, Intermedio, Experto) para que disfrutes aprendiendo figuras a tu ritmo. Lo mejor: muchos profes enseñan modelos creados por ellos mismos. ¡Son oportunidades únicas para aprender figuras que no encontrarás en otro lugar!`,
    ],
    categoria: 'talleres',
  },
  {
    id: 'exposicion',
    titulo: { antes: $localize`:@@actividades.exposicion.titulo.antes:Exposición: ¡muestra tu `, enfasis: $localize`:@@actividades.exposicion.titulo.enfasis:arte`, despues: $localize`:@@actividades.exposicion.titulo.despues: al mundo!` },
    resumen: $localize`:@@actividades.exposicion.resumen:Expón tus mejores creaciones y vota por la mejor exposición.`,
    parrafos: [
      $localize`:@@actividades.exposicion.p1:¡Este es tu escenario! Trae tus mejores creaciones y exponlas para que todos las admiren. Recuerda ponerle una etiqueta con los datos clave (autor, plegador, tipo de papel...). Además, ¡tus modelos pueden ganar premios! Habrá votaciones durante todo el evento para elegir la mejor exposición (¡la creatividad cuenta!). No olvides votar por tus favoritas y dejar que tu trabajo inspire a otros.`,
    ],
    categoria: 'comunidad',
    foto: 'assets/act-expo.jpg',
  },
  {
    id: 'origami-kids',
    titulo: { antes: $localize`:@@actividades.origami-kids.titulo.antes:Origami Kids: ¡diversión para los `, enfasis: $localize`:@@actividades.origami-kids.titulo.enfasis:peques`, despues: $localize`:@@actividades.origami-kids.titulo.despues:!` },
    resumen: $localize`:@@actividades.origami-kids.resumen:Un rincón con talleres, juegos y figuras para niños y niñas.`,
    parrafos: [
      $localize`:@@actividades.origami-kids.p1:¡El futuro del origami está aquí! Tenemos un rincón especial para los niños y niñas, con talleres súper divertidos, juegos y figuras pensadas para sus manitas creativas. Y si te encanta enseñar y tienes un don con los peques, ¡anímate a ser profe voluntario en Origami Kids! Es una experiencia gratificante llena de risas y papelitos de colores.`,
    ],
    categoria: 'talleres',
  },
  {
    id: 'subasta',
    titulo: { antes: $localize`:@@actividades.subasta.titulo.antes:Subasta silenciosa: ¡tesoros `, enfasis: $localize`:@@actividades.subasta.titulo.enfasis:escondidos`, despues: $localize`:@@actividades.subasta.titulo.despues:!` },
    resumen: $localize`:@@actividades.subasta.resumen:Libros, papeles y modelos donados por artistas, al mejor postor secreto.`,
    parrafos: [
      $localize`:@@actividades.subasta.p1:¿Buscas esa joya de origami que no encuentras en ningún lado? ¡Participa en nuestra Subasta Silenciosa! Podrás encontrar libros, papeles especiales, modelos exclusivos donados por artistas y muchas sorpresas más. Escribe tu oferta en secreto, cruza los dedos y ¡llévate a casa algo único! Además, ayudas a apoyar a la comunidad de origami.`,
    ],
    categoria: 'retos',
  },
  {
    id: 'origami-pasillo',
    titulo: { antes: $localize`:@@actividades.origami-pasillo.titulo.antes:Origami de pasillo: ¡la magia `, enfasis: $localize`:@@actividades.origami-pasillo.titulo.enfasis:espontánea`, despues: $localize`:@@actividades.origami-pasillo.titulo.despues:!` },
    resumen: $localize`:@@actividades.origami-pasillo.resumen:Trucos rápidos y figuras nuevas en cualquier rincón del evento.`,
    parrafos: [
      $localize`:@@actividades.origami-pasillo.p1:A veces, los mejores descubrimientos ocurren fuera de las aulas. El "Origami de Pasillo" es ese momento mágico donde compartes un truco rápido, aprendes una figura de un nuevo amigo o simplemente charlas sobre papeles y pliegues en cualquier rincón del evento. ¡Mantén los ojos abiertos y el papel listo, la conexión y el aprendizaje suceden en todas partes!`,
    ],
    categoria: 'comunidad',
  },
  {
    id: 'explora-bogota',
    titulo: { antes: $localize`:@@actividades.explora-bogota.titulo.antes:Explora Bogotá: ¡más allá del `, enfasis: $localize`:@@actividades.explora-bogota.titulo.enfasis:papel`, despues: $localize`:@@actividades.explora-bogota.titulo.despues:!` },
    resumen: $localize`:@@actividades.explora-bogota.resumen:Tips y paseos opcionales para conocer la ciudad entre pliegue y pliegue.`,
    parrafos: [
      $localize`:@@actividades.explora-bogota.p1:¿Quieres aprovechar el viaje para conocer la capital? ¡Claro que sí! Te daremos tips y quizás organicemos algunos paseos opcionales para descubrir los encantos de Bogotá. Explora la cultura, prueba la comida local y llévate recuerdos que van más allá del origami. ¡Una oportunidad para estirar las piernas y ver la ciudad entre pliegue y pliegue!`,
    ],
    categoria: 'bogota',
    foto: 'assets/act-explora.jpg',
    nota: $localize`:@@actividades.explora-bogota.nota:✳ Actividad opcional — consulta la programación para más detalles.`,
  },
  {
    id: 'rifas',
    titulo: { antes: $localize`:@@actividades.rifas.titulo.antes:¡Rifas y sorteos `, enfasis: $localize`:@@actividades.rifas.titulo.enfasis:sorpresa`, despues: $localize`:@@actividades.rifas.titulo.despues:!` },
    resumen: $localize`:@@actividades.rifas.resumen:Libros, papeles y herramientas para quien esté atento a los anuncios.`,
    parrafos: [
      $localize`:@@actividades.rifas.p1:¡Mantente alerta y atento/a durante todo el evento! Organizaremos rifas y sorteos sorpresa en diferentes momentos. Podrías ganar libros de origami, papeles especiales, herramientas únicas y mucho más. La clave para no perderte nada: ¡estate pendiente a los anuncios y actividades! Participa en talleres, asiste a charlas y mantén los oídos bien abiertos. ¡La suerte sonríe a quienes están presentes y activos! No te quedes con las ganas de llevarte a casa un premio genial.`,
    ],
    categoria: 'retos',
    foto: 'assets/act-rifas.jpg',
  },
];

/** Las que se destacan en la home (en este orden). */
export const DESTACADAS: string[] = ['publica-diagramas', 'pines', 'talleres'];
