/* ==========================================================
   Ventanas por etapas (parcial «ventana»), rápidas y con sonido.
   - Sin recargar: al enviar un formulario o tocar un enlace dentro de la ventana, se pide la página siguiente por
     detrás y solo se cambia la ventana (el inicio de fondo queda igual): cada etapa aparece como una ventana nueva
     (la anterior sale por un lado y la nueva entra por el otro), sin parpadeo, y el cursor queda listo para la pistola. La dirección del navegador se actualiza, así «Atrás» y
     recargar funcionan. Si la respuesta no es una ventana de la misma tarea (por ejemplo, «Finalizar» lleva al
     inicio), se navega normal.
   - Con sonido, para trabajar sin mirar la pantalla: un bip corto al avanzar, tres notas que suben al terminar
     (aparece la boleta «¡Listo!») y un tono grave si algo quedó marcado como error.
   Sin JavaScript todo funciona igual, recargando la página en cada etapa.
   ========================================================== */
(() => {
  "use strict";

  const actual = () => document.querySelector("[data-ventana]");
  if (!actual() || !window.fetch || !window.DOMParser) return;

  const sinMovimiento = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // ---------- Sonidos (Web Audio: sin archivos) ----------
  let audio = null;
  const SONIDOS = {
    paso: [[880, 0.07]],
    listo: [[660, 0.09], [880, 0.09], [1320, 0.16]],
    error: [[220, 0.16], [165, 0.24]],
  };

  const sonar = (nombre) => {
    try {
      audio ??= new (window.AudioContext || window.webkitAudioContext)();
      let inicio = audio.currentTime + 0.01;
      SONIDOS[nombre].forEach(([frecuencia, duracion]) => {
        const oscilador = audio.createOscillator();
        const volumen = audio.createGain();
        oscilador.type = nombre === "error" ? "square" : "sine";
        oscilador.frequency.value = frecuencia;
        volumen.gain.setValueAtTime(0.0001, inicio);
        volumen.gain.exponentialRampToValueAtTime(nombre === "error" ? 0.08 : 0.2, inicio + 0.01);
        volumen.gain.exponentialRampToValueAtTime(0.0001, inicio + duracion);
        oscilador.connect(volumen).connect(audio.destination);
        oscilador.start(inicio);
        oscilador.stop(inicio + duracion + 0.02);
        inicio += duracion + 0.03;
      });
    } catch {
      /* Sin audio (navegador sin Web Audio o sin parlantes): se sigue sin sonido. */
    }
  };

  const sonidoDe = (ventana) => {
    if (ventana.querySelector(".field__error, .alert--danger")) return "error";
    if (ventana.querySelector(".listo, .boleta")) return "listo";
    return "paso";
  };

  // ---------- Cambiar la ventana sin recargar ----------
  const rutasCargadas = (selector, atributo) => new Set([...document.querySelectorAll(selector)]
    .map((elemento) => elemento.getAttribute(atributo))
    .filter(Boolean)
    .map((ruta) => new URL(ruta, window.location.href).pathname));

  // La página nueva necesita un script o una hoja de estilos que esta no tiene: se navega normal.
  const necesitaOtrosArchivos = (doc) => {
    const scripts = rutasCargadas("script[src]", "src");
    const hojas = rutasCargadas('link[rel="stylesheet"]', "href");
    return [...doc.querySelectorAll("script[src]")].some((s) => !scripts.has(new URL(s.getAttribute("src"), window.location.href).pathname))
      || [...doc.querySelectorAll('link[rel="stylesheet"]')].some((l) => !hojas.has(new URL(l.getAttribute("href"), window.location.href).pathname));
  };

  let ocupado = false;

  /** @param {"adelante"|"atras"} direccion hacia dónde se mueve la ventana al cambiar (ventana-basico.css) */
  const ir = async (url, opciones, direccion = "adelante") => {
    if (ocupado) return;
    ocupado = true;
    document.documentElement.dataset.direccion = direccion;
    actual()?.querySelector(".ventana__caja")?.setAttribute("aria-busy", "true");

    let respuesta;
    let html;
    try {
      respuesta = await fetch(url, { ...opciones, credentials: "same-origin", cache: "no-store" });
      html = await respuesta.text();
    } catch {
      // Sin conexión o el servidor no respondió: se recarga, y el navegador muestra qué pasó.
      window.location.reload();
      return;
    }

    const doc = new DOMParser().parseFromString(html, "text/html");
    const nueva = doc.querySelector("[data-ventana]");

    if (!nueva || necesitaOtrosArchivos(doc)) {
      // Una página de otro tipo (el inicio, una ficha, el login, una página de error): se muestra normal.
      if (opciones.method === "GET" || respuesta.redirected) {
        window.location.assign(respuesta.url);
      } else {
        document.open();
        document.write(html);
        document.close();
      }
      return;
    }

    const cambiar = () => {
      actual().replaceWith(document.importNode(nueva, true));
      document.title = doc.title;
    };
    if (document.startViewTransition && !sinMovimiento) {
      await document.startViewTransition(cambiar).updateCallbackDone;
    } else {
      cambiar();
      // Sin View Transitions: la ventana nueva entra sola, como una ventana que aparece.
      actual().querySelector(".ventana__caja")?.classList.add("is-entrando");
    }

    if (respuesta.url !== window.location.href) {
      window.history.pushState({ ventana: true }, "", respuesta.url);
    }

    const ventana = actual();
    window.Bodega?.reiniciar(ventana);
    sonar(sonidoDe(ventana));
    ocupado = false;
  };

  // Formularios de la ventana. ui.js (data-submit-once) ya desactivó el botón y copió el botón tocado.
  document.addEventListener("submit", (event) => {
    const form = event.target;
    if (event.defaultPrevented || !form.closest("[data-ventana]") || (form.target && form.target !== "_self")) return;
    event.preventDefault();

    const datos = new FormData(form);
    if (event.submitter?.name && !datos.has(event.submitter.name)) datos.append(event.submitter.name, event.submitter.value);

    if ((form.method || "get").toUpperCase() === "GET") {
      const destino = new URL(form.action, window.location.href);
      destino.search = new URLSearchParams(datos).toString();
      ir(destino, { method: "GET" });
    } else {
      ir(form.action, { method: "POST", body: datos });
    }
  });

  // Enlaces de la ventana: «Atrás», «Cambiar», los productos de la lista. «Cerrar» y «Finalizar» van al inicio: normal.
  document.addEventListener("click", (event) => {
    const enlace = event.target.closest("a[href]");
    const ventana = enlace?.closest("[data-ventana]");
    if (!ventana || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey
      || event.altKey || enlace.target || enlace.hasAttribute("download")) return;

    const destino = new URL(enlace.href, window.location.href);
    const cerrar = new URL(ventana.dataset.cerrar, window.location.href);
    if (destino.origin !== window.location.origin || destino.href === cerrar.href) return;

    // «Atrás» (abajo, junto al botón principal) y «Cambiar» vuelven a una etapa anterior; lo demás avanza.
    const vuelve = enlace.closest(".formulario__acciones, .campo__fijo") !== null;

    event.preventDefault();
    ir(destino, { method: "GET" }, vuelve ? "atras" : "adelante");
  });

  // «Atrás» y «Adelante» del navegador: se carga esa etapa desde el servidor.
  window.addEventListener("popstate", () => window.location.reload());
})();
