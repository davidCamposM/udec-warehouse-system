<?php

use App\Modules\Insumos\InsumoService;

defined('APP_ROOT') || exit;

// Recibe $insumo (fila con activo, stock_actual y stock_minimo).
$estado = InsumoService::estado($insumo);
[$texto, $icono] = InsumoService::ESTADOS[$estado];
?>
<span class="status status--<?= e($estado) ?>"><?= icon($icono) ?><?= e($texto) ?></span>
