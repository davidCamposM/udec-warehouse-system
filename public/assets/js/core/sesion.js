/* Sesión: la ventana «¿Sigues ahí?» (parcial «sesion»).
   - Uso = tocar, hacer clic, escribir o desplazar la página. Mientras hay uso, se avisa al servidor como mucho cada
     2 minutos (POST sesion/seguir), así la sesión no vence mientras alguien trabaja sin cambiar de página.
   - Tras data-aviso segundos sin uso aparece la ventana, con la cuenta regresiva de data-respuesta segundos.
     «Sí, sigo aquí» avisa al servidor y cierra la ventana; «No», o que la cuenta llegue a 0, cierra la sesión.
   - Las pestañas comparten el último uso (localStorage): una pestaña quieta no cierra la sesión si en otra se trabaja.
   - Si el computador estuvo suspendido más que todo ese tiempo, al volver la sesión se cierra de inmediato. */
(() => {
  "use strict";

  const ventana = document.querySelector("[data-sesion]");
  if (!ventana || typeof ventana.showModal !== "function") return;

  const AVISO = Number(ventana.dataset.aviso) * 1000;
  const RESPUESTA = Number(ventana.dataset.respuesta) * 1000;
  const CADA_AVISO_AL_SERVIDOR = 2 * 60 * 1000;
  const CLAVE = "bodega-ultimo-uso";

  const formulario = ventana.querySelector("[data-sesion-salir]");
  const motivo = ventana.querySelector("[data-sesion-motivo]");
  const segundos = ventana.querySelector("[data-sesion-segundos]");
  const sigo = ventana.querySelector("[data-sesion-sigo]");
  const token = document.querySelector('meta[name="csrf-token"]')?.content ?? "";

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
      const respuesta = await fetch(ventana.dataset.seguirUrl, {
        method: "POST",
        headers: { "X-CSRF-Token": token, "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
        credentials: "same-origin",
        cache: "no-store",
      });
      // La sesión ya se había cerrado (por ejemplo, otra pestaña dijo «No»): al login, donde se ve el aviso.
      if (respuesta.status === 401 || respuesta.status === 403) {
        const datos = await respuesta.json().catch(() => ({}));
        window.location.assign(datos.login ?? window.location.href);
      }
    } catch {
      /* Sin conexión: se intenta de nuevo con el próximo uso. */
    }
  };

  const cerrarSesion = (porInactividad) => {
    if (cerrando) return;
    cerrando = true;
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

  sigo.addEventListener("click", () => {
    ultimoUso = Date.now();
    guardarCompartido(ultimoUso);
    ventana.close();
    avisarAlServidor();
  });

  formulario.addEventListener("submit", () => {
    cerrando = true;
  });

  // Escape no cierra la ventana sin responder: la pregunta sigue en pie hasta que se conteste o venza.
  ventana.addEventListener("cancel", (event) => event.preventDefault());

  ["pointerdown", "keydown", "wheel", "touchstart", "scroll"].forEach((evento) => {
    window.addEventListener(evento, huboUso, { passive: true, capture: true });
  });

  guardarCompartido(ultimoUso);
  window.setInterval(revisar, 1000);
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) revisar();
  });
})();
