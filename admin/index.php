<?php
// RutaNexo — Panel del Super Administrador: resumen
require __DIR__ . '/_panel.php';

$n = fn(string $sql) => (int) $pdo->query($sql)->fetchColumn();
$datos = [
    ['solicitudes.php', 'fa-clipboard-check', $n('SELECT COUNT(*) FROM suscripcion WHERE id_estados = 10'), 'Solicitudes por revisar'],
    ['rutas.php',       'fa-route',           $n('SELECT COUNT(*) FROM ruta'),                              'Rutas'],
    ['rutas.php',       'fa-location-dot',    $n('SELECT COUNT(*) FROM paradero'),                          'Paraderos'],
    ['empresas.php',    'fa-building',        $n('SELECT COUNT(*) FROM empresa'),                           'Empresas'],
    ['planes.php',      'fa-tags',            $n('SELECT COUNT(*) FROM plan_suscripcion'),                  'Planes'],
    ['usuarios.php',    'fa-users',           $n('SELECT COUNT(*) FROM usuario'),                           'Usuarios'],
];

panel_abrir('resumen', 'Panel de administración', 'Gestiona las rutas, las empresas, los planes y los usuarios de RutaNexo.');
?>
<div class="pn-cards">
    <?php foreach ($datos as [$url, $icono, $total, $texto]): ?>
        <a class="pn-card-n" href="<?= $url ?>"><i class="fa-solid <?= $icono ?>"></i><b><?= $total ?></b><span><?= e($texto) ?></span></a>
    <?php endforeach; ?>
</div>

<div class="pn-caja">
    <h2><i class="fa-solid fa-user-shield"></i> Sesión</h2>
    <dl class="pn-dato">
        <div><dt>Super Administrador</dt><dd><?= e($u['nombre']) ?></dd></div>
        <div><dt>Correo</dt><dd><?= e($u['correo']) ?></dd></div>
    </dl>
    <form method="post" action="../includes/logout.php" class="pn-pie-form">
        <?= campo_csrf() ?>
        <button type="submit" class="pn-btn"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</button>
    </form>
</div>
<?php panel_cerrar();
