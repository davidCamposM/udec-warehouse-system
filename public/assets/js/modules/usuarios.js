/* Usuarios: «Copiar enlace» para compartirlo.
   <button data-copiar="id-del-campo" data-copiado="¡Copiado!"> copia el texto del campo con ese id; el botón muestra
   «¡Copiado!» un momento. Sin permiso para el portapapeles, deja el texto seleccionado para copiarlo con Ctrl+C. */
(() => {
  "use strict";

  document.querySelectorAll("[data-copiar]").forEach((boton) => {
    const campo = document.getElementById(boton.dataset.copiar);
    if (!campo) return;
    const original = boton.innerHTML;

    boton.addEventListener("click", async () => {
      campo.focus();
      campo.select();
      try {
        await navigator.clipboard.writeText(campo.value);
      } catch {
        // Sin acceso al portapapeles (sitio sin HTTPS): el texto queda seleccionado.
        if (!document.execCommand || !document.execCommand("copy")) return;
      }
      boton.textContent = boton.dataset.copiado || "¡Copiado!";
      boton.classList.add("is-copiado");
      setTimeout(() => {
        boton.innerHTML = original;
        boton.classList.remove("is-copiado");
      }, 2500);
    });
  });
})();
