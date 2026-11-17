<?php
// ============================================================
// Panel de prueba (se reemplaza en las siguientes partes del proyecto)
// Solo entra quien haya iniciado sesión.
// ============================================================
require_once 'config/conexion.php';
require_once 'includes/funciones.php';
require_once 'config/enlaces.php';
require_once 'includes/compras.php';

iniciar_sesion();

if (empty($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Evita que el botón "atrás" muestre esta página después de cerrar sesión
header('Cache-Control: no-store, no-cache, must-revalidate');

$u = $_SESSION['usuario'];

// Nombre del rol desde la tabla rol
$nombre_rol = 'Usuario';
if ($pdo = (new Database())->conectar()) {
    $stmt = $pdo->prepare('SELECT nombre_rol FROM rol WHERE id_rol = ?');
    $stmt->execute([$u['id_rol']]);
    $nombre_rol = $stmt->fetchColumn() ?: $nombre_rol;
}

// Compra de plan (usuarios) o solicitudes pendientes (Super Administrador)
$compra = null; $pendientes = 0;
if ($pdo ?? null) {
    if (($u['origen'] ?? '') === 'super_admin') {
        $pendientes = (int) $pdo->query('SELECT COUNT(*) FROM suscripcion WHERE id_estados = ' . PAGO_ESTADO_ESPERA)->fetchColumn();
    } else {
        $compra = compra_del_usuario($pdo, $u['documento']);
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RutaNexo — Mi panel</title>
    <link rel="icon" type="image/png" href="img/logo-pin.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/base.css?v=<?= filemtime(__DIR__ . '/css/base.css') ?>">
    <style>
        .dash { min-height: calc(100vh - 80px); display: grid; place-items: center; padding: 40px 0; }
        .dash-caja { background: #fff; border: 1px solid var(--linea); border-radius: 24px; box-shadow: var(--sombra); padding: 40px; max-width: 560px; text-align: center; }
        .dash-caja h1 { font-size: 2rem; color: var(--navy); margin-bottom: 8px; }
        .dash-caja p { color: var(--muted); margin: 6px 0; }
        .dash-rol { display: inline-block; margin: 14px 0 22px; padding: 6px 16px; border-radius: 999px; background: var(--teal-soft); color: var(--teal-d); font-weight: 600; }
        .dash-acciones { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    </style>
</head>

<body>
<?php include 'includes/navbar.php'; ?>

<main class="dash">
    <div class="dash-caja">
        <h1>¡Hola, <?= e($u['nombre']) ?>! 👋</h1>
        <p>Iniciaste sesión correctamente en RutaNexo.</p>
        <span class="dash-rol"><i class="fa-solid fa-user-shield"></i> <?= e($nombre_rol) ?></span>
        <p><?= e($u['correo']) ?> · Documento <?= e($u['documento']) ?></p>
        <?php if (($u['origen'] ?? '') === 'super_admin'): ?>
            <p><b><?= $pendientes ?></b> solicitud<?= $pendientes === 1 ? '' : 'es' ?> de plan por revisar.</p>
            <a href="admin/index.php" class="btn btn-teal" style="margin:8px 6px 6px 0"><i class="fa-solid fa-gauge-high"></i> Ir al panel</a>
            <a href="admin/solicitudes.php" class="btn btn-borde" style="margin:8px 0 6px"><i class="fa-solid fa-clipboard-check"></i> Ver solicitudes de planes</a>
        <?php elseif ((int) $u['id_rol'] === 1): ?>
            <a href="administrador/index.php" class="btn btn-teal" style="margin:8px 0 6px"><i class="fa-solid fa-gauge-high"></i> Ir al panel del administrador</a>
            <?php if ($compra): ?><p>Plan <b><?= e($compra['plan']) ?></b> · <?= e($compra['razon_social']) ?></p><?php endif; ?>
        <?php elseif ($compra): ?>
            <p>Plan <b><?= e($compra['plan']) ?></b> · <?= e($compra['razon_social']) ?></p>
            <a href="mi_compra.php?id=<?= (int) $compra['id_suscripcion'] ?>" class="btn btn-teal" style="margin:8px 0 6px"><i class="fa-solid fa-receipt"></i> Ver mi compra</a>
        <?php else: ?>
            <a href="<?= e(url_landing('planes')) ?>" class="btn btn-teal" style="margin:8px 0 6px"><i class="fa-solid fa-bag-shopping"></i> Ver planes y comprar</a>
        <?php endif; ?>
        <p><small>Esta es una página temporal: aquí irán los paneles de cada rol en las siguientes partes.</small></p>
        <div class="dash-acciones" style="margin-top:22px">
            <a href="index.php" class="btn btn-borde">Ir al inicio</a>
            <form method="post" action="includes/logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="btn btn-teal"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</button>
            </form>
        </div>
    </div>
</main>
<?php include __DIR__ . '/includes/cookies.php'; ?>
</body>

</html>
