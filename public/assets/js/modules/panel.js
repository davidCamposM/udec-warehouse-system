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
  const content = panel.querySelector("[data-panel-content]");
  const status = panel.querySelector("[data-panel-status]");
  let timer = null;
  let loading = false;

  const now = () => new Date().toLocaleTimeString("es-CL", { hour: "2-digit", minute: "2-digit", hourCycle: "h23" });

  const setStatus = (text, state) => {
    status.textContent = text;
    status.dataset.state = state;
  };

  const refresh = async () => {
    if (loading || document.hidden) return;
    loading = true;

    try {
      const response = await fetch(url, {
        headers: { "X-Bodega-Background": "1", "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
        cache: "no-store",
      });

      // La sesión se cerró (inactividad o cuenta desactivada): al login, donde se explica.
      if (response.status === 401) {
        const data = await response.json().catch(() => ({}));
        window.location.assign(data.login || window.location.href);
        return;
      }
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      content.innerHTML = await response.text();
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

  start();
})();
