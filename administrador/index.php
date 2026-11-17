<?php
// RutaNexo — Panel del Administrador: resumen de la empresa
require __DIR__ . '/_panel.php';

$cuenta = function (string $extra = '') use ($pdo, $id_empresa): int {
    $q = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE id_empresa = ?' . $extra);
    $q->execute([$id_empresa]);
    return (int) $q->fetchColumn();
};
$por_rol = $pdo->prepare('SELECT r.id_rol, r.nombre_rol, COUNT(u.documento) AS n
                            FROM rol r LEFT JOIN usuario u ON u.id_rol = r.id_rol AND u.id_empresa = ?
                           WHERE r.id_rol <> 6 GROUP BY r.id_rol, r.nombre_rol ORDER BY r.id_rol');
$por_rol->execute([$id_empresa]);
$por_rol = $por_rol->fetchAll();

$plan = $pdo->prepare('SELECT p.nombre, s.fecha_fin FROM suscripcion s JOIN plan_suscripcion p ON p.id_plan = s.id_plan
                        WHERE s.id_empresa = ? AND s.id_estados = 4 ORDER BY s.id_suscripcion DESC LIMIT 1');
$plan->execute([$id_empresa]);
$plan = $plan->fetch();

panel_abrir('resumen', 'Panel del administrador', 'Gestiona los usuarios de tu empresa y los roles que tiene cada uno.');
?>
<div class="pn-cards">
    <a class="pn-card-n" href="usuarios.php"><i class="fa-solid fa-users"></i><b><?= $cuenta() ?></b><span>Usuarios de la empresa</span></a>
    <a class="pn-card-n" href="usuarios.php?estado=1"><i class="fa-solid fa-user-check"></i><b><?= $cuenta(' AND id_estados = 1') ?></b><span>Habilitados</span></a>
    <a class="pn-card-n" href="usuarios.php?estado=2"><i class="fa-solid fa-user-slash"></i><b><?= $cuenta(' AND id_estados = 2') ?></b><span>Deshabilitados</span></a>
</div>

<div class="pn-caja">
    <h2><i class="fa-solid fa-user-tag"></i> Usuarios por rol <a class="pn-btn" style="margin-left:auto" href="roles.php">Asignar roles</a></h2>
    <div class="pn-cards" style="margin-bottom:0">
        <?php foreach ($por_rol as $r): ?>
            <a class="pn-card-n" href="usuarios.php?rol=<?= (int) $r['id_rol'] ?>"><b><?= (int) $r['n'] ?></b><span><?= e($r['nombre_rol']) ?></span></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="pn-caja">
    <h2><i class="fa-solid fa-building"></i> Mi empresa</h2>
    <dl class="pn-dato">
        <div><dt>Razón social</dt><dd><?= e($yo['razon_social']) ?></dd></div>
        <div><dt>NIT</dt><dd><?= e($yo['nit']) ?></dd></div>
        <div><dt>Plan vigente</dt><dd><?= $plan ? e($plan['nombre']) . ($plan['fecha_fin'] ? ' · hasta ' . e(date('d/m/Y', strtotime($plan['fecha_fin']))) : '') : '—' ?></dd></div>
        <div><dt>Administrador</dt><dd><?= e($yo['nombre'] . ' ' . $yo['apellido']) ?> · <?= e($yo['correo']) ?></dd></div>
    </dl>
    <form method="post" action="../includes/logout.php" class="pn-pie-form">
        <?= campo_csrf() ?>
        <button type="submit" class="pn-btn"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</button>
    </form>
</div>
<?php panel_cerrar();
