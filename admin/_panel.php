<?php
// ============================================================
// RutaNexo — Panel del Super Administrador (base compartida)
//   Valida la sesión, ofrece ayudas (flash, CSRF, redirección)
//   y dibuja la cabecera y el menú del panel.
//   Cada página del panel la incluye con:  require __DIR__ . '/_panel.php';
// ============================================================
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';

iniciar_sesion();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$u = $_SESSION['usuario'] ?? null;
if (!$u || ($u['origen'] ?? '') !== 'super_admin') {
    header('Location: ../superadmin.php');
    exit;
}

$pdo = (new Database())->conectar();
if (!$pdo) { http_response_code(503); exit('No hay conexión con la base de datos.'); }

require_once __DIR__ . '/../includes/panel_helpers.php';

$secciones = [
    'resumen'     => ['index.php',       'fa-house',            'Inicio'],
    'empresas'    => ['empresas.php',    'fa-building',         'Empresas'],
    'solicitudes' => ['solicitudes.php', 'fa-clipboard-check',  'Solicitudes'],
    'planes'      => ['planes.php',      'fa-tags',             'Planes'],
    'usuarios'    => ['usuarios.php',    'fa-users',            'Usuarios'],
];

/** Abre la página: <head>, navbar, título y menú del panel. */
function panel_abrir(string $activa, string $titulo, string $intro = ''): void
{
    global $secciones;
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> — Super Administración | RutaNexo</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="../img/logo-pin.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/style_panel.css">
    <?php foreach (($GLOBALS['css_extra'] ?? []) as $hoja): ?>
    <link rel="stylesheet" href="../<?= e($hoja) ?>">
    <?php endforeach; ?>
    <style>
      body{margin:0;background:#f3f7f8;color:#173247;font-family:Inter, sans-serif}
      .panel-top{background:#102d43;color:#fff;padding:15px 4%;display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap}
      .panel-brand{display:flex;align-items:center;gap:12px;color:#fff;text-decoration:none;font:800 1.15rem Outfit,sans-serif}
      .panel-brand img{width:42px;height:42px;object-fit:contain;background:#fff;border-radius:12px;padding:4px}
      .panel-top small{display:block;color:#b9d0dc;font:500 .75rem Inter,sans-serif}
      .panel-user{display:flex;align-items:center;gap:14px;color:#dce8ee;font-size:.88rem}
      .panel-logout{border:0;background:transparent;color:inherit;font:inherit;font-weight:600;cursor:pointer;padding:0}
      .pn-menu form{display:inline-flex;margin:0}
      .pn-menu .pn-salir{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border:1px solid #ead5d5;border-radius:999px;background:#fff;color:#a33b3b;font:600 .9rem Inter,sans-serif}
      .pn-menu .pn-salir:hover{background:#fff5f5;border-color:#c77}
      .panel-heading{background:linear-gradient(115deg,#153c55,#176c70);color:#fff;padding:30px 4%}
      .panel-heading .eyebrow{font-size:.76rem;text-transform:uppercase;letter-spacing:.12em;color:#bce9df;font-weight:700}
      .panel-heading h1{font:800 clamp(1.6rem,3vw,2.2rem) Outfit,sans-serif;margin:7px 0}
      .panel-heading p{margin:0;color:#d9e9ed;max-width:760px}
      .pn-pg{padding:26px 4% 70px;background:#f3f7f8;min-height:60vh}
      .pn-pg>.contenedor{max-width:1400px;margin:auto}
      .pn-menu{padding:12px;background:#fff;border:1px solid #e0e9ed;border-radius:14px;box-shadow:0 5px 20px #1732470b}
      .panel-footer{text-align:center;padding:18px;color:#718391;font-size:.8rem;background:#fff;border-top:1px solid #e3eaed}
      @media(max-width:600px){.panel-top{padding:12px 4%}.panel-user{width:100%;justify-content:space-between}.panel-heading{padding:24px 5%}}
    </style>
</head>
<body>
<header class="panel-top">
    <a class="panel-brand" href="index.php"><img src="../img/logo.png" alt=""><span>RutaNexo<small>Centro de control · Super Administrador</small></span></a>
    <div class="panel-user"><span><i class="fa-solid fa-user-shield"></i> <?= e($u['nombre'] ?? 'Super Administrador') ?></span></div>
</header>
<main>
    <section class="panel-heading"><span class="eyebrow">Super Administrador</span><h1><?= e($titulo) ?></h1><?php if ($intro): ?><p><?= e($intro) ?></p><?php endif; ?></section>
    <section class="pn-pg">
        <div class="contenedor">
            <nav class="pn-menu" aria-label="Secciones del panel">
                <?php foreach ($secciones as $clave => [$url, $icono, $texto]): ?>
                    <a href="<?= $url ?>" class="<?= $clave === $activa ? 'activo' : '' ?>"><i class="fa-solid <?= $icono ?>"></i> <?= e($texto) ?></a>
                <?php endforeach; ?>
                <form method="post" action="../includes/logout.php">
                    <?= campo_csrf() ?>
                    <button type="submit" class="pn-salir panel-logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</button>
                </form>
            </nav>
            <?php if (!empty($_SESSION['flash_panel'])): $f = $_SESSION['flash_panel']; unset($_SESSION['flash_panel']); ?>
                <div class="pn-alerta <?= $f['tipo'] === 'ok' ? 'ok' : 'mal' ?>" role="<?= $f['tipo'] === 'ok' ? 'status' : 'alert' ?>">
                    <i class="fa-solid <?= $f['tipo'] === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i> <?= e($f['texto']) ?>
                </div>
            <?php endif; ?>
<?php
}

/** Cierra la página. */
function panel_cerrar(): void
{
    global $raiz;
    ?>
        </div>
    </section>
</main>
<footer class="panel-footer">RutaNexo · Sistema de administración independiente</footer>
</body>

</html>
<?php
}
