<?php
// ============================================================
// RutaNexo — Super Administrador: empresas
//   Ver las empresas registradas y habilitarlas o deshabilitarlas.
// ============================================================
require __DIR__ . '/_panel.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id_empresa'] ?? 0);
    $accion = (string) ($_POST['accion'] ?? '');
    if (!post_valido() || !in_array($accion, ['habilitar', 'deshabilitar'], true)) {
        flash('mal', 'La sesión expiró. Vuelve a intentarlo.');
    } else {
        $nuevo = $accion === 'habilitar' ? ESTADO_HABILITADO : ESTADO_DESHABILITADO;
        $st = $pdo->prepare('UPDATE empresa SET id_estados = ? WHERE id_empresa = ?');
        $st->execute([$nuevo, $id]);
        flash('ok', $accion === 'habilitar' ? 'Empresa habilitada.' : 'Empresa deshabilitada.');
    }
    redirigir('empresas.php' . ($id && !empty($_POST['volver_detalle']) ? '?id=' . $id : ''));
}

$estados = $pdo->query('SELECT id_estados, nombre FROM estado_registro ORDER BY nombre')->fetchAll(PDO::FETCH_KEY_PAIR);

$sel = "SELECT e.*, c.nombre AS ciudad, d.nombre AS departamento, s.nombre AS estado
          FROM empresa e
          LEFT JOIN ciudad c ON c.id_ciudad = e.id_ciudad
          LEFT JOIN departamento d ON d.id_departamento = c.id_departamento
          JOIN estado_registro s ON s.id_estados = e.id_estados";

// ---------------------------------------------------------------- Detalle
if (!empty($_GET['id'])) {
    $q = $pdo->prepare($sel . ' WHERE e.id_empresa = ?');
    $q->execute([(int) $_GET['id']]);
    $em = $q->fetch();
    if (!$em) { flash('mal', 'La empresa no existe.'); redirigir('empresas.php'); }

    $subs = $pdo->prepare('SELECT s.id_suscripcion, s.fecha_inicio, s.fecha_fin, s.valor_pagado, p.nombre AS plan, r.nombre AS estado, s.id_estados
                             FROM suscripcion s JOIN plan_suscripcion p ON p.id_plan = s.id_plan JOIN estado_registro r ON r.id_estados = s.id_estados
                            WHERE s.id_empresa = ? ORDER BY s.id_suscripcion DESC');
    $subs->execute([$em['id_empresa']]);
    $subs = $subs->fetchAll();
    $cuantos = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE id_empresa = ?');
    $cuantos->execute([$em['id_empresa']]);
    $cuantos = (int) $cuantos->fetchColumn();
    $habilitada = (int) $em['id_estados'] === ESTADO_HABILITADO;

    panel_abrir('empresas', $em['razon_social'], 'Datos de la empresa y sus suscripciones.');
    ?>
    <div class="pn-caja">
        <h2><i class="fa-solid fa-building"></i> Datos <?= etiqueta_estado((int) $em['id_estados'], $em['estado']) ?>
            <form method="post" style="margin-left:auto" onsubmit="return confirm('<?= $habilitada ? '¿Deshabilitar esta empresa?' : '¿Habilitar esta empresa?' ?>')">
                <?= campo_csrf() ?><input type="hidden" name="id_empresa" value="<?= (int) $em['id_empresa'] ?>"><input type="hidden" name="volver_detalle" value="1">
                <input type="hidden" name="accion" value="<?= $habilitada ? 'deshabilitar' : 'habilitar' ?>">
                <button class="pn-btn <?= $habilitada ? 'peligro' : 'principal' ?>" type="submit">
                    <i class="fa-solid <?= $habilitada ? 'fa-ban' : 'fa-circle-check' ?>"></i> <?= $habilitada ? 'Deshabilitar' : 'Habilitar' ?>
                </button>
            </form>
        </h2>
        <dl class="pn-dato">
            <div><dt>NIT</dt><dd><?= e($em['nit']) ?></dd></div>
            <div><dt>Representante</dt><dd><?= e($em['nombre_representante']) ?> · <?= e($em['documento_representante']) ?></dd></div>
            <div><dt>Correo</dt><dd><?= e($em['correo']) ?></dd></div>
            <div><dt>Teléfono</dt><dd><?= e($em['telefono'] ?: '—') ?></dd></div>
            <div><dt>Dirección</dt><dd><?= e($em['direccion'] ?: '—') ?></dd></div>
            <div><dt>Ciudad</dt><dd><?= e($em['ciudad'] ? $em['ciudad'] . ', ' . $em['departamento'] : '—') ?></dd></div>
            <div><dt>Registrada</dt><dd><?= e(date('d/m/Y H:i', strtotime($em['fecha_registro']))) ?></dd></div>
            <div><dt>Usuarios de la empresa</dt><dd><?= $cuantos ?></dd></div>
        </dl>
    </div>
    <div class="pn-caja">
        <h2><i class="fa-solid fa-file-invoice"></i> Suscripciones</h2>
        <?php if (!$subs): ?>
            <p class="pn-vacio">Esta empresa no tiene suscripciones.</p>
        <?php else: ?>
        <div class="pn-tabla-wrap"><table class="pn-tabla">
            <thead><tr><th>#</th><th>Plan</th><th>Inicio</th><th>Fin</th><th>Valor</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($subs as $s): ?>
                <tr>
                    <td><?= (int) $s['id_suscripcion'] ?></td>
                    <td><b><?= e($s['plan']) ?></b></td>
                    <td><?= $s['fecha_inicio'] ? e(date('d/m/Y', strtotime($s['fecha_inicio']))) : '—' ?></td>
                    <td><?= $s['fecha_fin'] ? e(date('d/m/Y', strtotime($s['fecha_fin']))) : '—' ?></td>
                    <td><?= e('$' . number_format((float) $s['valor_pagado'], 0, ',', '.')) ?></td>
                    <td><?= etiqueta_estado((int) $s['id_estados'], $s['estado']) ?></td>
                    <td><a class="pn-btn" href="solicitudes.php?id=<?= (int) $s['id_suscripcion'] ?>">Ver solicitud</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
    <a class="pn-btn" href="empresas.php"><i class="fa-solid fa-arrow-left"></i> Volver a las empresas</a>
    <?php
    panel_cerrar();
    exit;
}

// ---------------------------------------------------------------- Lista
$q = trim((string) ($_GET['q'] ?? ''));
$fe = (int) ($_GET['estado'] ?? 0);
$sql = $sel . ' WHERE 1=1';
$par = [];
if ($q !== '') { $sql .= ' AND (e.razon_social LIKE ? OR e.nit LIKE ? OR e.correo LIKE ? OR e.nombre_representante LIKE ?)'; $par = array_fill(0, 4, '%' . $q . '%'); }
if ($fe) { $sql .= ' AND e.id_estados = ?'; $par[] = $fe; }
$lista = $pdo->prepare($sql . ' ORDER BY e.fecha_registro DESC, e.id_empresa DESC');
$lista->execute($par);
$lista = $lista->fetchAll();

panel_abrir('empresas', 'Empresas', 'Consulta las empresas registradas y habilítalas o deshabilítalas.');
?>
<div class="pn-caja">
    <div class="pn-barra">
        <form method="get" class="pn-busca">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por razón social, NIT, correo…" aria-label="Buscar empresas">
            <select name="estado" aria-label="Filtrar por estado">
                <option value="0">Todos los estados</option>
                <?php foreach ([ESTADO_HABILITADO, ESTADO_DESHABILITADO, 10, 13] as $idE): if (isset($estados[$idE])): ?>
                    <option value="<?= $idE ?>" <?= $fe === $idE ? 'selected' : '' ?>><?= e($estados[$idE]) ?></option>
                <?php endif; endforeach; ?>
            </select>
            <button class="pn-btn" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
            <?php if ($q !== '' || $fe): ?><a class="pn-btn" href="empresas.php">Limpiar</a><?php endif; ?>
        </form>
    </div>
    <?php if (!$lista): ?>
        <p class="pn-vacio">No hay empresas<?= ($q !== '' || $fe) ? ' con ese filtro' : ' registradas todavía' ?>.</p>
    <?php else: ?>
    <div class="pn-tabla-wrap"><table class="pn-tabla">
        <thead><tr><th>Empresa</th><th>NIT</th><th>Representante</th><th>Ciudad</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($lista as $em): $hab = (int) $em['id_estados'] === ESTADO_HABILITADO; ?>
            <tr>
                <td><b><?= e($em['razon_social']) ?></b><br><small><?= e($em['correo']) ?></small></td>
                <td><?= e($em['nit']) ?></td>
                <td><?= e($em['nombre_representante']) ?></td>
                <td><?= e($em['ciudad'] ?: '—') ?></td>
                <td><?= etiqueta_estado((int) $em['id_estados'], $em['estado']) ?></td>
                <td><div class="pn-acc">
                    <a class="pn-btn" href="empresas.php?id=<?= (int) $em['id_empresa'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                    <form method="post" onsubmit="return confirm('<?= $hab ? '¿Deshabilitar esta empresa?' : '¿Habilitar esta empresa?' ?>')">
                        <?= campo_csrf() ?><input type="hidden" name="id_empresa" value="<?= (int) $em['id_empresa'] ?>">
                        <input type="hidden" name="accion" value="<?= $hab ? 'deshabilitar' : 'habilitar' ?>">
                        <button class="pn-btn <?= $hab ? 'peligro' : 'principal' ?>" type="submit"><?= $hab ? 'Deshabilitar' : 'Habilitar' ?></button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php panel_cerrar();
