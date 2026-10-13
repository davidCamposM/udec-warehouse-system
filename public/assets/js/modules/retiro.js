/* ==========================================================
   Punto de retiro, como la caja del supermercado:
   - Cada lectura se envía sin recargar la página y en orden: el
     campo se limpia al instante, así no se pierde ningún código
     aunque se escanee rápido.
   - Un pitido corto confirma cada producto agregado y uno grave
     avisa de un problema (además del mensaje en pantalla).
   - Tras un rato sin uso pregunta «¿Sigues ahí?» y, si nadie
     responde, cierra la identificación.
   Sin JavaScript todo funciona igual, recargando la página.
   <div data-retiro data-inactividad="90" data-sigo-url="…" data-terminar-url="…" data-salir-url="…">
     <form data-retiro-lectura> · <div data-retiro-mensaje> · <div data-retiro-pantalla>
       (formularios data-retiro-form y data-retiro-confirmar) · <div data-retiro-sigues>
   ========================================================== */
(() => {
  "use strict";

  const raiz = document.querySelector("[data-retiro]");
  if (!raiz) return;

  const lectura = raiz.querySelector("[data-retiro-lectura]");
  const campo = lectura.querySelector("input[name=codigo]");
  const mensaje = raiz.querySelector("[data-retiro-mensaje]");
  const pantalla = raiz.querySelector("[data-retiro-pantalla]");
  const sigues = raiz.querySelector("[data-retiro-sigues]");
  const cuenta = raiz.querySelector("[data-retiro-cuenta]");
  const segundos = Math.max(30, Number(raiz.dataset.inactividad) || 90);
  const AVISO = 15;
  const token = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
  const cola = [];
  let ocupado = false;
  let confirmarPendiente = null;
  let cerrando = false;

  /* ---------- Sonido: un tono corto (bien) o grave y largo (problema). Sin archivos. ---------- */
  let audio = null;
  const pitar = (bien) => {
    try {
      audio ??= new (window.AudioContext || window.webkitAudioContext)();
      const duracion = bien ? 0.12 : 0.4;
      const tono = audio.createOscillator();
      const volumen = audio.createGain();
      tono.type = bien ? "sine" : "square";
      tono.frequency.value = bien ? 1046 : 196;
      volumen.gain.setValueAtTime(bien ? 0.18 : 0.08, audio.currentTime);
      volumen.gain.exponentialRampToValueAtTime(0.001, audio.currentTime + duracion);
      tono.connect(volumen).connect(audio.destination);
      tono.start();
      tono.stop(audio.currentTime + duracion);
    } catch {
      // Sin sonido queda la señal en pantalla.
    }
  };

  /* ---------- Mensaje de cada lectura, con su color y su palabra ---------- */
  const mostrar = (texto, tono) => {
    const variante = { ok: "success", info: "info", error: "danger" }[tono] ?? "info";
    const caja = document.createElement("div");
    caja.className = `alert alert--${variante} flash is-nuevo`;
    caja.setAttribute("role", tono === "error" ? "alert" : "status");
    const parrafo = document.createElement("p");
    parrafo.textContent = texto;
    caja.append(parrafo);
    mensaje.replaceChildren(caja);
  };

  const salir = () => window.location.assign(raiz.dataset.salirUrl);

  /* ---------- Envíos en orden ---------- */
  const enviar = async (accion, datos) => {
    const respuesta = await fetch(accion, {
      method: "POST",
      body: datos,
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
    });
    const json = await respuesta.json().catch(() => ({}));

    // La identificación se cerró o el equipo dejó de estar habilitado: a la pantalla inicial.
    if (json.cerrado || respuesta.status === 401 || respuesta.status === 403) {
      salir();
      return;
    }

    if (!respuesta.ok) {
      mostrar(Object.values(json.errores ?? {})[0] ?? json.error ?? "No se pudo. Inténtalo de nuevo.", "error");
      pitar(false);
      return;
    }

    pantalla.innerHTML = json.html;
    window.Bodega?.initSubmitOnce(pantalla);
    mostrar(json.mensaje, json.tono);
    if (json.tono === "ok") pitar(true);
  };

  const procesar = async () => {
    if (ocupado) return;
    ocupado = true;

    while (cola.length > 0) {
      const [accion, datos] = cola.shift();
      try {
        await enviar(accion, datos);
      } catch {
        mostrar("Sin conexión con el sistema. Vuelve a escanear.", "error");
        pitar(false);
      }
    }

    ocupado = false;
    if (!pantalla.contains(document.activeElement) || document.activeElement === document.body) campo.focus();
    confirmarPendiente?.();
  };

  const encolar = (accion, datos) => {
    cola.push([accion, datos]);
    procesar();
  };

  // La lectura: se toma lo leído y el campo queda vacío al instante para el siguiente código.
  lectura.addEventListener("submit", (event) => {
    event.preventDefault();
    if (campo.value.trim() === "") return;
    const datos = new FormData(lectura);
    campo.value = "";
    campo.focus();
    encolar(lectura.action, datos);
  });

  // − / +, cantidad escrita, «Quitar» y «Agregar» en los resultados.
  pantalla.addEventListener("submit", (event) => {
    const form = event.target;

    // «Confirmar retiro» espera a que terminen las lecturas que aún se están enviando. Para entonces la pantalla ya
    // se reemplazó: se confirma con el formulario nuevo.
    if (form.matches("[data-retiro-confirmar]") && (ocupado || cola.length > 0)) {
      event.preventDefault();
      confirmarPendiente = () => {
        confirmarPendiente = null;
        pantalla.querySelector("[data-retiro-confirmar]")?.requestSubmit();
      };
      return;
    }

    if (!form.matches("[data-retiro-form]")) return;
    event.preventDefault();
    const datos = new FormData(form);
    if (event.submitter?.name) datos.append(event.submitter.name, event.submitter.value);
    encolar(form.action, datos);
  });

  /* ---------- «¿Sigues ahí?» ---------- */
  let ultimaActividad = Date.now();
  let ultimoAvisoAlServidor = Date.now();

  const avisarAlServidor = () => {
    ultimoAvisoAlServidor = Date.now();
    const datos = new FormData();
    datos.append("_csrf", token);
    fetch(raiz.dataset.sigoUrl, {
      method: "POST",
      body: datos,
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
    })
      .then((respuesta) => respuesta.json())
      .then((json) => {
        if (json.abierto === false) salir();
      })
      .catch(() => {});
  };

  const actividad = () => {
    ultimaActividad = Date.now();
    if (!sigues.hidden) {
      sigues.hidden = true;
      campo.focus();
    }
    // Las lecturas ya renuevan el tiempo en el servidor; tocar la pantalla lo renueva cada 20 segundos como mucho.
    if (Date.now() - ultimoAvisoAlServidor > 20000) avisarAlServidor();
  };

  ["keydown", "pointerdown"].forEach((evento) => document.addEventListener(evento, actividad, { passive: true }));
  raiz.querySelector("[data-retiro-sigo]")?.addEventListener("click", avisarAlServidor);

  setInterval(() => {
    const quedan = segundos - Math.floor((Date.now() - ultimaActividad) / 1000);

    if (quedan <= 0) {
      if (cerrando) return;
      cerrando = true;
      // Se cierra en el servidor («Terminar») y se vuelve a la pantalla inicial.
      const datos = new FormData();
      datos.append("_csrf", token);
      fetch(raiz.dataset.terminarUrl, { method: "POST", body: datos, credentials: "same-origin" }).finally(salir);
      return;
    }

    if (quedan <= AVISO) {
      sigues.hidden = false;
      cuenta.textContent = String(quedan);
    }
  }, 1000);
})();
