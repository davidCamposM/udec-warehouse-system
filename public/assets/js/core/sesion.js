/* Sesión: la ventana «¿Sigues ahí?» (parcial «sesion»).
   - Uso = tocar, hacer clic, escribir o desplazar la página. Mientras hay uso, se avisa al servidor como mucho cada
     2 minutos (POST sesion/seguir), así la sesión no vence mientras alguien trabaja sin cambiar de página.
   - Tras data-aviso segundos sin uso aparece la ventana, con la cuenta regresiva de data-respuesta segundos.
     «Sí, sigo aquí» avisa al servidor y cierra la ventana; «No», o que la cuenta llegue a 0, cierra la sesión.
   - Las pestañas comparten el último uso (localStorage): una pestaña quieta no cierra la sesión si en otra se trabaja.
   - Si el computador estuvo suspendido más que todo ese tiempo, al volver la sesión se cierra de inmediato.
   - Cada 10 segundos (y al volver a la pestaña) pregunta de fondo si la sesión sigue abierta: si la contraseña cambió,
     la pestaña se va al login con el aviso «Contraseña cambiada. Ingrese nuevamente.». */
import { ApiError, goToLogin, request } from "./api.js";

const HTTP_FORBIDDEN = 403;

(() => {
  "use strict";

  const ventana = document.querySelector("[data-sesion]");
  if (!ventana || typeof ventana.showModal !== "function") return;

  const AVISO = Number(ventana.dataset.aviso) * 1000;
  const RESPUESTA = Number(ventana.dataset.respuesta) * 1000;
  const CADA_AVISO_AL_SERVIDOR = 2 * 60 * 1000;
  const REVISAR_SESION = 10 * 1000;
  const CLAVE = "bodega-ultimo-uso";

  const formulario = ventana.querySelector("[data-sesion-salir]");
  const motivo = ventana.querySelector("[data-sesion-motivo]");
  const segundos = ventana.querySelector("[data-sesion-segundos]");
  const sigo = ventana.querySelector("[data-sesion-sigo]");

  let ultimoUso = Date.now();
  let ultimoAvisoAlServidor = Date.now(); // cargar la página ya renovó la sesión
  let cerrando = false;

  const leerCompartido = () => {
    try {
      return Number(localStorage.getItem(CLAVE)) || 0;
    } catch {
      return 0;
    }
  };

  const guardarCompartido = (momento) => {
    try {
      localStorage.setItem(CLAVE, String(momento));
    } catch {
      /* Sin almacenamiento (ventana privada, bloqueado): cada pestaña cuenta por su cuenta. */
    }
  };

  const avisarAlServidor = async () => {
    ultimoAvisoAlServidor = Date.now();
    try {
      await request(ventana.dataset.seguirUrl, { method: "POST" });
    } catch (error) {
      // La sesión ya se había cerrado (por ejemplo, otra pestaña dijo «No») o el formulario venció: al login, donde se
      // ve el aviso. Sin conexión (no es ApiError): se intenta de nuevo con el próximo uso.
      if (error instanceof ApiError && (error.isUnauthenticated || error.status === HTTP_FORBIDDEN)) goToLogin(error);
    }
  };

  // Avisa a la página que la sesión se está cerrando (panel.js deja de pedir el resumen: si no, su respuesta «sin
  // sesión» llevaría al login otra vez y se perdería el aviso «Tu sesión se cerró por inactividad»).
  const avisarCierre = () => document.dispatchEvent(new CustomEvent("bodega:cerrando-sesion"));

  const cerrarSesion = (porInactividad) => {
    if (cerrando) return;
    cerrando = true;
    avisarCierre();
    motivo.value = porInactividad ? "inactividad" : "";
    formulario.submit();
  };

  const huboUso = () => {
    if (cerrando || ventana.open) return;
    ultimoUso = Date.now();
    guardarCompartido(ultimoUso);
    if (ultimoUso - ultimoAvisoAlServidor >= CADA_AVISO_AL_SERVIDOR) avisarAlServidor();
  };

  const revisar = () => {
    if (cerrando) return;
    ultimoUso = Math.max(ultimoUso, leerCompartido());
    const sinUso = Date.now() - ultimoUso;

    // En otra pestaña se siguió trabajando (o se respondió «Sí»): la ventana ya no hace falta.
    if (sinUso < AVISO) {
      if (ventana.open) ventana.close();
      return;
    }

    const quedan = AVISO + RESPUESTA - sinUso;
    if (quedan <= 0) {
      cerrarSesion(true);
      return;
    }

    segundos.textContent = String(Math.ceil(quedan / 1000));
    if (!ventana.open) {
      ventana.showModal();
      sigo.focus();
    }
  };

  // ¿La sesión sigue abierta? Si la contraseña cambió o la cuenta se
  // eliminó, el servidor ya la cerró: la pestaña se va al login, que dice por qué. Es de fondo: no cuenta como uso.
  let preguntando = false;
  const revisarSesion = async () => {
    if (cerrando || preguntando || document.hidden) return;
    preguntando = true;
    try {
      await request(ventana.dataset.estadoUrl, { background: true });
    } catch (error) {
      if (error instanceof ApiError && error.isUnauthenticated && !cerrando) {
        cerrando = true;
        avisarCierre();
        goToLogin(error);
      }
      // Sin conexión: se pregunta de nuevo en la próxima vuelta.
    } finally {
      preguntando = false;
    }
  };

  sigo.addEventListener("click", () => {
    ultimoUso = Date.now();
    guardarCompartido(ultimoUso);
    ventana.close();
    avisarAlServidor();
  });

  formulario.addEventListener("submit", () => {
    cerrando = true;
    avisarCierre();
  });

  // Escape no cierra la ventana sin responder: la pregunta sigue en pie hasta que se conteste o venza.
  ventana.addEventListener("cancel", (event) => event.preventDefault());

  ["pointerdown", "keydown", "wheel", "touchstart", "scroll"].forEach((evento) => {
    window.addEventListener(evento, huboUso, { passive: true, capture: true });
  });

  guardarCompartido(ultimoUso);
  window.setInterval(revisar, 1000);
  window.setInterval(revisarSesion, REVISAR_SESION);
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) return;
    revisar();
    revisarSesion();
  });
})();
