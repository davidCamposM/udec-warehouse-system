/* ==========================================================
   El inicio recuerda dónde estaba la persona: al abrir una tarea (Registrar, Retirar, Actualizar…), la ventana se
   abre sobre el inicio en la misma posición, y al cerrarla, cancelar o terminar se vuelve a ese mismo lugar, no
   arriba. También al volver al inicio desde otra pantalla.
   Solo el inicio (.inicio). Si la dirección trae un ancla (#inventario-insumo-…), manda el ancla (insumos-regreso.js).
   ========================================================== */
(() => {
  "use strict";

  const CLAVE = "inicio:posicion";
  const VIGENCIA = 30 * 60 * 1000; // media hora: después, el inicio parte arriba

  const leer = () => {
    try {
      const guardado = JSON.parse(sessionStorage.getItem(CLAVE) || "null");
      return guardado && Date.now() - guardado.at < VIGENCIA && Number.isFinite(guardado.y) ? guardado.y : null;
    } catch {
      return null;
    }
  };

  const guardar = () => {
    try {
      sessionStorage.setItem(CLAVE, JSON.stringify({ y: window.scrollY, at: Date.now() }));
    } catch {
      // Sin sessionStorage (bloqueado), el inicio simplemente parte arriba.
    }
  };

  if (!document.querySelector(".inicio")) return;

  const conVentana = document.querySelector("[data-ventana]") !== null;

  // En el inicio (sin ventana), la posición se guarda al salir: al tocar una tarea, un enlace o al cambiar de página.
  if (!conVentana) {
    document.addEventListener("click", (event) => {
      if (event.target instanceof Element && event.target.closest("a[href], button[type='submit']")) guardar();
    }, true);
    window.addEventListener("pagehide", guardar);
  }

  // Al llegar (el inicio solo, o de fondo bajo una ventana): se vuelve a donde estaba.
  const y = leer();
  if (y === null || (!conVentana && window.location.hash)) return;
  requestAnimationFrame(() => window.scrollTo(0, y));
})();
