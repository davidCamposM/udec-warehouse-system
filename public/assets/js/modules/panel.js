/* ==========================================================
   Panel de inicio: vuelve a pedir el resumen cada cierto tiempo
   para que se vea lo que pasa en la bodega sin recargar la página.
   <div data-panel data-panel-url="…" data-panel-interval="30">
     <p data-panel-status> … <div data-panel-content>
   Las peticiones van marcadas como "en segundo plano": no cuentan
   como actividad, así la sesión igual se cierra por inactividad.
   ========================================================== */
(() => {
  "use strict";

  const panel = document.querySelector("[data-panel]");
  if (!panel) return;

  const url = panel.dataset.panelUrl;
  const seconds = Math.max(10, Number(panel.dataset.panelInterval) || 30);
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

  /** Pide un bloque y lo cambia solo si llegó algo distinto (así el gráfico no vuelve a animarse sin motivo). */
  const actualizar = async (content) => {
    const response = await fetch(content.dataset.panelUrl || url, {
      headers: { "X-Bodega-Background": "1", "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
      cache: "no-store",
    });

    // La sesión se cerró (inactividad o cuenta desactivada): al login, donde se explica.
    if (response.status === 401) {
      if (saliendo) return false;
      const data = await response.json().catch(() => ({}));
      window.location.assign(data.login || window.location.href);
      return false;
    }
    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    const nuevo = document.createElement("template");
    nuevo.innerHTML = await response.text();
    if (nuevo.innerHTML !== content.innerHTML) content.innerHTML = nuevo.innerHTML;
    contar(content);
    return true;
  };

  /** El número de la campana del docente: lo que dice la lista recién llegada (data-cuenta); con 0, no se muestra. */
  const contar = (content) => {
    const lista = content.querySelector("[data-cuenta]");
    const cuenta = panel.querySelector("[data-panel-cuenta]");
    if (!lista || !cuenta) return;
    const n = Number(lista.dataset.cuenta) || 0;
    cuenta.textContent = n > 99 ? "99+" : String(n);
    cuenta.hidden = n === 0;
  };

  // La campana del docente: al abrirla, sus notificaciones quedan vistas. El número desaparece de inmediato y se avisa
  // al servidor (así no vuelve a aparecer hasta que llegue algo nuevo, como en un chat).
  const token = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
  for (const campana of panel.querySelectorAll("details[data-vistas-url]")) {
    campana.addEventListener("toggle", () => {
      const cuenta = campana.querySelector("[data-panel-cuenta]");
      if (!campana.open || !cuenta || cuenta.hidden) return;
      cuenta.hidden = true;
      fetch(campana.dataset.vistasUrl, {
        method: "POST",
        headers: { "X-CSRF-Token": token, "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
        cache: "no-store",
      }).catch(() => {
        /* Sin conexión: el número vuelve en la próxima actualización y se intenta al abrirla otra vez. */
      });
    });
  }

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
        if (!(await actualizar(content))) return;
      }
      setStatus(`Actualizado a las ${now()}`, "ok");
    } catch {
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
