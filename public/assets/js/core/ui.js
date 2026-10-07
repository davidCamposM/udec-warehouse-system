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

  initPasswordToggles(document);
})();
