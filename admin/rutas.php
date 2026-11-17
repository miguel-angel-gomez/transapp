<?php
// ============================================================
// RutaNexo — Super Administrador: rutas y paraderos
//   Crear, editar y borrar rutas y los paraderos de cada ruta.
//   Vistas:  rutas.php (lista) · ?nueva=1 / ?editar=ID (formulario)
//            ?id=ID (paraderos de una ruta) · ?id=ID&parada=ID|nueva
// ============================================================
require __DIR__ . '/_panel.php';

// ------------------------------------------------------------
// Acciones (POST)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!post_valido()) { flash('mal', 'La sesión expiró. Vuelve a intentarlo.'); redirigir('rutas.php'); }
    $accion = (string) ($_POST['accion'] ?? '');

    // ---- Guardar ruta (crear / editar)
    if ($accion === 'guardar_ruta') {
        $id       = (int) ($_POST['id_ruta'] ?? 0);
        $nombre   = post_texto('nombre_ruta', 100);
        $codigo   = post_texto('codigo', 10);
        $color    = post_texto('color', 7);
        $origen   = post_texto('origen', 150);
        $destino  = post_texto('destino', 150);
        $desc     = post_texto('descripcion', 500);
        $tiempo   = post_texto('tiempo_estimado', 50);
        $dist     = trim((string) ($_POST['distancia'] ?? ''));
        $empresa  = (int) ($_POST['id_empresa'] ?? 0);

        $error = '';
        if ($nombre === '') { $error = 'El nombre de la ruta es obligatorio.'; }
        elseif ($color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) { $error = 'El color debe ser como #1F9D8B.'; }
        elseif ($dist !== '' && (!is_numeric($dist) || (float) $dist < 0 || (float) $dist > 99999999)) { $error = 'La distancia debe ser un número positivo (km).'; }
        elseif ($empresa && !$pdo->query('SELECT 1 FROM empresa WHERE id_empresa = ' . $empresa)->fetchColumn()) { $error = 'La empresa elegida no existe.'; }

        if ($error) {
            flash('mal', $error);
            redirigir($id ? 'rutas.php?editar=' . $id : 'rutas.php?nueva=1');
        }
        $par = [$empresa ?: null, $nombre, $codigo ?: null, $color ?: null, $origen ?: null, $destino ?: null,
                $desc ?: null, $dist === '' ? null : (float) $dist, $tiempo ?: null];
        if ($id) {
            $par[] = $id;
            $pdo->prepare('UPDATE ruta SET id_empresa=?, nombre_ruta=?, codigo=?, color=?, origen=?, destino=?, descripcion=?, distancia=?, tiempo_estimado=? WHERE id_ruta=?')->execute($par);
            flash('ok', 'Ruta actualizada.');
            redirigir('rutas.php?id=' . $id);
        }
        $pdo->prepare('INSERT INTO ruta (id_empresa, nombre_ruta, codigo, color, origen, destino, descripcion, distancia, tiempo_estimado) VALUES (?,?,?,?,?,?,?,?,?)')->execute($par);
        flash('ok', 'Ruta creada. Ahora agrega sus paraderos.');
        redirigir('rutas.php?id=' . (int) $pdo->lastInsertId());
    }

    // ---- Borrar ruta (con sus paraderos)
    if ($accion === 'borrar_ruta') {
        $id = (int) ($_POST['id_ruta'] ?? 0);
        try {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM paradero WHERE id_ruta = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM ruta WHERE id_ruta = ?')->execute([$id]);
            $pdo->commit();
            flash('ok', 'Ruta y paraderos eliminados.');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('Borrar ruta: ' . $ex->getMessage());
            flash('mal', 'No se pudo borrar: la ruta tiene viajes registrados.');
        }
        redirigir('rutas.php');
    }

    // ---- Guardar paradero (crear / editar)
    if ($accion === 'guardar_parada') {
        $id_ruta = (int) ($_POST['id_ruta'] ?? 0);
        $id      = (int) ($_POST['id_parada'] ?? 0);
        $nombre  = post_texto('nombre', 100);
        $ubic    = post_texto('ubicacion', 255);
        $orden   = trim((string) ($_POST['orden_parada'] ?? ''));
        $lat     = trim((string) ($_POST['lat'] ?? ''));
        $lng     = trim((string) ($_POST['lng'] ?? ''));
        $volver  = 'rutas.php?id=' . $id_ruta . ($id ? '&parada=' . $id : '&parada=nueva');

        $error = '';
        if (!$pdo->query('SELECT 1 FROM ruta WHERE id_ruta = ' . $id_ruta)->fetchColumn()) { flash('mal', 'La ruta no existe.'); redirigir('rutas.php'); }
        if ($nombre === '') { $error = 'El nombre del paradero es obligatorio.'; }
        elseif ($orden !== '' && !ctype_digit($orden)) { $error = 'El orden debe ser un número entero.'; }
        elseif (($lat === '') !== ($lng === '')) { $error = 'Escribe latitud y longitud juntas, o deja ambas vacías.'; }
        elseif ($lat !== '' && (!is_numeric($lat) || !is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180)) { $error = 'Coordenadas inválidas (latitud −90 a 90, longitud −180 a 180).'; }
        if ($error) { flash('mal', $error); redirigir($volver); }

        if ($orden === '') {
            $q = $pdo->prepare('SELECT COALESCE(MAX(orden_parada), 0) + 1 FROM paradero WHERE id_ruta = ?');
            $q->execute([$id_ruta]);
            $orden = (string) $q->fetchColumn();
        }
        $la = $lat === '' ? null : round((float) $lat, 6);
        $lo = $lng === '' ? null : round((float) $lng, 6);
        if ($id) {
            $pdo->prepare('UPDATE paradero SET nombre=?, ubicacion=?, orden_parada=?, lat=?, lng=? WHERE id_parada=? AND id_ruta=?')
                ->execute([$nombre, $ubic ?: null, (int) $orden, $la, $lo, $id, $id_ruta]);
            flash('ok', 'Paradero actualizado.');
        } else {
            $pdo->prepare('INSERT INTO paradero (id_ruta, nombre, ubicacion, orden_parada, lat, lng) VALUES (?,?,?,?,?,?)')
                ->execute([$id_ruta, $nombre, $ubic ?: null, (int) $orden, $la, $lo]);
            flash('ok', 'Paradero agregado.');
        }
        redirigir('rutas.php?id=' . $id_ruta);
    }

    // ---- Borrar paradero
    if ($accion === 'borrar_parada') {
        $id_ruta = (int) ($_POST['id_ruta'] ?? 0);
        $id = (int) ($_POST['id_parada'] ?? 0);
        try {
            $pdo->prepare('DELETE FROM paradero WHERE id_parada = ? AND id_ruta = ?')->execute([$id, $id_ruta]);
            flash('ok', 'Paradero eliminado.');
        } catch (PDOException $ex) {
            error_log('Borrar paradero: ' . $ex->getMessage());
            flash('mal', 'No se pudo borrar: el paradero se usa en viajes registrados.');
        }
        redirigir('rutas.php?id=' . $id_ruta);
    }
    redirigir('rutas.php');
}

// ------------------------------------------------------------
// Datos para mostrar
// ------------------------------------------------------------
$empresas = $pdo->query('SELECT id_empresa, razon_social FROM empresa ORDER BY razon_social')->fetchAll();
$ver_id   = (int) ($_GET['id'] ?? 0);
$editar   = (int) ($_GET['editar'] ?? 0);
$nueva    = !empty($_GET['nueva']);

$buscar_ruta = function (int $id) use ($pdo) {
    $q = $pdo->prepare('SELECT * FROM ruta WHERE id_ruta = ?');
    $q->execute([$id]);
    return $q->fetch() ?: null;
};

// ---------------------------------------------------------------- Formulario de ruta
if ($nueva || $editar) {
    $r = $editar ? $buscar_ruta($editar) : ['id_ruta' => 0, 'id_empresa' => null, 'nombre_ruta' => '', 'codigo' => '', 'color' => '#13c4a3',
        'origen' => '', 'destino' => '', 'descripcion' => '', 'distancia' => '', 'tiempo_estimado' => ''];
    if (!$r) { flash('mal', 'La ruta no existe.'); redirigir('rutas.php'); }
    panel_abrir('rutas', $editar ? 'Editar ruta' : 'Nueva ruta', 'Datos generales de la ruta. Los paraderos se agregan después de guardarla.');
    ?>
    <div class="pn-caja">
        <form method="post" class="pn-form" autocomplete="off">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="guardar_ruta">
            <input type="hidden" name="id_ruta" value="<?= (int) $r['id_ruta'] ?>">
            <div><label for="codigo">Código</label><input id="codigo" name="codigo" maxlength="10" value="<?= e($r['codigo']) ?>" placeholder="Ej.: 90"></div>
            <div class="ancho" style="grid-column:span 2"><label for="nombre_ruta">Nombre de la ruta *</label><input id="nombre_ruta" name="nombre_ruta" maxlength="100" required value="<?= e($r['nombre_ruta']) ?>"></div>
            <div><label for="color">Color en el mapa</label><input id="color" type="color" name="color" value="<?= e($r['color'] ?: '#13c4a3') ?>"></div>
            <div><label for="origen">Origen</label><input id="origen" name="origen" maxlength="150" value="<?= e($r['origen']) ?>"></div>
            <div><label for="destino">Destino</label><input id="destino" name="destino" maxlength="150" value="<?= e($r['destino']) ?>"></div>
            <div><label for="distancia">Distancia (km)</label><input id="distancia" name="distancia" inputmode="decimal" value="<?= e($r['distancia']) ?>"></div>
            <div><label for="tiempo_estimado">Tiempo estimado</label><input id="tiempo_estimado" name="tiempo_estimado" maxlength="50" value="<?= e($r['tiempo_estimado']) ?>" placeholder="Ej.: 45 min"></div>
            <div>
                <label for="id_empresa">Empresa</label>
                <select id="id_empresa" name="id_empresa">
                    <option value="0">— Sin empresa —</option>
                    <?php foreach ($empresas as $em): ?>
                        <option value="<?= (int) $em['id_empresa'] ?>" <?= (int) $r['id_empresa'] === (int) $em['id_empresa'] ? 'selected' : '' ?>><?= e($em['razon_social']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ancho"><label for="descripcion">Descripción (“Pasa por…”)</label><textarea id="descripcion" name="descripcion" rows="3" maxlength="500"><?= e($r['descripcion']) ?></textarea></div>
            <div class="ancho pn-pie-form">
                <button class="pn-btn principal" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar ruta</button>
                <a class="pn-btn" href="<?= $editar ? 'rutas.php?id=' . $editar : 'rutas.php' ?>">Cancelar</a>
            </div>
        </form>
    </div>
    <?php
    panel_cerrar();
    exit;
}

// ---------------------------------------------------------------- Paraderos de una ruta
if ($ver_id) {
    $r = $buscar_ruta($ver_id);
    if (!$r) { flash('mal', 'La ruta no existe.'); redirigir('rutas.php'); }
    $paradas = $pdo->prepare('SELECT * FROM paradero WHERE id_ruta = ? ORDER BY orden_parada, id_parada');
    $paradas->execute([$ver_id]);
    $paradas = $paradas->fetchAll();

    $p = null;
    $abre_form = isset($_GET['parada']);
    if ($abre_form && $_GET['parada'] !== 'nueva') {
        foreach ($paradas as $x) { if ((int) $x['id_parada'] === (int) $_GET['parada']) { $p = $x; } }
    }
    $p = $p ?: ['id_parada' => 0, 'nombre' => '', 'ubicacion' => '', 'orden_parada' => '', 'lat' => '', 'lng' => ''];

    panel_abrir('rutas', 'Ruta ' . ($r['codigo'] ? $r['codigo'] . ' · ' : '') . $r['nombre_ruta'], 'Datos de la ruta y administración de sus paraderos.');
    ?>
    <div class="pn-caja">
        <h2><i class="fa-solid fa-route"></i> <span class="pn-color" style="background:<?= e($r['color'] ?: '#ccc') ?>"></span> Datos de la ruta
            <span class="pn-acc" style="margin-left:auto">
                <a class="pn-btn" href="rutas.php?editar=<?= $ver_id ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                <form method="post" onsubmit="return confirm('¿Borrar la ruta y todos sus paraderos? No se puede deshacer.')">
                    <?= campo_csrf() ?><input type="hidden" name="accion" value="borrar_ruta"><input type="hidden" name="id_ruta" value="<?= $ver_id ?>">
                    <button class="pn-btn peligro" type="submit"><i class="fa-solid fa-trash"></i> Borrar</button>
                </form>
            </span>
        </h2>
        <dl class="pn-dato">
            <div><dt>Origen</dt><dd><?= e($r['origen'] ?: '—') ?></dd></div>
            <div><dt>Destino</dt><dd><?= e($r['destino'] ?: '—') ?></dd></div>
            <div><dt>Distancia</dt><dd><?= $r['distancia'] !== null ? e($r['distancia']) . ' km' : '—' ?></dd></div>
            <div><dt>Tiempo estimado</dt><dd><?= e($r['tiempo_estimado'] ?: '—') ?></dd></div>
            <div style="grid-column:1/-1"><dt>Descripción</dt><dd><?= e($r['descripcion'] ?: '—') ?></dd></div>
        </dl>
    </div>

    <?php if ($abre_form): ?>
    <div class="pn-caja">
        <h2><i class="fa-solid fa-location-dot"></i> <?= $p['id_parada'] ? 'Editar paradero' : 'Nuevo paradero' ?></h2>
        <form method="post" class="pn-form" autocomplete="off">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="guardar_parada">
            <input type="hidden" name="id_ruta" value="<?= $ver_id ?>">
            <input type="hidden" name="id_parada" value="<?= (int) $p['id_parada'] ?>">
            <div><label for="pn">Nombre *</label><input id="pn" name="nombre" maxlength="100" required value="<?= e($p['nombre']) ?>"></div>
            <div><label for="pu">Ubicación / dirección</label><input id="pu" name="ubicacion" maxlength="255" value="<?= e($p['ubicacion']) ?>"></div>
            <div><label for="po">Orden</label><input id="po" name="orden_parada" inputmode="numeric" value="<?= e($p['orden_parada']) ?>" placeholder="Automático"><small>Posición en el recorrido.</small></div>
            <div><label for="pla">Latitud</label><input id="pla" name="lat" inputmode="decimal" value="<?= e($p['lat']) ?>" placeholder="4.438900"></div>
            <div><label for="plo">Longitud</label><input id="plo" name="lng" inputmode="decimal" value="<?= e($p['lng']) ?>" placeholder="-75.232200"></div>
            <div class="ancho pn-pie-form">
                <button class="pn-btn principal" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar paradero</button>
                <a class="pn-btn" href="rutas.php?id=<?= $ver_id ?>">Cancelar</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="pn-caja">
        <div class="pn-barra">
            <h2 style="margin:0"><i class="fa-solid fa-location-dot"></i> Paraderos (<?= count($paradas) ?>)</h2>
            <a class="pn-btn principal" href="rutas.php?id=<?= $ver_id ?>&parada=nueva"><i class="fa-solid fa-plus"></i> Agregar paradero</a>
        </div>
        <?php if (!$paradas): ?>
            <p class="pn-vacio">Esta ruta aún no tiene paraderos.</p>
        <?php else: ?>
        <div class="pn-tabla-wrap"><table class="pn-tabla">
            <thead><tr><th>#</th><th>Nombre</th><th>Ubicación</th><th>Latitud</th><th>Longitud</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($paradas as $x): ?>
                <tr>
                    <td><?= e($x['orden_parada']) ?></td>
                    <td><b><?= e($x['nombre']) ?></b></td>
                    <td><?= e($x['ubicacion'] ?: '—') ?></td>
                    <td><?= e($x['lat'] ?? '—') ?></td>
                    <td><?= e($x['lng'] ?? '—') ?></td>
                    <td><div class="pn-acc">
                        <a class="pn-btn" href="rutas.php?id=<?= $ver_id ?>&parada=<?= (int) $x['id_parada'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                        <form method="post" onsubmit="return confirm('¿Borrar este paradero?')">
                            <?= campo_csrf() ?><input type="hidden" name="accion" value="borrar_parada">
                            <input type="hidden" name="id_ruta" value="<?= $ver_id ?>"><input type="hidden" name="id_parada" value="<?= (int) $x['id_parada'] ?>">
                            <button class="pn-btn peligro" type="submit"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
    <a class="pn-btn" href="rutas.php"><i class="fa-solid fa-arrow-left"></i> Volver a las rutas</a>
    <?php
    panel_cerrar();
    exit;
}

// ---------------------------------------------------------------- Lista de rutas
$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT r.*, e.razon_social, (SELECT COUNT(*) FROM paradero p WHERE p.id_ruta = r.id_ruta) AS paraderos
          FROM ruta r LEFT JOIN empresa e ON e.id_empresa = r.id_empresa';
$par = [];
if ($q !== '') {
    $sql .= ' WHERE r.nombre_ruta LIKE ? OR r.codigo LIKE ? OR r.origen LIKE ? OR r.destino LIKE ?';
    $par = array_fill(0, 4, '%' . $q . '%');
}
$sql .= ' ORDER BY CAST(r.codigo AS UNSIGNED), r.codigo, r.nombre_ruta';
$lista = $pdo->prepare($sql);
$lista->execute($par);
$lista = $lista->fetchAll();

panel_abrir('rutas', 'Rutas y paraderos', 'Crea, edita o borra las rutas de Ibagué y los paraderos de cada una.');
?>
<div class="pn-caja">
    <div class="pn-barra">
        <form method="get" class="pn-busca">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por código, nombre, origen o destino" aria-label="Buscar rutas">
            <button class="pn-btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            <?php if ($q !== ''): ?><a class="pn-btn" href="rutas.php">Limpiar</a><?php endif; ?>
        </form>
        <a class="pn-btn principal" href="rutas.php?nueva=1"><i class="fa-solid fa-plus"></i> Nueva ruta</a>
    </div>
    <?php if (!$lista): ?>
        <p class="pn-vacio">No hay rutas<?= $q !== '' ? ' que coincidan con la búsqueda' : '' ?>.</p>
    <?php else: ?>
    <div class="pn-tabla-wrap"><table class="pn-tabla">
        <thead><tr><th>Código</th><th>Ruta</th><th>Origen → Destino</th><th>Empresa</th><th>Paraderos</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($lista as $r): ?>
            <tr>
                <td><span class="pn-color" style="background:<?= e($r['color'] ?: '#ccc') ?>"></span><b><?= e($r['codigo'] ?: '—') ?></b></td>
                <td><?= e($r['nombre_ruta']) ?></td>
                <td><?= e(($r['origen'] ?: '—') . ' → ' . ($r['destino'] ?: '—')) ?></td>
                <td><?= e($r['razon_social'] ?: '—') ?></td>
                <td><?= (int) $r['paraderos'] ?></td>
                <td><div class="pn-acc">
                    <a class="pn-btn" href="rutas.php?id=<?= (int) $r['id_ruta'] ?>"><i class="fa-solid fa-location-dot"></i> Paraderos</a>
                    <a class="pn-btn" href="rutas.php?editar=<?= (int) $r['id_ruta'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                    <form method="post" onsubmit="return confirm('¿Borrar la ruta y todos sus paraderos? No se puede deshacer.')">
                        <?= campo_csrf() ?><input type="hidden" name="accion" value="borrar_ruta"><input type="hidden" name="id_ruta" value="<?= (int) $r['id_ruta'] ?>">
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
