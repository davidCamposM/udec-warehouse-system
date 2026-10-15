<?php

defined('APP_ROOT') || exit;

/**
 * «Listo», como una boleta: lo que se acaba de hacer, un dato por línea, cada uno con su ícono, para leerlo de un vistazo
 * y sin frases largas. Va arriba de la primera ventana de la tarea, que queda lista para el siguiente producto.
 *
 * - $titulo: «¡Listo! Quedó guardado».
 * - $filas: lista de [ícono, texto], en orden. El texto ya viene escapado (puede llevar <strong>).
 *   Una fila ['lugar', ['codigo' => 'M12', 'nombre' => 'Mueble de madera · Cubículo 12']] es dónde quedó: el código
 *   como la etiqueta pegada en el mueble (en un recuadro) y el nombre en palabras («Mueble de madera, cubículo 12»).
 */
$nombreLugar = static function (string $nombre): string {
    $partes = explode(' · ', $nombre);

    return implode(', ', array_map(
        static fn (string $parte, int $i): string => $i === 0 ? $parte : mb_strtolower(mb_substr($parte, 0, 1)) . mb_substr($parte, 1),
        $partes,
        array_keys($partes)
    ));
};
?>
<div class="boleta" role="status">
    <p class="boleta__titulo"><span class="boleta__check"><?= icon('ok') ?></span><?= e($titulo) ?></p>
    <ul class="boleta__filas">
        <?php foreach ($filas as [$icono, $texto]) : ?>
            <?php if ($icono === 'lugar') : ?>
                <li class="boleta__fila">
                    <span class="boleta__icono"><?= icon('map-pin') ?></span>
                    <span><span class="boleta__etiqueta"><?= e($texto['codigo']) ?></span> <?= e($nombreLugar($texto['nombre'])) ?></span>
                </li>
            <?php else : ?>
                <li class="boleta__fila"><span class="boleta__icono"><?= icon($icono) ?></span><span><?= $texto ?></span></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>
