/* ==========================================================
   Comportamientos comunes de la interfaz.
   Se activan con atributos data-* en el HTML: no hace falta escribir JavaScript por cada página.
   Todos escuchan en document (delegación de eventos): sirven igual para el contenido que llega después, como la etapa
   nueva de una ventana (ventana.js), sin volver a enganchar nada. Solo el estado inicial de algunos campos se prepara
   al cargar o al cambiar el contenido (prepare).
   ========================================================== */

const SCANNER_PAUSE_MS = 200;
const TAP_TO_SUBMIT_MS = 1500;
const STEPPER_VALUE = /^\d{1,5}$/;

/* ---------- Mostrar u ocultar una contraseña ----------
   <button type="button" data-toggle-password="id-del-campo" aria-pressed="false"> */
document.addEventListener("click", (event) => {
  const button = event.target.closest?.("[data-toggle-password]");
  if (!button) return;
  const input = document.getElementById(button.dataset.togglePassword);
  if (!input) return;

  const show = input.type === "password";
  input.type = show ? "text" : "password";
  button.setAttribute("aria-pressed", String(show));
  button.setAttribute("aria-label", show ? "Ocultar contraseña" : "Mostrar contraseña");
  input.focus();
});

/* ---------- Enviar una sola vez (doble clic o Enter repetido) ----------
   En el login, un segundo envío contaría como otro intento.
   <form data-submit-once> … <button type="submit" data-loading-text="Ingresando…">
   Un botón con nombre y valor (<button name="sector_id" value="3">) dice cuál opción se tocó; desactivado ya no se
   enviaría, así que su valor se copia antes en un campo oculto. En captura: corre antes que ventana.js, que envía el
   formulario por su cuenta. */
document.addEventListener("submit", (event) => {
  const form = event.target;
  if (!form.matches?.("form[data-submit-once]")) return;
  if (form.dataset.submitting === "true") {
    event.preventDefault();
    return;
  }
  form.dataset.submitting = "true";

  if (event.submitter?.name) {
    const copy = document.createElement("input");
    copy.type = "hidden";
    copy.name = event.submitter.name;
    copy.value = event.submitter.value;
    copy.dataset.botonTocado = "";
    form.append(copy);
  }

  form.querySelectorAll('[type="submit"]').forEach((button) => {
    button.disabled = true;
    if (!button.dataset.loadingText) return;
    button.dataset.originalHtml = button.innerHTML;
    button.textContent = button.dataset.loadingText;
  });
}, { capture: true });

/* ---------- Cantidad con − y + ----------
   Solo enteros: todo se cuenta por unidad. data-max (opcional) es el tope del +, como lo que hay en bodega al retirar.
   <div data-stepper data-min="0" data-max="12"> <button type="button" data-step="-1"> <input> <button data-step="1">
   Escanear otra vez el producto suma 1, pero la lectura reemplaza el número del campo. Por eso el número que se ve
   queda guardado aparte (<input type="hidden" data-cantidad-antes>): al tocar − / + y al terminar de escribir. Lo
   escrito se guarda tras una pausa, así los dígitos de un código leído (llegan seguidos) no lo pisan. */
const stepperInput = (stepper) => stepper.querySelector("input:not([type=hidden])");

const rememberStepper = (stepper) => {
  const input = stepperInput(stepper);
  const previous = stepper.querySelector("[data-cantidad-antes]");
  if (input && previous && STEPPER_VALUE.test(input.value.trim())) previous.value = input.value.trim();
};

const pendingRemember = new WeakMap();

document.addEventListener("click", (event) => {
  const button = event.target.closest?.("[data-stepper] [data-step]");
  if (!button) return;
  const stepper = button.closest("[data-stepper]");
  const input = stepperInput(stepper);
  if (!input) return;

  const min = Number(stepper.dataset.min ?? 0);
  const max = stepper.dataset.max === undefined ? Infinity : Number(stepper.dataset.max);
  const current = Number.parseInt(input.value, 10) || 0;
  input.value = String(Math.min(max, Math.max(min, current + Number(button.dataset.step))));
  rememberStepper(stepper);
  input.dispatchEvent(new Event("change", { bubbles: true }));
});

document.addEventListener("input", (event) => {
  const stepper = event.target.closest?.("[data-stepper]");
  if (!stepper || event.target !== stepperInput(stepper)) return;
  clearTimeout(pendingRemember.get(stepper));
  pendingRemember.set(stepper, setTimeout(() => rememberStepper(stepper), SCANNER_PAUSE_MS));
});

/* ---------- Seleccionar al tocar ----------
   El número de una cantidad y el campo de lectura con la pistola (<input data-lectura>): al recibir el foco su texto
   queda seleccionado, así lo que se escriba o la próxima lectura lo reemplaza (escribir 5 sobre «0» da 5, no 50). */
const SELECT_ON_FOCUS = "[data-lectura], [data-stepper] input:not([type=hidden])";

document.addEventListener("focusin", (event) => {
  if (event.target.matches?.(SELECT_ON_FOCUS)) event.target.select();
});

/* ---------- Enter pasa a la siguiente pregunta ----------
   La pistola escribe el código y presiona Enter. En un formulario con más preguntas, ese Enter no debe enviarlo a
   medias: pasa al siguiente campo; si no hay más, al botón para guardar. <input data-enter-next> */
document.addEventListener("keydown", (event) => {
  if (event.key !== "Enter" || !event.target.matches?.("[data-enter-next]")) return;
  event.preventDefault();

  const input = event.target;
  const fields = [...input.form.querySelectorAll("input:not([type=hidden]):not([type=radio]), textarea")]
    .filter((field) => !field.disabled && field.offsetParent !== null);
  const next = fields[fields.indexOf(input) + 1] ?? input.form.querySelector("[type=submit]");
  next?.focus();
});

/* ---------- Elegir y avanzar ----------
   En un formulario de una sola pregunta con opciones, tocar una con el mouse o el dedo lo envía (un clic menos). Con el
   teclado no: las flechas recorren las opciones y Enter envía, como siempre. <form data-enviar-al-elegir> */
const lastTouch = new WeakMap();

document.addEventListener("pointerdown", (event) => {
  const form = event.target.closest?.("form[data-enviar-al-elegir]");
  if (form) lastTouch.set(form, Date.now());
});

document.addEventListener("change", (event) => {
  const form = event.target.closest?.("form[data-enviar-al-elegir]");
  if (!form || !event.target.matches("input[type=radio]")) return;
  if (Date.now() - (lastTouch.get(form) ?? 0) < TAP_TO_SUBMIT_MS) form.requestSubmit();
});

/* ---------- Ventana sobre la página ----------
   Esc la cierra, igual que «Cerrar». En un campo con texto, Esc primero lo borra (así funcionan las búsquedas). La
   ventana se busca en cada tecla: ventana.js la reemplaza al cambiar de etapa. */
document.addEventListener("keydown", (event) => {
  const modal = document.querySelector("[data-ventana]");
  if (!modal || event.key !== "Escape" || event.defaultPrevented) return;
  if (event.target.matches?.("input, textarea") && event.target.value !== "") return;
  window.location.assign(modal.dataset.cerrar);
});

/* ---------- Foco ----------
   Con la pistola todo depende de que el cursor esté en el cuadro de lectura. Pone el foco en el [autofocus] de root (o
   en su [data-foco], sin mover la página: para un campo que está más abajo y así no esconder lo de arriba). */
const focusFirst = (root) => {
  const autofocus = root.querySelector("[autofocus]");
  if (autofocus) {
    autofocus.focus();
    return;
  }
  root.querySelector("[data-foco]")?.focus({ preventScroll: true });
};

/** Al cargar, solo si el navegador no puso el foco (a veces pasa, por ejemplo al volver con «Atrás»). */
const focusOnLoad = () => {
  if (document.activeElement !== document.body && document.activeElement !== null) return;
  focusFirst(document);
};

/** El estado inicial de lo que llegó: cada cantidad recuerda su número y el campo con foco queda seleccionado. */
const prepare = (root) => {
  root.querySelectorAll("[data-stepper]").forEach(rememberStepper);
  if (document.activeElement?.matches?.(SELECT_ON_FOCUS) && root.contains(document.activeElement)) {
    document.activeElement.select();
  }
};

/* ---------- Al volver con «Atrás» ----------
   El navegador puede mostrar la página tal como quedó: los formularios se habilitan de nuevo. */
window.addEventListener("pageshow", (event) => {
  if (!event.persisted) return;

  focusOnLoad();
  document.querySelectorAll("form[data-submit-once]").forEach((form) => {
    delete form.dataset.submitting;
    form.querySelectorAll("[data-boton-tocado]").forEach((copy) => copy.remove());
    form.querySelectorAll('[type="submit"]').forEach((button) => {
      button.disabled = false;
      if (button.dataset.originalHtml) button.innerHTML = button.dataset.originalHtml;
    });
  });
});

/* ---------- Contenido nuevo ----------
   ventana.js cambia la etapa de una ventana sin recargar: los módulos que preparan algo en el contenido (como las
   sugerencias de insumos.js) se registran con onContentReady y se vuelven a llamar sobre lo nuevo. */
const contentHooks = [];

/** Un módulo pide que se prepare también lo suyo en el contenido que llegue después. */
export function onContentReady(hook) {
  contentHooks.push(hook);
}

/** Prepara el contenido nuevo (lo común y lo de los módulos) y pone el foco donde corresponde. */
export function refreshContent(root) {
  prepare(root);
  contentHooks.forEach((hook) => hook(root));
  focusFirst(root);
}

prepare(document);
focusOnLoad();
