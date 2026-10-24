import inscripciones from '@contenido/inscripciones.json';
import { api, apiUrl } from './api';
import { Traducido } from './idioma';

/* ============ INSCRIPCIÓN: DATOS, VALIDACIÓN Y ENVÍO ============
   Lógica del formulario de registro, sin vista (la usa FormularioInscripcion).
   Es la del sitio anterior (rama master de congreso-origami): mismos campos, mismo
   orden de validación y mismos mensajes, con estas correcciones:
     · «Grupo de apoyo» no exige comprobante (estaba escrito «Gupo de apoyo»);
     · acompañantes de la chiva: de 2 a 3 en el campo y en el texto (decían 9);
     · el correo admite subdominios (nombre@unal.edu.co era rechazado);
     · un solo listado de países para mostrar y para enviar (había indicativos cruzados).
   Sin backend: `enviarInscripcion` simula la respuesta mientras evento.json tenga
   "apiUrl": null. El contrato del servicio está en `EnvioInscripcion`. */

type TextosFormulario = Traducido<typeof inscripciones.formulario>;
export type CampoInscripcion = keyof DatosInscripcion;

/** Estado del formulario (lo que escribe la persona). */
export interface DatosInscripcion {
  name: string;
  lastname: string;
  age: string;
  idname: string;
  /** Indicativo del país, p. ej. '57'. */
  country: string;
  city: string;
  email: string;
  phone: string;
  paymethod: string;
  paydate: string;
  receipt: string;
  /** T = todos los días · S = sábado · D = domingo. */
  days: '' | 'T' | 'S' | 'D';
  companion: string;
  info: string;
  otherInfo: string;
  printCertificate: boolean;
  group: boolean;
  origamigroup: string;
  cards: boolean;
  cardGroup: string;
  workshops: boolean;
  go_to_chiva: boolean;
  /** null = aún no ha respondido si irá con acompañantes. */
  chiva_accompanied: boolean | null;
  chiva_companions: string;
}

/** Lo que se envía al servicio: POST {apiUrl}/attendees con este JSON (igual que el sitio anterior). */
export interface EnvioInscripcion {
  name: string;
  lastname: string;
  age: number;
  /** «Colombia (+57)». */
  country: string;
  city: string;
  email: string;
  phone: string;
  companion: string;
  idname: string;
  paymethod: string;
  /** AAAA-MM-DD; si no se indicó, la fecha de hoy. */
  paydate: string;
  receipt: string;
  group: 0 | 1;
  origamigroup: string;
  medium: string;
  othermedium: string;
  info: string;
  cards: 0 | 1;
  cardgroup: string;
  days: string;
  printCertificate: 0 | 1;
  go_to_chiva: 0 | 1;
  chiva_companions: number;
}

export interface ErrorCampo {
  campo: CampoInscripcion;
  mensaje: string;
}

export const TOTAL_PASOS = 5;

export function datosVacios(): DatosInscripcion {
  return {
    name: '', lastname: '', age: '', idname: '',
    country: '57', city: '', email: '', phone: '',
    paymethod: '', paydate: '', receipt: '',
    days: '', companion: '',
    info: '', otherInfo: '', printCertificate: false,
    group: false, origamigroup: '', cards: false, cardGroup: '',
    workshops: false, go_to_chiva: false, chiva_accompanied: null, chiva_companions: '',
  };
}

/** ¿Este tipo de pago exige fecha y comprobante? (becas, patrocinios, grupo de apoyo e invitados, no). */
export function requiereComprobante(paymethod: string): boolean {
  return !inscripciones.formulario.tiposPago.some((t) => t.valor === paymethod && 'sinComprobante' in t && t.sinComprobante);
}

const CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const SOLO_DIGITOS = /^\d+$/;

/** Errores de un paso (1–5), en el orden del formulario original. Vacío = el paso es válido. */
export function validarPaso(paso: number, d: DatosInscripcion, t: TextosFormulario): ErrorCampo[] {
  const v = t.validaciones;
  const errores: ErrorCampo[] = [];
  const falta = (campo: CampoInscripcion, mensaje: string, condicion: boolean): void => {
    if (condicion) errores.push({ campo, mensaje });
  };
  switch (paso) {
    case 1:
      falta('name', v.name, !d.name.trim());
      falta('lastname', v.lastname, !d.lastname.trim());
      falta('age', v.age, !d.age);
      falta('idname', v.idname, !d.idname.trim());
      break;
    case 2:
      falta('country', v.country, !d.country);
      falta('city', v.city, !d.city.trim());
      falta('email', v.email, !d.email.trim());
      falta('email', v.emailInvalid, !!d.email.trim() && !CORREO.test(d.email.trim()));
      falta('phone', v.phone, !d.phone);
      falta('phone', v.phoneNumeric, !!d.phone && !SOLO_DIGITOS.test(d.phone));
      break;
    case 3:
      falta('paymethod', v.payMethod, !d.paymethod);
      if (d.paymethod && requiereComprobante(d.paymethod)) {
        falta('paydate', v.payDate, !d.paydate);
        falta('receipt', v.receipt, !d.receipt.trim());
      }
      break;
    case 4:
      falta('days', v.days, !d.days);
      break;
    case 5: {
      falta('info', v.howHeard, !d.info);
      falta('otherInfo', v.howHeardSpecify, d.info === 'Otro' && !d.otherInfo.trim());
      falta('origamigroup', v.origamiGroup, d.group && !d.origamigroup.trim());
      if (d.cards) {
        const grupos = Number(d.cardGroup);
        falta('cardGroup', v.cardGroup, !d.cardGroup);
        falta('cardGroup', v.cardGroupRange, !!d.cardGroup && !(grupos >= 1 && grupos <= 2));
      }
      if (d.go_to_chiva) {
        falta('chiva_accompanied', v.chivaAccompanied, d.chiva_accompanied === null);
        if (d.chiva_accompanied) {
          const n = Number(d.chiva_companions);
          falta('chiva_companions', v.chivaCompanions, !d.chiva_companions);
          falta('chiva_companions', v.chivaMin, !!d.chiva_companions && !(n >= 2));
          falta('chiva_companions', v.chivaMax, n > 3);
        }
      }
      break;
    }
  }
  // un mensaje por campo: el primero, como hacía el original al detenerse en el primer fallo
  return errores.filter((e, i) => errores.findIndex((o) => o.campo === e.campo) === i);
}

/** «maría josé» → «María José» (como hacía el original con nombre, apellido, ciudad y escarapela). */
export function capitalizar(texto: string): string {
  return texto.trim().split(/\s+/).map((p) => p.charAt(0).toUpperCase() + p.slice(1).toLowerCase()).join(' ');
}

/** El JSON que espera el servicio, a partir de lo que escribió la persona. */
export function construirEnvio(d: DatosInscripcion, hoy = new Date()): EnvioInscripcion {
  const pais = inscripciones.paises.find((p) => p.indicativo === d.country);
  return {
    name: capitalizar(d.name),
    lastname: capitalizar(d.lastname),
    age: parseInt(d.age, 10),
    country: pais ? `${pais.nombre} (+${pais.indicativo})` : 'Colombia',
    city: capitalizar(d.city),
    email: d.email.trim(),
    phone: d.phone,
    companion: d.companion.trim(),
    idname: capitalizar(d.idname),
    paymethod: d.paymethod,
    paydate: d.paydate || hoy.toISOString().split('T')[0],
    receipt: d.receipt.trim(),
    group: d.group ? 1 : 0,
    origamigroup: d.group ? d.origamigroup.trim() : '',
    medium: d.info,
    othermedium: d.info === 'Otro' ? d.otherInfo.trim() : '',
    info: d.info,
    cards: d.cards ? 1 : 0,
    cardgroup: d.cards ? d.cardGroup : '',
    days: d.days,
    printCertificate: d.printCertificate ? 1 : 0,
    go_to_chiva: d.go_to_chiva ? 1 : 0,
    chiva_companions: d.go_to_chiva && d.chiva_accompanied && d.chiva_companions ? parseInt(d.chiva_companions, 10) : 0,
  };
}

export interface RespuestaEnvio {
  /** true = no hubo servidor: la respuesta es simulada. */
  simulado: boolean;
  /** Número de participante asignado al inscribirse (null si aún no le corresponde: menores, staff…). */
  code: number | null;
}

/** Envía la inscripción a POST {apiUrl}/attendees. Sin `apiUrl` (evento.json), la simula (y la deja en la consola).
 *  Los errores del servicio llegan como ErrorApi / ErrorRed (core/api.ts). */
export async function enviarInscripcion(envio: EnvioInscripcion): Promise<RespuestaEnvio> {
  if (!apiUrl()) {
    console.info('[inscripción sin conexión] Se enviaría a POST /attendees:', envio);
    await new Promise((listo) => setTimeout(listo, 600));
    return { simulado: true, code: null };
  }
  const respuesta = await api<{ id: number; status: number; code: number | null }>('POST', 'attendees', envio);
  return { simulado: false, code: respuesta.code };
}
