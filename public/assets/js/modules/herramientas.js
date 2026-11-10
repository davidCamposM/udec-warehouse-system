/* Herramientas: encontrar rápido una entre muchas, y en «Solicitar herramientas», ver lo que se lleva.
   - Buscador (parcial Herramientas::partials/buscador): el campo filtra por nombre mientras se escribe (sin importar
     tildes ni mayúsculas) y las pastillas muestran una categoría, todas o, al solicitar, solo las elegidas. Los grupos
     sin herramientas visibles se ocultan. Sin JavaScript el buscador no aparece y se ven todas.
   - Al solicitar: la ficha de cada herramienta con unidades se marca como elegida, y la pastilla «Elegidas» dice
     cuántas lleva.
   Los eventos se escuchan una vez en el contenedor (delegación), no en cada pastilla ni en cada ficha: hay decenas. */
import { onContentReady } from "../core/ui.js";

(() => {
  "use strict";

  const iniciar = (root) => {
    const fichas = [...root.querySelectorAll("[data-herramienta]")];
    if (fichas.length === 0) return;

    const normalizar = (texto) => texto.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().trim();
    const unidadesDe = (ficha) => {
      const valor = Number.parseInt(ficha.querySelector("input")?.value ?? "", 10);
      return Number.isFinite(valor) && valor > 0 ? valor : 0;
    };

    // ---------- Buscador ----------
    const buscador = root.querySelector("[data-buscador-herramientas]");
    const campo = root.querySelector("[data-buscar-herramienta]");
    const pastillas = [...root.querySelectorAll("[data-ver-grupo]")];
    const grupos = [...root.querySelectorAll("[data-grupo]")];
    const sinResultados = root.querySelector("[data-sin-resultados]");
    let grupoElegido = "todas";

    const filtrar = () => {
      const texto = normalizar(campo?.value ?? "");
      let visibles = 0;

      grupos.forEach((grupo) => {
        let enGrupo = 0;
        grupo.querySelectorAll("[data-herramienta]").forEach((ficha) => {
          const deGrupo = grupoElegido === "todas"
            || (grupoElegido === "elegidas" ? unidadesDe(ficha) > 0 : grupo.dataset.grupo === grupoElegido);
          const ver = deGrupo && normalizar(ficha.dataset.nombre ?? "").includes(texto);
          ficha.hidden = !ver;
          if (ver) enGrupo += 1;
        });
        grupo.hidden = enGrupo === 0;
        visibles += enGrupo;
      });

      if (sinResultados) sinResultados.hidden = visibles > 0;
    };

    const elegirGrupo = (clave) => {
      grupoElegido = clave;
      pastillas.forEach((pastilla) => {
        const activa = pastilla.dataset.verGrupo === clave;
        pastilla.classList.toggle("is-activa", activa);
        pastilla.setAttribute("aria-pressed", String(activa));
      });
      filtrar();
    };

    if (buscador) {
      buscador.hidden = false;
      buscador.addEventListener("click", (event) => {
        const pastilla = event.target.closest("[data-ver-grupo]");
        if (pastilla) elegirGrupo(pastilla.dataset.verGrupo);
      });
      // Al escribir se busca en todas las categorías.
      campo?.addEventListener("input", () => {
        if (grupoElegido !== "todas") elegirGrupo("todas");
        else filtrar();
      });
      // Enter en el buscador no envía el formulario de la solicitud.
      campo?.addEventListener("keydown", (event) => {
        if (event.key === "Enter") event.preventDefault();
      });
    }

    // ---------- Lo que se lleva (solo al solicitar) ----------
    // La pastilla «Elegidas» solo está en el formulario de la solicitud.
    const cuentaElegidas = root.querySelector("[data-cuenta-elegidas]");
    if (!cuentaElegidas) return;

    const actualizar = () => {
      let herramientas = 0;

      fichas.forEach((ficha) => {
        const cuantas = unidadesDe(ficha);
        ficha.classList.toggle("is-elegida", cuantas > 0);
        if (cuantas > 0) herramientas += 1;
      });

      cuentaElegidas.textContent = String(herramientas);
      if (grupoElegido === "elegidas") filtrar();
    };

    // Las unidades de cualquier ficha: un solo oyente en el formulario que las contiene.
    const alCambiarUnidades = (event) => {
      if (event.target.closest?.("[data-herramienta]")) actualizar();
    };
    const contenedor = fichas[0].closest("form") ?? root;
    contenedor.addEventListener("input", alCambiarUnidades);
    contenedor.addEventListener("change", alCambiarUnidades);

    actualizar();
  };

  iniciar(document);
  // También cuando la ventana vuelve con errores sin recargar la página (ventana.js).
  onContentReady(iniciar);
})();
