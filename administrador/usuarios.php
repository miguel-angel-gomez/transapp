
<?php
// ============================================================
// RutaNexo — Administrador: gestión de usuarios de SU empresa
//   Crear, editar, habilitar/deshabilitar y eliminar usuarios, con su rol.
//   Vistas: usuarios.php (lista) · ?nuevo=1 · ?editar=DOCUMENTO
// ============================================================
require __DIR__ . '/_panel.php';

const USUARIOS_POR_PAGINA = 30;
const REGEX_NOMBRE = '/^[\p{L}][\p{L}\s.\'-]{1,99}$/u';

$roles = roles_asignables($pdo);
$err_form = '';
$datos_form = null;
$form_abierto = false;
$form_doc = '';

// ------------------------------------------------------------
// Acciones (POST)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!post_valido()) {
        flash('mal', 'La sesión expiró. Vuelve a intentarlo.');
        redirigir('usuarios.php');
    }

    $accion = (string) ($_POST['accion'] ?? '');

    // ---- Habilitar / deshabilitar
    if ($accion === 'habilitar' || $accion === 'deshabilitar') {
        $doc = (string) ($_POST['documento'] ?? '');
        $obj = usuario_de_empresa($pdo, $id_empresa, $doc);

        if (!$obj) {
            flash('mal', 'El usuario no existe en tu empresa.');
        } elseif ($doc === (string) $yo['documento']) {
            flash('mal', 'No puedes deshabilitar tu propia cuenta.');
        } else {
            $pdo->prepare('UPDATE usuario SET id_estados = ? WHERE documento = ? AND id_empresa = ?')
                ->execute([
                    $accion === 'habilitar' ? ESTADO_HABILITADO : ESTADO_DESHABILITADO,
                    $doc,
                    $id_empresa
                ]);

            flash(
                'ok',
                $accion === 'habilitar'
                    ? 'Usuario habilitado.'
                    : 'Usuario deshabilitado: ya no podrá iniciar sesión.'
            );
        }

        redirigir('usuarios.php' . (!empty($_POST['volver']) ? '?' . ltrim((string) $_POST['volver'], '?') : ''));
    }

    // ---- Eliminar
    if ($accion === 'borrar') {
        $doc = (string) ($_POST['documento'] ?? '');
        $obj = usuario_de_empresa($pdo, $id_empresa, $doc);

        if (!$obj) {
            flash('mal', 'El usuario no existe en tu empresa.');
        } elseif ($doc === (string) $yo['documento']) {
            flash('mal', 'No puedes eliminar tu propia cuenta.');
        } else {
            try {
                $pdo->beginTransaction();

                foreach (['conductor', 'tecnico', 'titular_vehiculo'] as $tabla) {
                    $pdo->prepare("DELETE FROM $tabla WHERE documento = ?")->execute([$doc]);
                }

                $pdo->prepare('DELETE FROM usuario WHERE documento = ? AND id_empresa = ?')
                    ->execute([$doc, $id_empresa]);

                $pdo->commit();
                flash('ok', 'Usuario eliminado.');
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Borrar usuario: ' . $ex->getMessage());

                flash(
                    'mal',
                    'No se pudo eliminar: el usuario tiene registros asociados (viajes, tiquetes, vehículos…). Mejor deshabilítalo.'
                );
            }
        }

        redirigir('usuarios.php');
    }

    // ---- Guardar (crear / editar)
    if ($accion === 'guardar') {
        $orig = (string) ($_POST['doc_original'] ?? '');
        $editando = $orig !== '' ? usuario_de_empresa($pdo, $id_empresa, $orig) : null;

        if ($orig !== '' && !$editando) {
            flash('mal', 'El usuario no existe en tu empresa.');
            redirigir('usuarios.php');
        }

        $doc = $editando
            ? $orig
            : preg_replace('/\s+/', '', (string) ($_POST['documento'] ?? ''));

        $nombre = post_texto('nombre', 100);
        $apellido = post_texto('apellido', 100);
        $correo = mb_strtolower(post_texto('correo', 100));
        $telefono = post_texto('telefono', 20);
        $direccion = post_texto('direccion', 255);
        $clave = (string) ($_POST['password'] ?? '');
        $rol = (int) ($_POST['id_rol'] ?? 0);

        $es_yo = $editando && $orig === (string) $yo['documento'];

        if ($es_yo) {
            $rol = (int) $yo['id_rol'];
        }

        $error = '';

        if (!$editando && !preg_match('/^\d{5,12}$/', $doc)) {
            $error = 'El documento debe tener solo números (5 a 12 dígitos).';
        } elseif (!preg_match(REGEX_NOMBRE, $nombre)) {
            $error = 'Escribe el nombre (solo letras).';
        } elseif (!preg_match(REGEX_NOMBRE, $apellido)) {
            $error = 'Escribe el apellido (solo letras).';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Escribe un correo válido.';
        } elseif ($telefono !== '' && !preg_match('/^[0-9+()\s-]{7,20}$/', $telefono)) {
            $error = 'El teléfono solo puede tener números, espacios, + ( ) y -.';
        } elseif (!isset($roles[$rol])) {
            $error = 'Elige un rol válido.';
        } elseif (!$editando && $clave === '') {
            $error = 'Escribe una contraseña inicial para el usuario.';
        } elseif ($clave !== '' && ($m = error_clave($clave))) {
            $error = 'Contraseña: ' . $m;
        } else {
            // Documento y correo no se pueden repetir
            $q = $pdo->prepare(
                'SELECT 1 FROM usuario WHERE documento = ?
                 UNION SELECT 1 FROM super_admin WHERE documento = ?'
            );
            $q->execute([$doc, $doc]);

            if (!$editando && $q->fetchColumn()) {
                $error = 'Ya existe una cuenta con ese documento.';
            } else {
                $q = $pdo->prepare(
                    'SELECT 1 FROM usuario WHERE correo = ? AND documento <> ?
                     UNION SELECT 1 FROM super_admin WHERE email = ?'
                );
                $q->execute([$correo, $doc, $correo]);

                if ($q->fetchColumn()) {
                    $error = 'Ese correo ya está registrado.';
                }
            }
        }

        if ($error) {
            $err_form = $error;
            $form_abierto = true;
            $form_doc = $editando ? $orig : '';

            $datos_form = [
                'documento' => $doc,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'correo' => $correo,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'id_rol' => $rol
            ];
        } else {
            $rol_cambia = !$editando || (int) $editando['id_rol'] !== $rol;

            try {
                if ($editando) {
                    $pdo->prepare(
                        'UPDATE usuario
                         SET nombre=?, apellido=?, correo=?, telefono=?, direccion=?
                         WHERE documento=? AND id_empresa=?'
                    )->execute([
                        $nombre,
                        $apellido,
                        $correo,
                        $telefono ?: null,
                        $direccion ?: null,
                        $doc,
                        $id_empresa
                    ]);

                    if ($clave !== '') {
                        $pdo->prepare(
                            'UPDATE usuario
                             SET contrasena = ?, pin = NULL, pin_expira = NULL
                             WHERE documento = ? AND id_empresa = ?'
                        )->execute([
                            password_hash($clave, PASSWORD_DEFAULT),
                            $doc,
                            $id_empresa
                        ]);
                    }
                } else {
                    $pdo->prepare(
                        'INSERT INTO usuario
                         (documento, nit, nombre, apellido, correo, contrasena,
                          telefono, direccion, id_rol, id_empresa, id_estados)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?)'
                    )->execute([
                        $doc,
                        $yo['nit'],
                        $nombre,
                        $apellido,
                        $correo,
                        password_hash($clave, PASSWORD_DEFAULT),
                        $telefono ?: null,
                        $direccion ?: null,
                        ROL_USUARIO,
                        $id_empresa,
                        ESTADO_HABILITADO
                    ]);
                }

                if ($rol_cambia && !asignar_rol($pdo, $id_empresa, $doc, $rol)) {
                    flash(
                        'mal',
                        'Los datos se guardaron, pero no se pudo asignar el rol. Inténtalo desde la sección Roles.'
                    );
                } else {
                    flash(
                        'ok',
                        $editando
                            ? 'Usuario actualizado.'
                            : 'Usuario creado. Ya puede iniciar sesión con su correo y la contraseña inicial.'
                    );
                }
            } catch (PDOException $ex) {
                error_log('Guardar usuario: ' . $ex->getMessage());
                flash('mal', 'No se pudo guardar el usuario. Revisa los datos e inténtalo de nuevo.');
            }

            redirigir('usuarios.php');
        }
    }

    if (!$form_abierto) {
        redirigir('usuarios.php');
    }
}

// ------------------------------------------------------------
// Formulario (nuevo / editar)
// ------------------------------------------------------------
if (!$form_abierto && (!empty($_GET['nuevo']) || !empty($_GET['editar']))) {
    $form_abierto = true;
    $form_doc = '';

    if (!empty($_GET['editar'])) {
        $fila = usuario_de_empresa($pdo, $id_empresa, (string) $_GET['editar']);

        if (!$fila) {
            flash('mal', 'El usuario no existe en tu empresa.');
            redirigir('usuarios.php');
        }

        $form_doc = (string) $fila['documento'];
        $datos_form = $fila;
    }
}

if ($form_abierto) {
    $d = $datos_form ?: [
        'documento' => '',
        'nombre' => '',
        'apellido' => '',
        'correo' => '',
        'telefono' => '',
        'direccion' => '',
        'id_rol' => ROL_USUARIO
    ];

    $editando = $form_doc !== '';
    $es_yo = $editando && $form_doc === (string) $yo['documento'];

    panel_abrir(
        'usuarios',
        $editando ? 'Editar usuario' : 'Nuevo usuario',
        $editando
            ? 'Cambia los datos, el rol o la contraseña del usuario.'
            : 'Crea una cuenta para alguien de tu empresa y asígnale un rol.'
    );
    ?>
    <div class="pn-caja">
        <?php if ($err_form): ?>
            <div class="pn-alerta mal" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= e($err_form) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="pn-form" autocomplete="off">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="doc_original" value="<?= e($form_doc) ?>">

            <div>
                <label for="documento">Documento *</label>
                <input id="documento" name="documento" inputmode="numeric" maxlength="12"
                       required value="<?= e($d['documento']) ?>" <?= $editando ? 'readonly' : '' ?>>
                <?php if ($editando): ?>
                    <small>El documento no se puede cambiar.</small>
                <?php endif; ?>
            </div>

            <div>
                <label for="nombre">Nombre *</label>
                <input id="nombre" name="nombre" maxlength="100" required value="<?= e($d['nombre']) ?>">
            </div>

            <div>
                <label for="apellido">Apellido *</label>
                <input id="apellido" name="apellido" maxlength="100" required value="<?= e($d['apellido']) ?>">
            </div>

            <div>
                <label for="correo">Correo *</label>
                <input id="correo" type="email" name="correo" maxlength="100" required value="<?= e($d['correo']) ?>">
            </div>

            <div>
                <label for="telefono">Teléfono</label>
                <input id="telefono" name="telefono" maxlength="20" value="<?= e($d['telefono']) ?>">
            </div>

            <div>
                <label for="direccion">Dirección</label>
                <input id="direccion" name="direccion" maxlength="255" value="<?= e($d['direccion']) ?>">
            </div>

            <div>
                <label for="id_rol">Rol *</label>
                <select id="id_rol" name="id_rol" <?= $es_yo ? 'disabled' : '' ?>>
                    <?php foreach ($roles as $idR => $r): ?>
                        <option value="<?= $idR ?>" <?= (int) $d['id_rol'] === $idR ? 'selected' : '' ?>>
                            <?= e($r['nombre_rol']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($es_yo): ?>
                    <small>No puedes cambiar tu propio rol.</small>
                <?php endif; ?>
            </div>

            <div>
                <label for="password"><?= $editando ? 'Nueva contraseña' : 'Contraseña inicial *' ?></label>
                <input id="password" type="password" name="password" maxlength="72"
                       autocomplete="new-password" <?= $editando ? '' : 'required' ?>>
                <small>
                    <?= $editando ? 'Déjala vacía para no cambiarla. ' : '' ?>
                    Mínimo 8 caracteres, con letras y números.
                </small>
            </div>

            <div class="ancho pn-pie-form">
                <button class="pn-btn principal" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar usuario
                </button>
                <a class="pn-btn" href="usuarios.php">Cancelar</a>
            </div>
        </form>
    </div>
    <?php
    panel_cerrar();
    exit;
}

// ------------------------------------------------------------
// Lista
// ------------------------------------------------------------
$q = trim((string) ($_GET['q'] ?? ''));
$fr = (int) ($_GET['rol'] ?? 0);
$fe = (int) ($_GET['estado'] ?? 0);
$pg = max(1, (int) ($_GET['p'] ?? 1));

$donde = ' WHERE u.id_empresa = ?';
$par = [$id_empresa];

if ($q !== '') {
    $donde .= ' AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.correo LIKE ? OR CAST(u.documento AS CHAR) LIKE ?)';
    array_push($par, ...array_fill(0, 4, '%' . $q . '%'));
}

if ($fr) {
    $donde .= ' AND u.id_rol = ?';
    $par[] = $fr;
}

if ($fe) {
    $donde .= ' AND u.id_estados = ?';
    $par[] = $fe;
}

$total = $pdo->prepare('SELECT COUNT(*) FROM usuario u' . $donde);
$total->execute($par);
$total = (int) $total->fetchColumn();

$paginas = max(1, (int) ceil($total / USUARIOS_POR_PAGINA));
$pg = min($pg, $paginas);

$lista = $pdo->prepare(
    'SELECT u.documento, u.nombre, u.apellido, u.correo, u.telefono,
            u.fecha_registro, u.id_rol, u.id_estados, r.nombre_rol, s.nombre AS estado
     FROM usuario u
     JOIN rol r ON r.id_rol = u.id_rol
     JOIN estado_registro s ON s.id_estados = u.id_estados' . $donde .
    ' ORDER BY u.fecha_registro DESC, u.documento DESC
      LIMIT ' . USUARIOS_POR_PAGINA . ' OFFSET ' . (($pg - 1) * USUARIOS_POR_PAGINA)
);

$lista->execute($par);
$lista = $lista->fetchAll();

$filtros = http_build_query(array_filter([
    'q' => $q,
    'rol' => $fr,
    'estado' => $fe,
    'p' => $pg > 1 ? $pg : null
]));

panel_abrir(
    'usuarios',
    'Usuarios de mi empresa',
    'Crea, edita, habilita o elimina a las personas de tu empresa.'
);
?>

<div class="pn-caja">
    <div class="pn-barra">
        <form method="get" class="pn-busca">
            <input type="search" name="q" value="<?= e($q) ?>"
                   placeholder="Buscar por nombre, correo o documento"
                   aria-label="Buscar usuarios">

            <select name="rol" aria-label="Filtrar por rol">
                <option value="0">Todos los roles</option>
                <?php foreach ($roles as $idR => $r): ?>
                    <option value="<?= $idR ?>" <?= $fr === $idR ? 'selected' : '' ?>>
                        <?= e($r['nombre_rol']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="estado" aria-label="Filtrar por estado">
                <option value="0">Todos los estados</option>
                <option value="1" <?= $fe === 1 ? 'selected' : '' ?>>Habilitado</option>
                <option value="2" <?= $fe === 2 ? 'selected' : '' ?>>Deshabilitado</option>
            </select>

            <button class="pn-btn" type="submit">
                <i class="fa-solid fa-filter"></i> Filtrar
            </button>

            <?php if ($q !== '' || $fr || $fe): ?>
                <a class="pn-btn" href="usuarios.php">Limpiar</a>
            <?php endif; ?>
        </form>

        <!-- Botones de creación y exportación PDF -->
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <a class="pn-btn principal" href="usuarios.php?nuevo=1">
                <i class="fa-solid fa-user-plus"></i> Nuevo usuario
            </a>

            <a class="pn-btn" href="exportar_usuarios_pdf.php<?= $filtros !== '' ? '?' . e($filtros) : '' ?>">
                <i class="fa-solid fa-file-pdf"></i> Exportar PDF
            </a>
        </div>
    </div>

    <?php if (!$lista): ?>
        <p class="pn-vacio">
            No hay usuarios<?= ($q !== '' || $fr || $fe)
                ? ' con ese filtro'
                : ' en tu empresa todavía' ?>.
        </p>
    <?php else: ?>
        <div class="pn-tabla-wrap">
            <table class="pn-tabla">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Documento</th>
                        <th>Rol</th>
                        <th>Registro</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($lista as $us):
                        $hab = (int) $us['id_estados'] === ESTADO_HABILITADO;
                        $yo_mismo = (string) $us['documento'] === (string) $yo['documento'];
                    ?>
                        <tr>
                            <td>
                                <b><?= e($us['nombre'] . ' ' . $us['apellido']) ?></b>
                                <?= $yo_mismo ? '<span class="pn-tag ok">Tú</span>' : '' ?>
                                <br>
                                <small><?= e($us['correo']) ?></small>
                            </td>

                            <td><?= e($us['documento']) ?></td>
                            <td><?= e($us['nombre_rol']) ?></td>
                            <td><?= e(date('d/m/Y', strtotime($us['fecha_registro']))) ?></td>
                            <td><?= etiqueta_estado((int) $us['id_estados'], $us['estado']) ?></td>

                            <td>
                                <div class="pn-acc">
                                    <a class="pn-btn" href="usuarios.php?editar=<?= e($us['documento']) ?>">
                                        <i class="fa-solid fa-pen"></i> Editar
                                    </a>

                                    <?php if (!$yo_mismo): ?>
                                        <form method="post"
                                              onsubmit="return confirm('<?= $hab
                                                  ? '¿Deshabilitar a este usuario?'
                                                  : '¿Habilitar a este usuario?' ?>')">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="documento" value="<?= e($us['documento']) ?>">
                                            <input type="hidden" name="volver" value="<?= e($filtros) ?>">
                                            <input type="hidden" name="accion"
                                                   value="<?= $hab ? 'deshabilitar' : 'habilitar' ?>">

                                            <button class="pn-btn" type="submit">
                                                <?= $hab ? 'Deshabilitar' : 'Habilitar' ?>
                                            </button>
                                        </form>

                                        <form method="post"
                                              onsubmit="return confirm('¿Eliminar a este usuario? No se puede deshacer.')">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="documento" value="<?= e($us['documento']) ?>">
                                            <input type="hidden" name="accion" value="borrar">

                                            <button class="pn-btn peligro" type="submit" aria-label="Eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($paginas > 1):
            $base = http_build_query(array_filter([
                'q' => $q,
                'rol' => $fr,
                'estado' => $fe
            ]));
        ?>
            <div class="pn-pie-form" style="justify-content:center;align-items:center">
                <?php if ($pg > 1): ?>
                    <a class="pn-btn" href="?<?= e($base . '&p=' . ($pg - 1)) ?>">
                        <i class="fa-solid fa-chevron-left"></i> Anterior
                    </a>
                <?php endif; ?>

                <span>Página <?= $pg ?> de <?= $paginas ?> · <?= $total ?> usuarios</span>

                <?php if ($pg < $paginas): ?>
                    <a class="pn-btn" href="?<?= e($base . '&p=' . ($pg + 1)) ?>">
                        Siguiente <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php panel_cerrar(); ?>
