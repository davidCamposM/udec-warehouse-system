/* ==========================================================
   Panel de inicio: vuelve a pedir el resumen cada cierto tiempo
   para que se vea lo que pasa en la bodega sin recargar la página.
   <div data-panel data-panel-url="…" data-panel-interval="30">
     <p data-panel-status> … <div data-panel-content>
   Las peticiones van marcadas como "en segundo plano": no cuentan
   como actividad, así la sesión igual se cierra por inactividad.
   ========================================================== */
import { ApiError, goToLogin, request } from "../core/api.js";

const MIN_INTERVAL_SECONDS = 10;
const DEFAULT_INTERVAL_SECONDS = 30;
const MAX_BADGE = 99;

(() => {
  "use strict";

  const panel = document.querySelector("[data-panel]");
  if (!panel) return;

  const url = panel.dataset.panelUrl;
  const seconds = Math.max(MIN_INTERVAL_SECONDS, Number(panel.dataset.panelInterval) || DEFAULT_INTERVAL_SECONDS);
  // Cada bloque que se mantiene al día: los avisos (con la URL del panel) y, en el inicio del docente, el estado de las
  // herramientas (con su propia data-panel-url).
  const contents = Array.from(panel.querySelectorAll("[data-panel-content]"));
  const status = panel.querySelector("[data-panel-status]");
  let timer = null;
  let loading = false;
  // La página se está yendo (se cierra la sesión o se navega): no se pide nada más ni se redirige al login.
  let saliendo = false;

  const now = () => new Date().toLocaleTimeString("es-CL", { hour: "2-digit", minute: "2-digit", hourCycle: "h23" });

  const setStatus = (text, state) => {
    if (!status) return;
    status.textContent = text;
    status.dataset.state = state;
  };

  /**
   * Pide un bloque ({"html": …}) y lo cambia solo si llegó algo distinto (así el gráfico no vuelve a animarse sin
   * motivo). El HTML nuevo se arma en un <template> fuera de la página y entra de una sola vez (un solo repintado).
   */
  const actualizar = async (content) => {
    const { html } = await request(content.dataset.panelUrl || url, { background: true });

    const nuevo = document.createElement("template");
    nuevo.innerHTML = html;
    if (nuevo.innerHTML !== content.innerHTML) content.replaceChildren(nuevo.content);
    contar(content);
  };

  /** El número de la campana del docente: lo que dice la lista recién llegada (data-cuenta); con 0, no se muestra. */
  const contar = (content) => {
    const lista = content.querySelector("[data-cuenta]");
    const cuenta = panel.querySelector("[data-panel-cuenta]");
    if (!lista || !cuenta) return;
    const n = Number(lista.dataset.cuenta) || 0;
    cuenta.textContent = n > MAX_BADGE ? `${MAX_BADGE}+` : String(n);
    cuenta.hidden = n === 0;
  };

  // La campana del docente: al abrirla, sus notificaciones quedan vistas. El número desaparece de inmediato y se avisa
  // al servidor (así no vuelve a aparecer hasta que llegue algo nuevo, como en un chat). «toggle» no sube por el
  // documento: se escucha en captura, así sirve para cualquier campana sin engancharse a cada una.
  panel.addEventListener("toggle", (event) => {
    const campana = event.target;
    if (!campana.matches?.("details[data-vistas-url]")) return;
    const cuenta = campana.querySelector("[data-panel-cuenta]");
    if (!campana.open || !cuenta || cuenta.hidden) return;
    cuenta.hidden = true;
    request(campana.dataset.vistasUrl, { method: "POST" }).catch(() => {
      /* Sin conexión: el número vuelve en la próxima actualización y se intenta al abrirla otra vez. */
    });
  }, { capture: true });

  // Los paneles que se despliegan (la campana): se cierran al tocar fuera o con Escape.
  document.addEventListener("click", (evento) => {
    for (const abierto of document.querySelectorAll("details[data-desplegable][open]")) {
      if (!abierto.contains(evento.target)) abierto.open = false;
    }
  });
  document.addEventListener("keydown", (evento) => {
    if (evento.key !== "Escape") return;
    for (const abierto of document.querySelectorAll("details[data-desplegable][open]")) {
      abierto.open = false;
      abierto.querySelector("summary")?.focus();
    }
  });

  const refresh = async () => {
    if (loading || saliendo || document.hidden) return;
    loading = true;

    try {
      for (const content of contents) {
        await actualizar(content);
      }
      setStatus(`Actualizado a las ${now()}`, "ok");
    } catch (error) {
      // La sesión se cerró (inactividad o cuenta desactivada): al login, donde se explica. Si la página ya se estaba
      // yendo, no: así no se pierde el aviso «Tu sesión se cerró por inactividad».
      if (error instanceof ApiError && error.isUnauthenticated) {
        if (!saliendo) goToLogin(error);
        return;
      }
      setStatus("Sin conexión. Reintentando…", "error");
    } finally {
      loading = false;
    }
  };

  const start = () => {
    clearInterval(timer);
    timer = setInterval(refresh, seconds * 1000);
  };

  // Con la pestaña oculta no se pide nada; al volver, se actualiza de inmediato.
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      clearInterval(timer);
    } else {
      refresh();
      start();
    }
  });

  const detener = () => {
    saliendo = true;
    clearInterval(timer);
  };
  document.addEventListener("bodega:cerrando-sesion", detener);
  window.addEventListener("pagehide", detener);

  start();
})();
