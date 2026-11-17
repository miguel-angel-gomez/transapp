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
header('Cache-Control: no-store, no-cache, must-revalidate');

$u = $_SESSION['usuario'] ?? null;
if (!$u) { header('Location: ../superadmin.php'); exit; }
if (($u['origen'] ?? '') !== 'super_admin') { http_response_code(403); exit('Solo el Super Administrador puede entrar aquí.'); }

$pdo = (new Database())->conectar();
if (!$pdo) { http_response_code(503); exit('No hay conexión con la base de datos.'); }

require_once __DIR__ . '/../includes/panel_helpers.php';

$secciones = [
    'resumen'     => ['index.php',       'fa-gauge-high',       'Resumen'],
    'solicitudes' => ['solicitudes.php', 'fa-clipboard-check',  'Solicitudes'],
    'rutas'       => ['rutas.php',       'fa-route',            'Rutas y paraderos'],
    'empresas'    => ['empresas.php',    'fa-building',         'Empresas'],
    'planes'      => ['planes.php',      'fa-tags',             'Planes'],
    'usuarios'    => ['usuarios.php',    'fa-users',            'Usuarios'],
];

/** Abre la página: <head>, navbar, título y menú del panel. */
function panel_abrir(string $activa, string $titulo, string $intro = ''): void
{
    global $secciones, $raiz, $titulo_pagina, $descripcion_pagina, $css_extra, $pagina_activa;
    $raiz = '../';
    $titulo_pagina = $titulo;
    $descripcion_pagina = $intro ?: 'Panel del Super Administrador de RutaNexo.';
    $css_extra = ['css/style_panel.css'];
    $pagina_activa = '';
    include __DIR__ . '/../includes/head_publico.php';
    include __DIR__ . '/../includes/navbar.php';
    ?>
<main>
    <section class="pg-cab">
        <div class="contenedor">
            <span class="etiqueta">Super Administrador</span>
            <h1><?= e($titulo) ?></h1>
            <?php if ($intro): ?><p class="intro"><?= e($intro) ?></p><?php endif; ?>
        </div>
    </section>
    <section class="pn-pg">
        <div class="contenedor">
            <nav class="pn-menu" aria-label="Secciones del panel">
                <?php foreach ($secciones as $clave => [$url, $icono, $texto]): ?>
                    <a href="<?= $url ?>" class="<?= $clave === $activa ? 'activo' : '' ?>"><i class="fa-solid <?= $icono ?>"></i> <?= e($texto) ?></a>
                <?php endforeach; ?>
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
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="../js/landing.js"></script>
</body>

</html>
<?php
}
