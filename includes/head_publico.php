<?php
$raiz = $raiz ?? '';   // prefijo de rutas ('../' si la página está en una subcarpeta)
// Cabecera común de las páginas públicas internas (Rutas, Conócenos).
// Antes de incluirla define: $titulo_pagina y $descripcion_pagina.
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo_pagina) ?> — RutaNexo</title>
    <meta name="description" content="<?= e($descripcion_pagina) ?>">
    <link rel="icon" type="image/png" href="<?= $raiz ?>img/logo-pin.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="<?= $raiz ?>css/base.css?v=<?= filemtime(__DIR__ . '/../css/base.css') ?>">
    <link rel="stylesheet" href="<?= $raiz ?>css/style_paginas.css?v=<?= filemtime(__DIR__ . '/../css/style_paginas.css') ?>">
    <?php foreach ((array) ($css_extra ?? []) as $css): ?>
    <link rel="stylesheet" href="<?= $raiz . $css ?>?v=<?= filemtime(__DIR__ . '/../' . $css) ?>">
    <?php endforeach; ?>
</head>

<body>
