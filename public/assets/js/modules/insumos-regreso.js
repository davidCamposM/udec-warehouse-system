/* Devuelve la ficha al punto exacto desde el que se abrió: la lista de «Buscar producto», el inventario del inicio o
   una barra de búsqueda (con lo que se estaba escribiendo). */
(() => {
  "use strict";

  const KEY = "insumos:regreso";
  const read = () => {
    try {
      return JSON.parse(sessionStorage.getItem(KEY) || "null");
    } catch {
      return null;
    }
  };
  const save = (state) => {
    try {
      sessionStorage.setItem(KEY, JSON.stringify(state));
    } catch {
      // Los enlaces siguen funcionando si el navegador bloquea sessionStorage.
    }
  };
  const originOf = (state) => state?.origin ?? "busqueda";
  const anchorOf = (state) =>
    originOf(state) === "escrito" ? "#" + state.field
      : (originOf(state) === "inventario" ? "#inventario-insumo-" : "#insumo-") + state.productId;
  const here = () => location.pathname + location.search;

  /* Desde una barra de búsqueda (la del inicio o la de «Buscar producto»): se guarda la página y lo que se estaba
     escribiendo, para volver ahí con el texto puesto. Al elegir un producto de la lista que aparece al escribir, o al
     buscar un código con Enter (la búsqueda lleva directo a la ficha). */
  const saveTyped = (input, productId) => {
    if (!input?.id) return;
    save({
      origin: "escrito",
      productId,
      listUrl: here(),
      scrollY: window.scrollY,
      field: input.id,
      text: input.value,
      at: Date.now(),
    });
  };

  document.addEventListener("click", (event) => {
    if (!(event.target instanceof Element)) return;
    const link = event.target.closest("a.sugerencia[href]");
    const id = link?.getAttribute("href").match(/\/insumos\/(\d+)(?:$|[?#])/)?.[1];
    if (!id) return;
    saveTyped(link.closest("form, .sugerencias")?.querySelector("input[data-sugerencias]"), id);
  });

  document.addEventListener("submit", (event) => {
    const input = event.target instanceof HTMLFormElement ? event.target.querySelector("input[data-sugerencias]") : null;
    if (input && input.value.trim() !== "") saveTyped(input, null);
  });

  const searchResults = document.querySelector(".resultados");
  if (searchResults) {
    searchResults.addEventListener("click", (event) => {
      if (!(event.target instanceof Element)) return;
      const link = event.target.closest("a.resultado");
      if (!link || !searchResults.contains(link)) return;

      const id = link.closest("li")?.id.replace(/^insumo-/, "");
      if (!id) return;

      save({
        origin: "busqueda",
        productId: id,
        listUrl: location.pathname + location.search,
        scrollY: window.scrollY,
      });
    });
  }

  const inventory = document.querySelector(".inventario__tabla");
  if (inventory) {
    inventory.addEventListener("click", (event) => {
      if (!(event.target instanceof Element)) return;
      const link = event.target.closest("a.inventario__nombre");
      if (!link || !inventory.contains(link)) return;

      const id = link.closest("tr")?.id.replace(/^inventario-insumo-/, "");
      if (!id) return;

      save({
        origin: "inventario",
        productId: id,
        listUrl: location.pathname + location.search,
        scrollY: window.scrollY,
        tableScrollLeft: link.closest(".table-responsive")?.scrollLeft ?? 0,
      });
    });
  }

  if (searchResults || inventory || document.querySelector("input[data-sugerencias]")) {
    const restore = () => {
      const state = read();
      if (!state || state.listUrl !== location.pathname + location.search ||
          location.hash !== anchorOf(state) || !Number.isFinite(state.scrollY)) return;

      requestAnimationFrame(() => {
        if (originOf(state) === "escrito") {
          // El texto vuelve a la barra, con el cursor al final y la lista de productos abierta otra vez.
          const input = document.getElementById(state.field);
          history.replaceState(history.state, "", here());
          if (input) {
            input.value = state.text ?? "";
            input.focus({ preventScroll: true });
            input.setSelectionRange(input.value.length, input.value.length);
            input.dispatchEvent(new Event("input", { bubbles: true }));
          }
          window.scrollTo(0, state.scrollY);
          return;
        }
        if (originOf(state) === "inventario" && Number.isFinite(state.tableScrollLeft)) {
          document.querySelector(".inventario__tabla")?.closest(".table-responsive")?.scrollTo({
            left: state.tableScrollLeft,
          });
        }
        window.scrollTo(0, state.scrollY);
      });
    };

    window.addEventListener("pageshow", restore);
    restore();
  }

  const product = document.querySelector("[data-insumo-id]");
  if (!product) return;

  const state = read();
  if (!state?.listUrl) return;
  // Buscado con Enter: la búsqueda llevó directo a esta ficha, así que el producto aún no estaba anotado.
  if (originOf(state) === "escrito" && state.productId === null && Date.now() - (state.at ?? 0) < 30000) {
    state.productId = product.dataset.insumoId;
    save(state);
  }
  if (state.productId !== product.dataset.insumoId) return;

  const back = document.querySelector(".encabezado .back-link");
  if (!back) return;

  back.href = state.listUrl + anchorOf(state);
  // El botón dice adónde vuelve: al inicio, al inventario o a «Buscar producto».
  const path = state.listUrl.split(/[?#]/)[0];
  const texto = originOf(state) === "inventario" ? "Volver al inventario"
    : path.includes("/insumos") ? "Volver a Buscar producto" : "Volver al inicio";
  const label = [...back.childNodes].find((node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim());
  if (label) label.textContent = texto;
})();
