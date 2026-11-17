<?php
// ============================================================
// RutaNexo — Administrador: asignación de roles
//   Cambia el rol de las personas de SU empresa. Al elegir Conductor, Técnico o
//   Propietario también se crea su registro en la tabla correspondiente.
// ============================================================
require __DIR__ . '/_panel.php';

$roles = roles_asignables($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!post_valido()) { flash('mal', 'La sesión expiró. Vuelve a intentarlo.'); redirigir('roles.php'); }
    $doc = (string) ($_POST['documento'] ?? '');
    $rol = (int) ($_POST['id_rol'] ?? 0);
    $obj = usuario_de_empresa($pdo, $id_empresa, $doc);
    if (!$obj) { flash('mal', 'El usuario no existe en tu empresa.'); }
    elseif (!isset($roles[$rol])) { flash('mal', 'Elige un rol válido.'); }
    elseif ($doc === (string) $yo['documento']) { flash('mal', 'No puedes cambiar tu propio rol: otro administrador debe hacerlo.'); }
    elseif ((int) $obj['id_rol'] === $rol) { flash('ok', 'El usuario ya tiene ese rol.'); }
    elseif (asignar_rol($pdo, $id_empresa, $doc, $rol)) { flash('ok', $obj['nombre'] . ' ' . $obj['apellido'] . ' ahora es ' . $roles[$rol]['nombre_rol'] . '.'); }
    else { flash('mal', 'No se pudo asignar el rol. Inténtalo de nuevo.'); }
    redirigir('roles.php' . (!empty($_POST['volver']) ? '?' . ltrim((string) $_POST['volver'], '?') : ''));
}

// Cuántos usuarios hay por rol en la empresa
$cuantos = $pdo->prepare('SELECT id_rol, COUNT(*) FROM usuario WHERE id_empresa = ? GROUP BY id_rol');
$cuantos->execute([$id_empresa]);
$cuantos = $cuantos->fetchAll(PDO::FETCH_KEY_PAIR);

$q  = trim((string) ($_GET['q'] ?? ''));
$fr = (int) ($_GET['rol'] ?? 0);
$donde = ' WHERE u.id_empresa = ?';
$par = [$id_empresa];
if ($q !== '') { $donde .= ' AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.correo LIKE ? OR CAST(u.documento AS CHAR) LIKE ?)'; array_push($par, ...array_fill(0, 4, '%' . $q . '%')); }
if ($fr) { $donde .= ' AND u.id_rol = ?'; $par[] = $fr; }
$lista = $pdo->prepare('SELECT u.documento, u.nombre, u.apellido, u.correo, u.id_rol, u.id_estados FROM usuario u' . $donde . ' ORDER BY u.nombre, u.apellido LIMIT 200');
$lista->execute($par);
$lista = $lista->fetchAll();
$filtros = http_build_query(array_filter(['q' => $q, 'rol' => $fr]));

$iconos = [1 => 'fa-user-shield', 2 => 'fa-id-card', 3 => 'fa-screwdriver-wrench', 4 => 'fa-car', 5 => 'fa-person'];

panel_abrir('roles', 'Asignación de roles', 'Define qué puede hacer cada persona de tu empresa.');
?>
<div class="pn-cards">
    <?php foreach ($roles as $idR => $r): ?>
        <a class="pn-card-n" href="roles.php?rol=<?= $idR ?>">
            <i class="fa-solid <?= $iconos[$idR] ?? 'fa-user' ?>"></i><b><?= (int) ($cuantos[$idR] ?? 0) ?></b>
            <span><b style="font:700 .95rem var(--f-titulo);display:inline;margin:0;color:var(--navy)"><?= e($r['nombre_rol']) ?></b><br><?= e($r['descripcion']) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="pn-caja">
    <div class="pn-barra">
        <form method="get" class="pn-busca">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre, correo o documento" aria-label="Buscar usuarios">
            <select name="rol" aria-label="Filtrar por rol">
                <option value="0">Todos los roles</option>
                <?php foreach ($roles as $idR => $r): ?>
                    <option value="<?= $idR ?>" <?= $fr === $idR ? 'selected' : '' ?>><?= e($r['nombre_rol']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="pn-btn" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
            <?php if ($q !== '' || $fr): ?><a class="pn-btn" href="roles.php">Limpiar</a><?php endif; ?>
        </form>
        <a class="pn-btn" href="usuarios.php?nuevo=1"><i class="fa-solid fa-user-plus"></i> Nuevo usuario</a>
    </div>
    <?php if (!$lista): ?>
        <p class="pn-vacio">No hay usuarios con ese filtro.</p>
    <?php else: ?>
    <div class="pn-tabla-wrap"><table class="pn-tabla">
        <thead><tr><th>Usuario</th><th>Documento</th><th>Rol actual</th><th>Asignar rol</th></tr></thead>
        <tbody>
        <?php foreach ($lista as $us): $yo_mismo = (string) $us['documento'] === (string) $yo['documento']; ?>
            <tr>
                <td><b><?= e($us['nombre'] . ' ' . $us['apellido']) ?></b> <?= $yo_mismo ? '<span class="pn-tag ok">Tú</span>' : '' ?>
                    <?= (int) $us['id_estados'] !== ESTADO_HABILITADO ? '<span class="pn-tag mal">Deshabilitado</span>' : '' ?><br><small><?= e($us['correo']) ?></small></td>
                <td><?= e($us['documento']) ?></td>
                <td><span class="pn-tag"><?= e($roles[(int) $us['id_rol']]['nombre_rol'] ?? '—') ?></span></td>
                <td>
                    <?php if ($yo_mismo): ?>
                        <small>No puedes cambiar tu propio rol.</small>
                    <?php else: ?>
                    <form method="post" class="pn-busca" onsubmit="return confirm('¿Cambiar el rol de este usuario?')">
                        <?= campo_csrf() ?><input type="hidden" name="documento" value="<?= e($us['documento']) ?>"><input type="hidden" name="volver" value="<?= e($filtros) ?>">
                        <select name="id_rol" aria-label="Nuevo rol de <?= e($us['nombre']) ?>">
                            <?php foreach ($roles as $idR => $r): ?>
                                <option value="<?= $idR ?>" <?= (int) $us['id_rol'] === $idR ? 'selected' : '' ?>><?= e($r['nombre_rol']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="pn-btn principal" type="submit"><i class="fa-solid fa-check"></i> Asignar</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php panel_cerrar();
