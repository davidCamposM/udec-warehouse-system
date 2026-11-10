// Etiqueta de un producto: dibuja el código de barras (Code 128, que la pistola lee sin configurar nada) y abre la
// ventana de imprimir.
(() => {
  const svg = document.querySelector("svg[data-codigo]");
  if (svg && window.JsBarcode) {
    window.JsBarcode(svg, svg.dataset.codigo, { format: "CODE128", width: 2, height: 60, fontSize: 16, margin: 8 });
  }

  document.querySelector("[data-imprimir]")?.addEventListener("click", () => window.print());
  window.addEventListener("load", () => window.print());
})();
