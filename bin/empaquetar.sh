#!/usr/bin/env bash
# Arma el paquete para subir al servidor: los archivos de una versión (git archive) con el JavaScript y el CSS
# comprimidos (sin comentarios ni espacios, esbuild). En git el código sigue legible: solo se comprime el paquete.
#
#   bash bin/empaquetar.sh v1.1.0 /mnt/c/Users/David/Documents/despliegue-bodega-v1.1
#
# Deja <destino>/bodega-fcf-v1.1.0/ (la carpeta para subir con FileZilla) y <destino>/bodega-fcf-v1.1.0.zip.
# No incluye config/config.php (está fuera de git): el del servidor no se toca.

set -euo pipefail
cd "$(dirname "$0")/.."

version="${1:?Falta la versión, por ejemplo: v1.1.0}"
destino="${2:?Falta la carpeta de destino}"
ESBUILD=(npx --yes esbuild@0.24.0)

if ! git rev-parse -q --verify "${version}^{commit}" >/dev/null; then
    echo "✘ No existe la versión ${version} en git (¿hiciste el tag?)."
    exit 1
fi

nombre="bodega-fcf-${version}"
carpeta="${destino%/}/${nombre}"
mkdir -p "$destino"
rm -rf -- "$carpeta" "${carpeta}.zip"
mkdir -p "$carpeta"
git archive "$version" | tar -x -C "$carpeta"

assets="${carpeta}/public/assets"
mapfile -d '' js < <(find "$assets/js" -name '*.js' -not -path '*/vendor/*' -print0)
mapfile -d '' css < <(find "$assets/css" -name '*.css' -print0)

antes=$(cat "${js[@]}" "${css[@]}" | wc -c)

# Cada archivo por separado (sin juntar): las rutas de los import y de url() quedan iguales. Los scripts del sistema
# son módulos ES (script_modulo); el de vendor/ ya viene comprimido.
"${ESBUILD[@]}" "${js[@]}" --minify --format=esm --legal-comments=none --log-level=warning \
    --outdir="$assets/js" --outbase="$assets/js" --allow-overwrite
"${ESBUILD[@]}" "${css[@]}" --minify --legal-comments=none --log-level=warning \
    --outdir="$assets/css" --outbase="$assets/css" --allow-overwrite

despues=$(cat "${js[@]}" "${css[@]}" | wc -c)

(cd "$destino" && zip -qr "${nombre}.zip" "$nombre")

echo "✔ Paquete listo: ${carpeta}"
echo "  JavaScript y CSS: $((antes / 1024)) KB → $((despues / 1024)) KB (${#js[@]} scripts, ${#css[@]} hojas de estilo)"
