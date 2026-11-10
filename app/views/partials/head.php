<?php

defined('APP_ROOT') || exit;

// Estilos comunes a todas las páginas, en orden. Cada layout agrega los suyos en $estilos.
// Van como archivos separados (sin @import) para que cada uno lleve su propia versión
// y el navegador nunca use una copia vieja después de una actualización.
$hojas = [
    'css/base/fonts.css',
    'css/base/tokens.css',
    'css/base/tema-azul.css', // el modelo nuevo (azul marino): rige solo donde <html> lleva la clase «tema-azul»
    'css/base/reset.css',
    'css/base/base.css',
    'css/components/brand.css',
    'css/components/button.css',
    'css/components/form.css',
    'css/components/alert.css',
    ...($estilos ?? []),
];
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="<?= e($temaColor ?? '#539452') ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e(isset($titulo) ? $titulo . ' · ' . config('app.name') : config('app.name')) ?></title>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<?php foreach ($hojas as $hoja) : ?>
<link rel="stylesheet" href="<?= e(asset($hoja)) ?>">
<?php endforeach; ?>
