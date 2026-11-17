<?php
// ============================================================
// RutaNexo — Super Administrador: planes de suscripción
//   Ver, crear, editar, borrar y habilitar/deshabilitar planes.
//   Vistas: planes.php (lista) · ?nuevo=1 / ?editar=ID (formulario)
// ============================================================
require __DIR__ . '/_panel.php';
require_once __DIR__ . '/../includes/compras.php';

// Características que puede incluir un plan (clave del JSON → texto)
$caracteristicas = [];
foreach (['rutas_viajes', 'consulta_mantenimientos', 'mantenimientos_tecnicos', 'asignacion_conductores', 'reportes_basicos_limitados',
          'reportes_incidentes', 'reportes_avanzados_limitados', 'reportes_avanzados', 'pagos_digitales', 'programa_puntos', 'reciclaje', 'canje_puntos'] as $k) {
    $caracteristicas[$k] = texto_caracteristica($k);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!post_valido()) { flash('mal', 'La sesión expiró. Vuelve a intentarlo.'); redirigir('planes.php'); }
    $accion = (string) ($_POST['accion'] ?? '');
    $id = (int) ($_POST['id_plan'] ?? 0);

    if ($accion === 'guardar') {
        $nombre = post_texto('nombre', 100);
        $desc   = post_texto('descripcion', 255);
        $precio = str_replace(['.', ','], ['', '.'], trim((string) ($_POST['precio'] ?? '')));   // "1.490.000" → 1490000
        $dias   = trim((string) ($_POST['duracion_dias'] ?? ''));
        $lim    = trim((string) ($_POST['limite_vehiculos'] ?? ''));
        $marcadas = array_values(array_intersect(array_keys($caracteristicas), (array) ($_POST['caracteristicas'] ?? [])));

        $error = '';
        if ($nombre === '') { $error = 'El nombre del plan es obligatorio.'; }
        elseif (!is_numeric($precio) || (float) $precio < 0 || (float) $precio > 9999999999) { $error = 'El precio debe ser un número igual o mayor que 0.'; }
        elseif (!ctype_digit($dias) || (int) $dias < 1 || (int) $dias > 3650) { $error = 'La duración debe ser de 1 a 3650 días.'; }
        elseif ($lim !== '' && (!ctype_digit($lim) || (int) $lim < 1)) { $error = 'El límite de vehículos debe ser un entero positivo (o vacío = ilimitado).'; }
        else {
            $dup = $pdo->prepare('SELECT 1 FROM plan_suscripcion WHERE nombre = ? AND id_plan <> ?');
            $dup->execute([$nombre, $id]);
            if ($dup->fetchColumn()) { $error = 'Ya existe un plan con ese nombre.'; }
        }
        if ($error) { flash('mal', $error); redirigir($id ? 'planes.php?editar=' . $id : 'planes.php?nuevo=1'); }

        $json = json_encode($marcadas, JSON_UNESCAPED_UNICODE);
        $par = [$nombre, $desc ?: null, (float) $precio, (int) $dias, $lim === '' ? null : (int) $lim, $json];
        if ($id) {
            $par[] = $id;
            $pdo->prepare('UPDATE plan_suscripcion SET nombre=?, descripcion=?, precio=?, duracion_dias=?, limite_vehiculos=?, caracteristicas=? WHERE id_plan=?')->execute($par);
            flash('ok', 'Plan actualizado.');
        } else {
            $pdo->prepare('INSERT INTO plan_suscripcion (nombre, descripcion, precio, duracion_dias, limite_vehiculos, caracteristicas, id_estados) VALUES (?,?,?,?,?,?,1)')->execute($par);
            flash('ok', 'Plan creado y habilitado.');
        }
        redirigir('planes.php');
    }

    if ($accion === 'habilitar' || $accion === 'deshabilitar') {
        $pdo->prepare('UPDATE plan_suscripcion SET id_estados = ? WHERE id_plan = ?')
            ->execute([$accion === 'habilitar' ? ESTADO_HABILITADO : ESTADO_DESHABILITADO, $id]);
        flash('ok', $accion === 'habilitar' ? 'Plan habilitado: ya se puede comprar.' : 'Plan deshabilitado: ya no se puede comprar.');
        redirigir('planes.php');
    }

    if ($accion === 'borrar') {
        try {
            $pdo->prepare('DELETE FROM plan_suscripcion WHERE id_plan = ?')->execute([$id]);
            flash('ok', 'Plan eliminado.');
        } catch (PDOException $ex) {
            error_log('Borrar plan: ' . $ex->getMessage());
            flash('mal', 'No se pudo borrar: el plan tiene suscripciones. Mejor deshabilítalo.');
        }
        redirigir('planes.php');
    }
    redirigir('planes.php');
}

// ---------------------------------------------------------------- Formulario
if (!empty($_GET['nuevo']) || !empty($_GET['editar'])) {
    $id = (int) ($_GET['editar'] ?? 0);
    $pl = ['id_plan' => 0, 'nombre' => '', 'descripcion' => '', 'precio' => '0', 'duracion_dias' => 365, 'limite_vehiculos' => '', 'caracteristicas' => '[]'];
    if ($id) {
        $q = $pdo->prepare('SELECT * FROM plan_suscripcion WHERE id_plan = ?');
        $q->execute([$id]);
        $pl = $q->fetch();
        if (!$pl) { flash('mal', 'El plan no existe.'); redirigir('planes.php'); }
    }
    $activas = json_decode((string) $pl['caracteristicas'], true) ?: [];

    panel_abrir('planes', $id ? 'Editar plan' : 'Nuevo plan', 'Define el precio, la duración, el límite de vehículos y lo que incluye el plan.');
    ?>
    <div class="pn-caja">
        <form method="post" class="pn-form" autocomplete="off">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id_plan" value="<?= (int) $pl['id_plan'] ?>">
            <div><label for="nombre">Nombre *</label><input id="nombre" name="nombre" maxlength="100" required value="<?= e($pl['nombre']) ?>"></div>
            <div><label for="precio">Precio (COP) *</label><input id="precio" name="precio" inputmode="decimal" required value="<?= e(number_format((float) $pl['precio'], 0, '', '')) ?>"><small>Sin decimales. 0 = plan gratuito.</small></div>
            <div><label for="dias">Duración (días) *</label><input id="dias" name="duracion_dias" inputmode="numeric" required value="<?= (int) $pl['duracion_dias'] ?>"><small>365 = anual.</small></div>
            <div><label for="lim">Límite de vehículos</label><input id="lim" name="limite_vehiculos" inputmode="numeric" value="<?= e($pl['limite_vehiculos']) ?>" placeholder="Ilimitado"></div>
            <div class="ancho"><label for="desc">Descripción</label><input id="desc" name="descripcion" maxlength="255" value="<?= e($pl['descripcion']) ?>"></div>
            <div class="ancho">
                <label>Incluye</label>
                <div class="pn-checks">
                    <?php foreach ($caracteristicas as $k => $texto): ?>
                        <label><input type="checkbox" name="caracteristicas[]" value="<?= e($k) ?>" <?= in_array($k, $activas, true) ? 'checked' : '' ?>> <?= e($texto) ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="ancho pn-pie-form">
                <button class="pn-btn principal" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar plan</button>
                <a class="pn-btn" href="planes.php">Cancelar</a>
            </div>
        </form>
    </div>
    <?php
    panel_cerrar();
    exit;
}

// ---------------------------------------------------------------- Lista
$lista = $pdo->query('SELECT p.*, s.nombre AS estado,
                             (SELECT COUNT(*) FROM suscripcion x WHERE x.id_plan = p.id_plan) AS suscripciones
                        FROM plan_suscripcion p JOIN estado_registro s ON s.id_estados = p.id_estados
                       ORDER BY p.precio, p.id_plan')->fetchAll();

panel_abrir('planes', 'Planes', 'Administra los planes de suscripción que se muestran para comprar.');
?>
<div class="pn-caja">
    <div class="pn-barra">
        <span class="pn-tag"><?= count($lista) ?> plan<?= count($lista) === 1 ? '' : 'es' ?></span>
        <a class="pn-btn principal" href="planes.php?nuevo=1"><i class="fa-solid fa-plus"></i> Nuevo plan</a>
    </div>
    <?php if (!$lista): ?>
        <p class="pn-vacio">No hay planes creados.</p>
    <?php else: ?>
    <div class="pn-tabla-wrap"><table class="pn-tabla">
        <thead><tr><th>Plan</th><th>Precio</th><th>Duración</th><th>Vehículos</th><th>Incluye</th><th>Suscripciones</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($lista as $pl): $hab = (int) $pl['id_estados'] === ESTADO_HABILITADO; $inc = json_decode((string) $pl['caracteristicas'], true) ?: []; ?>
            <tr>
                <td><b><?= e($pl['nombre']) ?></b><br><small><?= e($pl['descripcion']) ?></small></td>
                <td><?= (float) $pl['precio'] > 0 ? e(dinero($pl['precio'])) : 'Gratis' ?></td>
                <td><?= (int) $pl['duracion_dias'] === 365 ? '1 año' : (int) $pl['duracion_dias'] . ' días' ?></td>
                <td><?= $pl['limite_vehiculos'] === null ? 'Ilimitados' : (int) $pl['limite_vehiculos'] ?></td>
                <td><?= count($inc) ?> funciones</td>
                <td><?= (int) $pl['suscripciones'] ?></td>
                <td><?= etiqueta_estado((int) $pl['id_estados'], $pl['estado']) ?></td>
                <td><div class="pn-acc">
                    <a class="pn-btn" href="planes.php?editar=<?= (int) $pl['id_plan'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                    <form method="post">
                        <?= campo_csrf() ?><input type="hidden" name="id_plan" value="<?= (int) $pl['id_plan'] ?>">
                        <input type="hidden" name="accion" value="<?= $hab ? 'deshabilitar' : 'habilitar' ?>">
                        <button class="pn-btn" type="submit"><?= $hab ? 'Deshabilitar' : 'Habilitar' ?></button>
                    </form>
                    <form method="post" onsubmit="return confirm('¿Borrar este plan? No se puede deshacer.')">
                        <?= campo_csrf() ?><input type="hidden" name="id_plan" value="<?= (int) $pl['id_plan'] ?>"><input type="hidden" name="accion" value="borrar">
                        <button class="pn-btn peligro" type="submit"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php panel_cerrar();
