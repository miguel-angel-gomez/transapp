<?php
// ============================================================
// RutaNexo — Super Administrador: usuarios
//   Ver los usuarios y habilitarlos o deshabilitarlos.
// ============================================================
require __DIR__ . '/_panel.php';

const USUARIOS_POR_PAGINA = 30;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc = (string) ($_POST['documento'] ?? '');
    $accion = (string) ($_POST['accion'] ?? '');
    if (!post_valido() || !ctype_digit($doc) || !in_array($accion, ['habilitar', 'deshabilitar'], true)) {
        flash('mal', 'La sesión expiró. Vuelve a intentarlo.');
    } else {
        $nuevo = $accion === 'habilitar' ? ESTADO_HABILITADO : ESTADO_DESHABILITADO;
        $pdo->prepare('UPDATE usuario SET id_estados = ? WHERE documento = ?')->execute([$nuevo, $doc]);
        flash('ok', $accion === 'habilitar' ? 'Usuario habilitado.' : 'Usuario deshabilitado: ya no podrá iniciar sesión.');
    }
    redirigir('usuarios.php' . (!empty($_POST['volver']) ? '?' . ltrim((string) $_POST['volver'], '?') : ''));
}

$roles = $pdo->query('SELECT id_rol, nombre_rol FROM rol WHERE id_rol <> 6 ORDER BY id_rol')->fetchAll(PDO::FETCH_KEY_PAIR);

$q  = trim((string) ($_GET['q'] ?? ''));
$fr = (int) ($_GET['rol'] ?? 0);
$fe = (int) ($_GET['estado'] ?? 0);
$pg = max(1, (int) ($_GET['p'] ?? 1));

$donde = ' WHERE 1=1';
$par = [];
if ($q !== '') { $donde .= ' AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.correo LIKE ? OR CAST(u.documento AS CHAR) LIKE ?)'; $par = array_fill(0, 4, '%' . $q . '%'); }
if ($fr) { $donde .= ' AND u.id_rol = ?'; $par[] = $fr; }
if ($fe) { $donde .= ' AND u.id_estados = ?'; $par[] = $fe; }

$total = $pdo->prepare('SELECT COUNT(*) FROM usuario u' . $donde);
$total->execute($par);
$total = (int) $total->fetchColumn();
$paginas = max(1, (int) ceil($total / USUARIOS_POR_PAGINA));
$pg = min($pg, $paginas);

$lista = $pdo->prepare('SELECT u.documento, u.nombre, u.apellido, u.correo, u.telefono, u.fecha_registro, u.id_estados, r.nombre_rol, e.razon_social, s.nombre AS estado
                          FROM usuario u
                          JOIN rol r ON r.id_rol = u.id_rol
                          JOIN estado_registro s ON s.id_estados = u.id_estados
                          LEFT JOIN empresa e ON e.id_empresa = u.id_empresa' . $donde .
                       ' ORDER BY u.fecha_registro DESC, u.documento DESC LIMIT ' . USUARIOS_POR_PAGINA . ' OFFSET ' . (($pg - 1) * USUARIOS_POR_PAGINA));
$lista->execute($par);
$lista = $lista->fetchAll();

$filtros = http_build_query(array_filter(['q' => $q, 'rol' => $fr, 'estado' => $fe, 'p' => $pg > 1 ? $pg : null]));

panel_abrir('usuarios', 'Usuarios', 'Consulta los usuarios de la plataforma y habilítalos o deshabilítalos.');
?>
<div class="pn-caja">
    <div class="pn-barra">
        <form method="get" class="pn-busca">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre, correo o documento" aria-label="Buscar usuarios">
            <select name="rol" aria-label="Filtrar por rol">
                <option value="0">Todos los roles</option>
                <?php foreach ($roles as $idR => $nombreR): ?>
                    <option value="<?= (int) $idR ?>" <?= $fr === (int) $idR ? 'selected' : '' ?>><?= e($nombreR) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="estado" aria-label="Filtrar por estado">
                <option value="0">Todos los estados</option>
                <option value="1" <?= $fe === 1 ? 'selected' : '' ?>>Habilitado</option>
                <option value="2" <?= $fe === 2 ? 'selected' : '' ?>>Deshabilitado</option>
            </select>
            <button class="pn-btn" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
            <?php if ($q !== '' || $fr || $fe): ?><a class="pn-btn" href="usuarios.php">Limpiar</a><?php endif; ?>
        </form>
        <span class="pn-tag"><?= $total ?> usuario<?= $total === 1 ? '' : 's' ?></span>
    </div>
    <?php if (!$lista): ?>
        <p class="pn-vacio">No hay usuarios<?= ($q !== '' || $fr || $fe) ? ' con ese filtro' : '' ?>.</p>
    <?php else: ?>
    <div class="pn-tabla-wrap"><table class="pn-tabla">
        <thead><tr><th>Usuario</th><th>Documento</th><th>Rol</th><th>Empresa</th><th>Registro</th><th>Estado</th><th>Acción</th></tr></thead>
        <tbody>
        <?php foreach ($lista as $us): $hab = (int) $us['id_estados'] === ESTADO_HABILITADO; ?>
            <tr>
                <td><b><?= e($us['nombre'] . ' ' . $us['apellido']) ?></b><br><small><?= e($us['correo']) ?></small></td>
                <td><?= e($us['documento']) ?></td>
                <td><?= e($us['nombre_rol']) ?></td>
                <td><?= e($us['razon_social'] ?: '—') ?></td>
                <td><?= e(date('d/m/Y', strtotime($us['fecha_registro']))) ?></td>
                <td><?= etiqueta_estado((int) $us['id_estados'], $us['estado']) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('<?= $hab ? '¿Deshabilitar a este usuario?' : '¿Habilitar a este usuario?' ?>')">
                        <?= campo_csrf() ?><input type="hidden" name="documento" value="<?= e($us['documento']) ?>"><input type="hidden" name="volver" value="<?= e($filtros) ?>">
                        <input type="hidden" name="accion" value="<?= $hab ? 'deshabilitar' : 'habilitar' ?>">
                        <button class="pn-btn <?= $hab ? 'peligro' : 'principal' ?>" type="submit"><?= $hab ? 'Deshabilitar' : 'Habilitar' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php if ($paginas > 1): $base = http_build_query(array_filter(['q' => $q, 'rol' => $fr, 'estado' => $fe])); ?>
        <div class="pn-pie-form" style="justify-content:center;align-items:center">
            <?php if ($pg > 1): ?><a class="pn-btn" href="?<?= e($base . '&p=' . ($pg - 1)) ?>"><i class="fa-solid fa-chevron-left"></i> Anterior</a><?php endif; ?>
            <span>Página <?= $pg ?> de <?= $paginas ?></span>
            <?php if ($pg < $paginas): ?><a class="pn-btn" href="?<?= e($base . '&p=' . ($pg + 1)) ?>">Siguiente <i class="fa-solid fa-chevron-right"></i></a><?php endif; ?>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php panel_cerrar();
