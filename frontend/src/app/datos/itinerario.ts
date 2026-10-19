/* ============ ITINERARIO COMPLETO ============
   Texto del modal «Ver itinerario completo». Un momento por bloque,
   en orden; cada párrafo es un elemento de la lista. */

export interface MomentoItinerario {
  momento: string;
  parrafos: string[];
}

export const ITINERARIO_AUTOR = 'Camilo Torres';

export const ITINERARIO: MomentoItinerario[] = [
  {
    momento: $localize`:@@itinerario.m1.momento:Día —`,
    parrafos: [
      $localize`:@@itinerario.m1.p1:Ya sabemos que está tachando los días que faltan para el evento uno a uno en su calendario, pliegue sistemáticamente los modelos del libro digital, escriba sobre su ansiedad en el grupo de WhatsApp y tenga paciencia, solo podemos hacer un evento al año.`,
    ],
  },
  {
    momento: $localize`:@@itinerario.m2.momento:Unos meses antes`,
    parrafos: [
      $localize`:@@itinerario.m2.p1:Ya están abiertas las inscripciones, ¡de por dios!, inscríbase ya, que le sale más barato y tiene mejor turno para escoger talleres.`,
    ],
  },
  {
    momento: $localize`:@@itinerario.m3.momento:60 días antes`,
    parrafos: [
      $localize`:@@itinerario.m3.p1:Aún estamos en el grupo de WhatsApp del año pasado, algún adelantado empezará a preguntar por los pines, no entre en pánico pero tampoco se relaje que no es mañana pero tampoco hay tanto tiempo, es momento de empezar a pensar en los pines, las tarjetas y su expo personal, bueno está bien entre en pánico supérelo y empiece a doblar.`,
    ],
  },
  {
    momento: $localize`:@@itinerario.m4.momento:1 día antes`,
    parrafos: [
      $localize`:@@itinerario.m4.p1:Aún puede trasnochar y aprovechar la ansiedad si no empezó antes puede llegar al menos con 10 pines, me pido uno, nada como pin con ansiedad y adrenalina, aliste la maleta y prepárese para el fin de semana que tanto ha esperado.`,
    ],
  },
  {
    momento: $localize`:@@itinerario.m5.momento:Día 1`,
    parrafos: [
      $localize`:@@itinerario.m5.p1:Espere la noche antes de que arranque el evento (es importante dormir aunque la ansiedad no lo deje).`,
      $localize`:@@itinerario.m5.p2:En la fecha indicada desplácese hasta el sitio, si lo prefiere un par de horas antes de que todo comience, seguro encontrará a otro Origamista ansioso por el sector, podrán almorzar juntos (en caso extremo desayunar) tomarse un café y esperar a que sean las 3 (ya aprendimos y en 1/2 hora no se monta la expo).`,
      $localize`:@@itinerario.m5.p3:Reclame su kit y si dobló algo para la Expo diríjase a hacer el montaje; Alejo Erazo lo estará esperando, no se desespere y tenga paciencia. Espere con calma mientras revisa el contenido del kit (haga lista mental de con qué va a completar la caja para el día siguiente).`,
      $localize`:@@itinerario.m5.p4:Listo para el canelazo y la inauguración, seguro que para estas alturas ya habrá doblado algo en los pasillos y habrá adelantado cuaderno con los viejos amigos o habrá conocido a su parche del fin de semana.`,
      $localize`:@@itinerario.m5.p5:Prepárese para no dormir esta noche tampoco.`,
      $localize`:@@itinerario.m5.p6:¡Duerma, por favor! … Espere el día 2.`,
    ],
  },
  {
    momento: $localize`:@@itinerario.m6.momento:Día 2`,
    parrafos: [
      $localize`:@@itinerario.m6.p1:Como no pudo dormirse temprano y finalmente pudo conciliar el sueño como a las 4 am, apúrele porque va a llegar tarde.`,
      $localize`:@@itinerario.m6.p2:Mientras se desplaza al evento en transmi, taxi, Uber, bici, teletransportación o corriendo entre a la página y seleccione los talleres, no entre en pánico, si no lo deja entrar tal vez aún no es su turno. ¡Si Ud. es el que conduce, pare para inscribir; no use su celular mientras conduce!`,
      $localize`:@@itinerario.m6.p3:Dedíquese a plegar todo el día, prepárese mentalmente para la foto grupal, si es precavido ya sabrá dónde va a almorzar, si no, fresco: hay grupos que ya tienen estudiada la zona, péguese al que más le llame la atención.`,
      $localize`:@@itinerario.m6.p4:Venciendo el sueño, asista a los talleres de la tarde, prepárese para el chocogami y ¡de por dios! si se inscribió a las tarjetas, entréguelas que lo están buscando desde ayer.`,
      $localize`:@@itinerario.m6.p5:Solo SI ES MAYOR DE EDAD, alístese para la chiva (hoy tampoco va a dormir).`,
    ],
  },
  {
    momento: $localize`:@@itinerario.m7.momento:Día 3`,
    parrafos: [
      $localize`:@@itinerario.m7.p1:Si logró levantarse nos vemos en el evento.`,
      $localize`:@@itinerario.m7.p2:Todo lo demás como el día segundo.`,
      $localize`:@@itinerario.m7.p3:Entrega de reconocimientos, clausura, chocolatada. Ud. sentirá una ligera nostalgia que durará hasta el próximo evento.`,
    ],
  },
  {
    momento: $localize`:@@itinerario.m8.momento:Día 4`,
    parrafos: [
      $localize`:@@itinerario.m8.p1:Es probable que salgan planes, fuera de programa, esté pendiente en el WhatsApp del evento, la emoción nos hace vernos hasta dos semanas seguidas, si le nace en alguno nos veremos.`,
    ],
  },
];
