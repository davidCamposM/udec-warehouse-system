#!/usr/bin/env bash
# Abre bin/cambiar-claves.php dentro de Docker (lo llama cambiar-claves.cmd desde Windows) y deja sus dos archivos (las
# contraseñas nuevas y el SQL para phpMyAdmin) en la carpeta de Windows indicada.
#
#   bash bin/cambiar-claves.sh 'C:\Users\...\carpeta' [base local]

set -u
cd "$(dirname "$0")/.." || exit 1

destino_windows="${1:-}"
base="${2:-bodega_fcf_v1}"

if ! docker info >/dev/null 2>&1; then
    echo '✘ Docker no está corriendo. Ábrelo, espera a que inicie y vuelve a intentar.'
    exit 1
fi
if [ -z "$(docker compose ps -q --status running app 2>/dev/null)" ]; then
    echo '✘ El sistema local no está corriendo. En Ubuntu: cd ~/projects/udec-warehouse && docker compose up -d'
    exit 1
fi

docker compose exec -u www-data app php bin/cambiar-claves.php --base="$base" --salida=storage/claves || exit 1

if compgen -G 'storage/claves/claves-*' >/dev/null; then
    if [ -n "$destino_windows" ]; then
        destino="$(wslpath -u "$destino_windows")"
        mkdir -p "$destino" && mv storage/claves/claves-* "$destino"/ \
            && echo && echo "Archivos guardados en: $destino_windows"
    else
        echo && echo "Archivos guardados en: $(pwd)/storage/claves"
    fi
fi
