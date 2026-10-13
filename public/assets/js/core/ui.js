/* ==========================================================
   Comportamientos comunes de la interfaz.
   Se activan con atributos data-* en el HTML: no hace falta
   escribir JavaScript por cada página.
   ========================================================== */
(() => {
  "use strict";

  /* Mostrar u ocultar una contraseña.
     <button type="button" data-toggle-password="id-del-campo" aria-pressed="false"> */
  const initPasswordToggles = (root) => {
    root.querySelectorAll("[data-toggle-password]").forEach((button) => {
      const input = document.getElementById(button.dataset.togglePassword);
      if (!input) return;

      button.addEventListener("click", () => {
        const show = input.type === "password";
        input.type = show ? "text" : "password";
        button.setAttribute("aria-pressed", String(show));
        button.setAttribute("aria-label", show ? "Ocultar contraseña" : "Mostrar contraseña");
        input.focus();
      });
    });
  };

  /* Evita enviar dos veces un formulario (doble clic o Enter repetido). En el login,
     un segundo envío contaría como otro intento.
     <form data-submit-once> … <button type="submit" data-loading-text="Ingresando…">
     Un botón con nombre y valor (<button name="sector_id" value="3">) dice cuál opción se tocó; desactivado ya no se
     enviaría, así que su valor se copia antes en un campo oculto. */
  const initSubmitOnce = (root) => {
    root.querySelectorAll("form[data-submit-once]").forEach((form) => {
      form.addEventListener("submit", (event) => {
        if (form.dataset.submitting === "true") {
          event.preventDefault();
          return;
        }
        form.dataset.submitting = "true";

        if (event.submitter?.name) {
          const copia = document.createElement("input");
          copia.type = "hidden";
          copia.name = event.submitter.name;
          copia.value = event.submitter.value;
          copia.dataset.botonTocado = "";
          form.append(copia);
        }

        form.querySelectorAll('[type="submit"]').forEach((button) => {
          button.disabled = true;
          if (button.dataset.loadingText) {
            button.dataset.originalHtml = button.innerHTML;
            button.textContent = button.dataset.loadingText;
          }
        });
      });
    });
  };

  /* Al volver con "Atrás", el navegador puede mostrar la página tal como quedó:
     los formularios se habilitan de nuevo. */
  window.addEventListener("pageshow", (event) => {
    if (!event.persisted) return;

    initFocoInicial();

    document.querySelectorAll("form[data-submit-once]").forEach((form) => {
      delete form.dataset.submitting;
      form.querySelectorAll("[data-boton-tocado]").forEach((copia) => copia.remove());
      form.querySelectorAll('[type="submit"]').forEach((button) => {
        button.disabled = false;
        if (button.dataset.originalHtml) button.innerHTML = button.dataset.originalHtml;
      });
    });
  });

  /* Cantidad con botones − y +, para no tener que escribir. Solo números enteros: todo se cuenta por unidad.
     <div data-stepper data-min="0">
       <button type="button" data-step="-1"> <input> <button type="button" data-step="1"> */
  const initSteppers = (root) => {
    root.querySelectorAll("[data-stepper]").forEach((stepper) => {
      const input = stepper.querySelector("input");
      if (!input) return;

      const min = Number(stepper.dataset.min ?? 0);

      // Al tocar el número queda seleccionado: lo que se escriba lo reemplaza (escribir 5 sobre «0» da 5, no 50).
      input.addEventListener("focus", () => input.select());
      if (document.activeElement === input) input.select();

      stepper.querySelectorAll("[data-step]").forEach((button) => {
        button.addEventListener("click", () => {
          const actual = Number.parseInt(input.value, 10) || 0;
          input.value = String(Math.max(min, actual + Number(button.dataset.step)));
          input.dispatchEvent(new Event("change", { bubbles: true }));
        });
      });
    });
  };

  /* La pistola escribe el código y presiona Enter. En un formulario con más preguntas, ese Enter
     no debe enviarlo a medias: pasa a la siguiente pregunta. <input data-enter-next> */
  const initEnterNext = (root) => {
    root.querySelectorAll("[data-enter-next]").forEach((input) => {
      input.addEventListener("keydown", (event) => {
        if (event.key !== "Enter") return;
        event.preventDefault();

        // Siguiente campo donde se escribe; si no hay más, el botón para guardar.
        const fields = [...input.form.querySelectorAll("input:not([type=hidden]):not([type=radio]), textarea")]
          .filter((field) => !field.disabled && field.offsetParent !== null);
        const next = fields[fields.indexOf(input) + 1] ?? input.form.querySelector("[type=submit]");
        if (next) next.focus();
      });
    });
  };

  /* Campo de lectura con la pistola: al recibir el foco su texto queda seleccionado, así la próxima lectura lo
     reemplaza en vez de pegarse a lo anterior. <input data-lectura> */
  const initLectura = (root) => {
    root.querySelectorAll("[data-lectura]").forEach((input) => {
      input.addEventListener("focus", () => input.select());
      if (document.activeElement === input) input.select();
    });
  };

  /* Foco inicial. Con la pistola todo depende de que el cursor esté en el cuadro de lectura, así que si el navegador
     no aplicó el autofocus (a veces pasa, por ejemplo al volver con «Atrás»), se pone aquí.
     <input data-foco>: foco sin mover la página, para un campo que está más abajo y así no esconder lo de arriba.
     Se usa en vez de autofocus. */
  const initFocoInicial = () => {
    if (document.activeElement !== document.body && document.activeElement !== null) return;

    const autofocus = document.querySelector("[autofocus]");
    if (autofocus) {
      autofocus.focus();
      return;
    }
    document.querySelector("[data-foco]")?.focus({ preventScroll: true });
  };

  /* Ventana sobre la página (parcial «ventana»): la tecla Esc la cierra, igual que «Cerrar». En un campo con texto,
     Esc primero lo borra (así funcionan los campos de búsqueda). */
  const initVentana = () => {
    const ventana = document.querySelector("[data-ventana]");
    if (!ventana) return;

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape" || event.defaultPrevented) return;
      if (event.target.matches?.("input, textarea") && event.target.value !== "") return;
      window.location.assign(ventana.dataset.cerrar);
    });
  };

  initPasswordToggles(document);
  initSubmitOnce(document);
  initEnterNext(document);
  initSteppers(document);
  initFocoInicial();
  initLectura(document);
  initVentana();

  // Para los scripts de cada módulo que agregan contenido nuevo a la página.
  window.Bodega = { initSteppers, initSubmitOnce };
})();
