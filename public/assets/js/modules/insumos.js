/* ==========================================================
   Barras para buscar productos: mientras se escribe aparece,
   bajo la barra, la lista de los productos que coinciden (la
   arma el servidor). Tocar uno lo elige.
   <div class="sugerencias"> … <input data-sugerencias="URL"> … </div>
   La lista se pone al final del .sugerencias. Con las flechas
   se recorre; Enter elige el marcado o, si no hay ninguno,
   envía el formulario como siempre (así funciona la pistola,
   que escribe el código y manda Enter). Esc la cierra.
   Sin JavaScript, la barra igual busca al enviar.
   ========================================================== */
import { request } from "../core/api.js";
import { onContentReady } from "../core/ui.js";

(() => {
  "use strict";

  const ESPERA = 150; // ms sin escribir antes de pedir la lista
  const ALTO_MINIMO = 240; // px: con menos espacio abajo, la pantalla se mueve para mostrar la lista

  const normalizar = (texto) => texto.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase();
  const palabras = (texto) => normalizar(texto).split(/\s+/).filter(Boolean);

  // Marca en el texto del elemento las palabras buscadas, sin importar mayúsculas ni tildes ("jabon" marca "Jabón").
  const resaltar = (elemento, buscadas) => {
    const texto = elemento.textContent;
    let plano = "";
    const origen = []; // posición en `texto` de cada letra de `plano`
    for (let i = 0; i < texto.length; i++) {
      const letra = normalizar(texto[i]);
      for (let j = 0; j < letra.length; j++) {
        plano += letra[j];
        origen.push(i);
      }
    }

    const tramos = [];
    buscadas.forEach((palabra) => {
      for (let desde = plano.indexOf(palabra); desde !== -1; desde = plano.indexOf(palabra, desde + 1)) {
        tramos.push([origen[desde], origen[desde + palabra.length - 1] + 1]);
      }
    });
    if (tramos.length === 0) return;

    tramos.sort((a, b) => a[0] - b[0]);
    const partes = [];
    let hasta = 0;
    tramos.forEach(([inicio, fin]) => {
      if (fin <= hasta) return;
      inicio = Math.max(inicio, hasta);
      if (inicio > hasta) partes.push(document.createTextNode(texto.slice(hasta, inicio)));
      const marca = document.createElement("mark");
      marca.textContent = texto.slice(inicio, fin);
      partes.push(marca);
      hasta = fin;
    });
    if (hasta < texto.length) partes.push(document.createTextNode(texto.slice(hasta)));
    elemento.replaceChildren(...partes);
  };

  const iniciar = (input, numero) => {
    const ancla = input.closest(".sugerencias") ?? input.parentElement;
    const panel = document.createElement("div");
    panel.className = "sugerencias__panel";
    panel.id = `sugerencias-${numero}`;
    panel.hidden = true;
    const aviso = document.createElement("p");
    aviso.className = "visually-hidden";
    aviso.setAttribute("role", "status");
    ancla.append(panel, aviso);

    input.setAttribute("role", "combobox");
    input.setAttribute("aria-autocomplete", "list");
    input.setAttribute("aria-expanded", "false");
    input.setAttribute("aria-controls", panel.id);

    let pedido = null; // AbortController de la última petición
    let reloj = 0;
    let mostrado = null; // el texto de la lista que tiene el panel
    let activa = -1;

    const opciones = () => [...panel.querySelectorAll("[role=option]")];

    const marcar = (indice) => {
      const lista = opciones();
      activa = indice;
      lista.forEach((opcion, i) => opcion.setAttribute("aria-selected", String(i === indice)));
      if (indice >= 0) {
        input.setAttribute("aria-activedescendant", lista[indice].id);
        lista[indice].scrollIntoView({ block: "nearest" });
      } else {
        input.removeAttribute("aria-activedescendant");
      }
    };

    // La lista llega hasta el borde de abajo de la pantalla (o del teclado, en el teléfono); lo que no quepa se ve
    // bajando dentro de ella. Si queda muy poco espacio, la pantalla se mueve para mostrarla.
    const ajustarAlto = () => {
      if (panel.hidden) return;
      const alto = window.visualViewport?.height ?? window.innerHeight;
      const libre = alto - panel.getBoundingClientRect().top - 12;
      panel.style.setProperty("--alto-libre", `${Math.max(libre, ALTO_MINIMO)}px`);
      if (libre < ALTO_MINIMO) panel.scrollIntoView({ block: "nearest" });
    };

    const abrir = () => {
      if (mostrado === null) return;
      panel.hidden = false;
      input.setAttribute("aria-expanded", "true");
      ajustarAlto();
    };

    const cerrar = () => {
      panel.hidden = true;
      input.setAttribute("aria-expanded", "false");
      marcar(-1);
    };

    const vaciar = () => {
      pedido?.abort();
      clearTimeout(reloj);
      mostrado = null;
      panel.replaceChildren();
      aviso.textContent = "";
      cerrar();
    };

    const pedir = async () => {
      const texto = input.value.trim();
      if (texto === "") {
        vaciar();
        return;
      }
      if (texto === mostrado) {
        if (document.activeElement === input) abrir();
        return;
      }

      pedido?.abort();
      pedido = new AbortController();
      const url = new URL(input.dataset.sugerencias, window.location.href);
      url.searchParams.set("q", texto);

      let html;
      try {
        ({ html } = await request(url, { signal: pedido.signal }));
      } catch (error) {
        // La sesión se cerró o no hay conexión: la barra sigue funcionando al enviar, y el sistema lleva al login.
        if (error.name !== "AbortError") vaciar();
        return;
      }

      // La lista se arma fuera de la página (resaltado e ids incluidos) y entra de una sola vez: un solo repintado.
      const nueva = document.createElement("template");
      nueva.innerHTML = html;
      const buscadas = palabras(texto);
      nueva.content.querySelectorAll("[data-resaltar]").forEach((elemento) => resaltar(elemento, buscadas));
      nueva.content.querySelectorAll("[role=option]").forEach((opcion, i) => {
        opcion.id = `${panel.id}-${i}`;
      });
      panel.replaceChildren(nueva.content);
      mostrado = texto;
      marcar(-1);
      aviso.textContent = panel.querySelector("[data-anuncio]")?.dataset.anuncio ?? "";
      if (document.activeElement === input) abrir();
    };

    input.addEventListener("input", () => {
      clearTimeout(reloj);
      if (input.value.trim() === "") {
        vaciar();
        return;
      }
      reloj = setTimeout(pedir, ESPERA);
    });

    // Al volver a la barra con lo mismo escrito, la lista reaparece.
    input.addEventListener("focus", () => {
      if (mostrado !== null && mostrado === input.value.trim()) abrir();
    });

    input.addEventListener("keydown", (event) => {
      const abierta = !panel.hidden;
      const total = opciones().length;

      if (event.key === "ArrowDown") {
        event.preventDefault();
        if (!abierta) {
          abrir();
          return;
        }
        if (total > 0) marcar(Math.min(activa + 1, total - 1));
      } else if (event.key === "ArrowUp" && abierta) {
        event.preventDefault();
        marcar(Math.max(activa - 1, -1));
      } else if (event.key === "Enter" && abierta && activa >= 0) {
        event.preventDefault();
        opciones()[activa].click();
      } else if (event.key === "Escape" && abierta) {
        // Solo cierra la lista: ni borra lo escrito ni cierra la ventana.
        event.preventDefault();
        cerrar();
      }
    });

    // Tocar la lista no le quita el foco a la barra (si no, se cerraría antes de recibir el clic).
    panel.addEventListener("mousedown", (event) => {
      if (event.target.closest("[role=option], button, a")) event.preventDefault();
    });

    ancla.addEventListener("focusout", (event) => {
      if (!ancla.contains(event.relatedTarget)) cerrar();
    });

    window.addEventListener("resize", ajustarAlto);
    window.visualViewport?.addEventListener("resize", ajustarAlto);

    // Al volver con «Atrás» del navegador, la página puede venir con la lista abierta.
    window.addEventListener("pageshow", (event) => {
      if (event.persisted) cerrar();
    });
  };

  let numero = 0;
  const iniciarEn = (root) => root.querySelectorAll("[data-sugerencias]").forEach((input) => iniciar(input, numero++));
  iniciarEn(document);
  // También en la ventana nueva cuando cambia la etapa sin recargar (ventana.js).
  onContentReady(iniciarEn);
})();
