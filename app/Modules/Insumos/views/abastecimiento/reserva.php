<?php

defined('APP_ROOT') || exit;

// Etapa 1, su propia ventana («¿Cuál es el número de reserva?»): el número que viene en el correo de SAP («Se creó la
// Reserva 0000018857»), con o sin los ceros. Si se acaba de ingresar una, arriba va su boleta (la reserva, cada producto
// con lo que llegó y el stock) y el campo queda listo para la siguiente.
$filas = [];
if ($ingresada !== null) {
    $unidades = array_sum(array_column($ingresada['productos'], 'cantidad'));
    $cuantos = count($ingresada['productos']);
    $filas[] = ['clipboard', 'Reserva <strong>' . e($ingresada['reserva']) . '</strong> · ' . e($cuantos === 1 ? '1 producto' : "{$cuantos} productos")
        . ', ' . e(unidades($unidades))];
    foreach ($ingresada['productos'] as $producto) {
        $filas[] = ['plus', '<strong>' . e($producto['nombre']) . '</strong> · llegaron ' . e(cantidad($producto['cantidad']))
            . ' · stock: ' . e(cantidad($producto['stock']))];
    }
}
?>
<?php if ($ingresada !== null) : ?>
    <?= partial('boleta', ['titulo' => '¡Listo! Quedó ingresada', 'filas' => $filas]) ?>
<?php endif; ?>

<form class="formulario" method="post" action="<?= e(url('abastecimiento')) ?>" data-submit-once>
    <?= csrf_field() ?>

    <div class="escaneo-foco<?= error_de('reserva') !== null ? ' has-error' : '' ?>">
        <span class="escaneo-foco__icono" aria-hidden="true"><?= icon('clipboard') ?></span>
        <label class="escaneo-foco__pregunta" for="reserva">N° de reserva</label>
        <input class="escaneo-foco__campo" id="reserva" name="reserva" type="text" inputmode="numeric" maxlength="40" autocomplete="off"
            placeholder="Ejemplo: 0000018857" aria-describedby="reserva-ayuda" autofocus data-lectura
            value="<?= e(old('reserva', $ingresada !== null ? '' : $reserva)) ?>" <?= aria_error('reserva') ?>>
        <?= mensaje_error('reserva') ?>
        <p class="escaneo-foco__ayuda" id="reserva-ayuda">Está en el correo: «Se creó la Reserva 0000018857». Sirve sin los ceros.</p>
    </div>

    <div class="formulario__acciones">
        <a class="btn btn--secondary" href="<?= e(url()) ?>"><?= icon($ingresada !== null ? 'home' : 'close') ?><?= $ingresada !== null ? 'Finalizar' : 'Cancelar' ?></a>
        <button class="btn btn--si" type="submit" data-loading-text="Un momento…">Siguiente<?= icon('arrow-right') ?></button>
    </div>
</form>
