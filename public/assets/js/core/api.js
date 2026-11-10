/* ==========================================================
   API: la única forma en que el JavaScript le pide datos al servidor.
   Todas las respuestas llegan con el mismo sobre (Response::ok / Response::error en PHP):
     éxito: {"ok": true,  "data": …}
     error: {"ok": false, "error": {"code": "…", "message": "…", …}}
   request() devuelve data o lanza ApiError; la red caída lanza el TypeError de fetch.
   ========================================================== */

const HEADERS = { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" };
const HTTP_UNAUTHORIZED = 401;

/** Un error que respondió el servidor: su estado HTTP, su código estable y sus detalles (como la dirección del login). */
export class ApiError extends Error {
  constructor(message, { status, code, details = {} }) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.code = code;
    this.details = details;
  }

  /** La sesión se cerró (inactividad, cuenta desactivada). */
  get isUnauthenticated() {
    return this.status === HTTP_UNAUTHORIZED;
  }
}

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";

/**
 * Pide algo al servidor y devuelve lo que trae en data.
 * - method: "GET" por defecto; los demás llevan el token CSRF.
 * - background: la petición no cuenta como actividad de la persona (la sesión igual vence por inactividad).
 * - signal: para cancelarla (AbortController), como al seguir escribiendo en una búsqueda.
 */
export async function request(url, { method = "GET", background = false, signal } = {}) {
  const headers = { ...HEADERS };
  if (method !== "GET") headers["X-CSRF-Token"] = csrfToken();
  if (background) headers["X-Bodega-Background"] = "1";

  const response = await fetch(url, { method, headers, signal, credentials: "same-origin", cache: "no-store" });
  const body = await response.json().catch(() => null);

  if (response.ok && body?.ok === true) return body.data;

  const { code = "http_error", message = `HTTP ${response.status}`, ...details } = body?.error ?? {};
  throw new ApiError(message, { status: response.status, code, details });
}

/** Sin sesión: lleva al login, donde se ve el aviso que dejó el servidor. */
export function goToLogin(error) {
  window.location.assign(error.details.login ?? window.location.href);
}
