<?php
// ============================================================
// RutaNexo — Comprar un plan
//   Landing → "Comprar" → login.php?plan=ID → este formulario.
//   Crea: empresa (En espera) + suscripción + pago, y deja la
//   solicitud lista para que el Super Administrador la valide.
// ============================================================
require_once 'config/conexion.php';
require_once 'includes/funciones.php';
require_once 'config/enlaces.php';
require_once 'includes/compras.php';

iniciar_sesion();
header('Cache-Control: no-store, no-cache, must-revalidate');

$plan_id = (int) ($_POST['plan'] ?? $_GET['plan'] ?? $_SESSION['plan_deseado'] ?? 0);
$u = exigir_sesion($plan_id ?: null);

$pdo = (new Database())->conectar();
$titulo_pagina      = 'Comprar plan';
$descripcion_pagina = 'Completa los datos de tu empresa para activar tu plan de RutaNexo.';
$css_extra          = ['css/style_compra.css'];
$pagina_activa      = '';

/** Muestra una página de aviso (sin formulario) con el mismo diseño. */
function aviso_compra(string $icono, string $titulo, string $texto, array $botones): void
{
    global $titulo_pagina, $descripcion_pagina, $css_extra, $pagina_activa;
    include __DIR__ . '/includes/head_publico.php';
    include __DIR__ . '/includes/navbar.php';
    echo '<main class="cp-aviso-pg"><div class="contenedor"><div class="cp-aviso">';
    echo '<span class="cp-aviso-ic"><i class="' . e($icono) . '"></i></span><h1>' . e($titulo) . '</h1><p>' . e($texto) . '</p><div class="cp-aviso-bot">';
    foreach ($botones as [$href, $txt, $clase]) {
        echo '<a class="btn ' . e($clase) . '" href="' . e($href) . '">' . e($txt) . '</a>';
    }
    echo '</div></div></div></main>';
    include __DIR__ . '/includes/footer.php';
    echo '<script src="js/landing.js"></script></body></html>';
    exit;
}

if (!$pdo) {
    aviso_compra('fa-solid fa-database', 'No hay conexión', 'No pudimos conectar con la base de datos. Revisa que MySQL esté encendido.', [['index.php', 'Ir al inicio', 'btn-borde']]);
}
if (($u['origen'] ?? '') === 'super_admin') {
    aviso_compra('fa-solid fa-user-shield', 'Eres Super Administrador', 'Las cuentas de Super Administrador no compran planes: los validan. Entra a las solicitudes para revisarlas.', [['admin/solicitudes.php', 'Ver solicitudes', 'btn-teal']]);
}

// Datos del usuario que compra (desde la BD, no desde la sesión)
$stmt = $pdo->prepare('SELECT documento, nombre, apellido, correo, telefono, id_rol, id_empresa FROM usuario WHERE documento = ?');
$stmt->execute([$u['documento']]);
$usuario = $stmt->fetch();
if (!$usuario) {
    header('Location: includes/logout.php');
    exit;
}

// ¿Ya tiene una empresa/solicitud? Se le muestra su compra (salvo que haya sido rechazada: entonces puede reintentar).
$reuso = null;   // empresa rechazada que se reutiliza en el nuevo intento
if ($usuario['id_empresa']) {
    $previa = compra_del_usuario($pdo, $usuario['documento']);
    if ($previa && (int) $previa['estado_suscripcion'] === ESTADO_ANULADO) {
        $reuso = $previa;
    } elseif ($previa) {
        header('Location: mi_compra.php?id=' . $previa['id_suscripcion']);
        exit;
    }
}

$plan = $plan_id ? plan_por_id($pdo, $plan_id) : null;
if (!$plan) {
    aviso_compra('fa-solid fa-box-open', 'Elige un plan', 'Primero escoge el plan que quieres comprar en la página de presentación.', [[url_landing('planes'), 'Ver planes', 'btn-teal']]);
}
$_SESSION['plan_deseado'] = $plan['id_plan'];

$gratis  = (float) $plan['precio'] <= 0;
$metodos = metodos_pago();
$ciudades = $pdo->query('SELECT id_ciudad, nombre FROM ciudad ORDER BY nombre')->fetchAll();

// Valores del formulario (se conservan si hay errores)
$v = [
    'nit' => '', 'razon_social' => '',
    'nombre_representante' => trim($usuario['nombre'] . ' ' . $usuario['apellido']),
    'documento_representante' => (string) $usuario['documento'],
    'correo_empresa' => $usuario['correo'], 'telefono_empresa' => (string) $usuario['telefono'],
    'direccion' => '', 'id_ciudad' => 37, 'metodo' => '', 'acepto' => '',
];
$errores = [];
$error_general = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) {
        if (isset($_POST[$k])) { $v[$k] = trim((string) $_POST[$k]); }
    }
    $v['id_ciudad'] = (int) $v['id_ciudad'];

    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error_general = 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.';
    } else {
        // --- Validaciones ---
        $nit = preg_replace('/[\s.]/', '', $v['nit']);
        if (!preg_match('/^\d{6,12}(-\d)?$/', $nit)) {
            $errores['nit'] = 'NIT no válido. Ejemplo: 900123456-7';
        }
        if (mb_strlen($v['razon_social']) < 3 || mb_strlen($v['razon_social']) > 150) {
            $errores['razon_social'] = 'Escribe la razón social (3 a 150 caracteres).';
        }
        if (!preg_match('/^[\p{L}][\p{L}\s.\'-]{2,149}$/u', $v['nombre_representante'])) {
            $errores['nombre_representante'] = 'Escribe el nombre completo del representante.';
        }
        if (!preg_match('/^\d{5,12}$/', $v['documento_representante'])) {
            $errores['documento_representante'] = 'Solo números, entre 5 y 12 dígitos.';
        }
        if (!filter_var($v['correo_empresa'], FILTER_VALIDATE_EMAIL) || mb_strlen($v['correo_empresa']) > 100) {
            $errores['correo_empresa'] = 'Escribe un correo válido.';
        }
        if ($v['telefono_empresa'] !== '' && !preg_match('/^[0-9+\s-]{7,20}$/', $v['telefono_empresa'])) {
            $errores['telefono_empresa'] = 'Teléfono no válido.';
        }
        if (mb_strlen($v['direccion']) < 5 || mb_strlen($v['direccion']) > 255) {
            $errores['direccion'] = 'Escribe la dirección de la empresa.';
        }
        if (!in_array($v['id_ciudad'], array_map('intval', array_column($ciudades, 'id_ciudad')), true)) {
            $errores['id_ciudad'] = 'Elige una ciudad.';
        }
        if (!$gratis && !isset($metodos[$v['metodo']])) {
            $errores['metodo'] = 'Elige cómo vas a pagar.';
        }
        if (empty($_POST['acepto'])) {
            $errores['acepto'] = 'Debes aceptar los términos para continuar.';
        }
        if (!isset($errores['nit'])) {
            $s = $pdo->prepare('SELECT 1 FROM empresa WHERE nit = ? AND id_empresa <> ?');
            $s->execute([$nit, $reuso['id_empresa'] ?? 0]);
            if ($s->fetchColumn()) {
                $errores['nit'] = 'Ya existe una empresa registrada con este NIT.';
            }
        }

        // --- Cámara de comercio ---
        $archivo = null;
        if (!$errores) {
            $archivo = guardar_archivo($_FILES['camara_comercio'] ?? null, 'camara');
            if (!$archivo['ok']) {
                $errores['camara_comercio'] = $archivo['error'];
            }
        } elseif (empty($_FILES['camara_comercio']['name'])) {
            $errores['camara_comercio'] = 'Adjunta la cámara de comercio (PDF, JPG o PNG, máx. 5 MB).';
        } else {
            // Hay otros errores: se avisa que el archivo debe volver a seleccionarse
            $errores['camara_comercio'] = 'Vuelve a seleccionar el archivo (PDF, JPG o PNG, máx. 5 MB).';
        }

        // --- Guardar ---
        if (!$errores) {
            $metodo = $gratis ? 'gratuito' : $v['metodo'];
            try {
                $pdo->beginTransaction();

                $datos_empresa = [$nit, $v['razon_social'], $v['nombre_representante'], $v['documento_representante'],
                                  $v['correo_empresa'], $v['telefono_empresa'] ?: null, $v['direccion'], $v['id_ciudad'],
                                  $archivo['nombre'], PAGO_ESTADO_ESPERA];
                if ($reuso) {
                    $pdo->prepare('UPDATE empresa SET nit=?, razon_social=?, nombre_representante=?, documento_representante=?, correo=?, telefono=?, direccion=?, id_ciudad=?, camara_comercio=?, id_estados=? WHERE id_empresa=?')
                        ->execute([...$datos_empresa, $reuso['id_empresa']]);
                    $id_empresa = (int) $reuso['id_empresa'];
                } else {
                    $pdo->prepare('INSERT INTO empresa (nit, razon_social, nombre_representante, documento_representante, correo, telefono, direccion, id_ciudad, camara_comercio, id_estados)
                                   VALUES (?,?,?,?,?,?,?,?,?,?)')
                        ->execute($datos_empresa);
                    $id_empresa = (int) $pdo->lastInsertId();
                }

                $pdo->prepare('UPDATE usuario SET nit = ?, id_empresa = ? WHERE documento = ?')
                    ->execute([$nit, $id_empresa, $usuario['documento']]);

                $pdo->prepare('INSERT INTO suscripcion (id_empresa, id_plan, valor_pagado, id_estados) VALUES (?,?,0,?)')
                    ->execute([$id_empresa, $plan['id_plan'], PAGO_ESTADO_ESPERA]);
                $id_susc = (int) $pdo->lastInsertId();

                $ref = generar_referencia();
                $ins = $pdo->prepare('INSERT INTO pago (id_suscripcion, metodo, referencia, valor, id_estados, fecha_vencimiento, fecha_pago)
                                      VALUES (?,?,?,?,?,?,?)');
                $vence = (!$gratis) ? date('Y-m-d H:i:s', time() + PAGO_DIAS_VIGENCIA_REFERENCIA * 86400) : null;
                for ($i = 0; ; $i++) {
                    try {
                        $ins->execute([$id_susc, $metodo, $ref, $plan['precio'],
                                       $gratis ? PAGO_ESTADO_REALIZADO : PAGO_ESTADO_ESPERA, $vence,
                                       $gratis ? date('Y-m-d H:i:s') : null]);
                        break;
                    } catch (PDOException $e) {
                        if ($i >= 4 || $e->getCode() !== '23000') { throw $e; }
                        $ref = generar_referencia();   // referencia repetida: se genera otra
                    }
                }
                $pdo->commit();
                if ($reuso) { borrar_archivo($reuso['camara_comercio']); }   // el certificado anterior ya no se usa

                unset($_SESSION['plan_deseado']);
                header('Location: mi_compra.php?id=' . $id_susc . '&nuevo=1');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                borrar_archivo($archivo['nombre'] ?? null);
                error_log('Error comprar.php: ' . $e->getMessage());
                $error_general = 'No pudimos registrar tu solicitud. Intenta de nuevo en unos minutos.';
            }
        }
    }
}

include 'includes/head_publico.php';
include 'includes/navbar.php';

/** Atributos comunes de un campo + mensaje de error. */
function campo_err(array $errores, string $k): string
{
    return isset($errores[$k]) ? '<small class="cp-err">' . e($errores[$k]) . '</small>' : '';
}
?>
<main>
    <section class="pg-cab">
        <div class="contenedor">
            <nav class="migas" aria-label="Migas de pan"><a href="<?= e(url_landing('planes')) ?>">Planes</a><span>/</span><span>Comprar</span></nav>
            <span class="etiqueta">Paso final</span>
            <h1>Activa tu plan <span class="acento"><?= e($plan['nombre']) ?></span></h1>
            <p class="intro">Completa los datos de tu empresa. Un Super Administrador validará tu cámara de comercio y tu pago para habilitar el plan.</p>
        </div>
    </section>

    <section class="cp-pg">
        <div class="contenedor">
            <?php if ($error_general): ?><div class="cp-alerta" role="alert"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($error_general) ?></div><?php endif; ?>
            <?php if ($errores && !$error_general): ?><div class="cp-alerta" role="alert"><i class="fa-solid fa-triangle-exclamation"></i> Revisa los campos marcados.</div><?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="cp-grid" novalidate id="formCompra">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="plan" value="<?= (int) $plan['id_plan'] ?>">

                <div class="cp-col">
                    <!-- 1. Tus datos -->
                    <fieldset class="cp-card">
                        <legend><span class="cp-n">1</span> Tus datos</legend>
                        <p class="cp-ayuda">Son los de tu cuenta. Quedas como representante de la empresa.</p>
                        <div class="cp-campos dos">
                            <div class="cp-campo"><label>Documento</label><input type="text" value="<?= e($usuario['documento']) ?>" readonly></div>
                            <div class="cp-campo"><label>Nombre</label><input type="text" value="<?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?>" readonly></div>
                            <div class="cp-campo"><label>Correo</label><input type="text" value="<?= e($usuario['correo']) ?>" readonly></div>
                            <div class="cp-campo"><label>Teléfono</label><input type="text" value="<?= e($usuario['telefono'] ?: '—') ?>" readonly></div>
                        </div>
                    </fieldset>

                    <!-- 2. Empresa -->
                    <fieldset class="cp-card">
                        <legend><span class="cp-n">2</span> Datos de la empresa</legend>
                        <div class="cp-campos dos">
                            <div class="cp-campo<?= isset($errores['nit']) ? ' mal' : '' ?>">
                                <label for="nit">NIT *</label>
                                <input type="text" id="nit" name="nit" maxlength="14" inputmode="numeric" placeholder="900123456-7" value="<?= e($v['nit']) ?>" required>
                                <?= campo_err($errores, 'nit') ?>
                            </div>
                            <div class="cp-campo<?= isset($errores['razon_social']) ? ' mal' : '' ?>">
                                <label for="razon_social">Razón social *</label>
                                <input type="text" id="razon_social" name="razon_social" maxlength="150" placeholder="Cooperativa de Transportes…" value="<?= e($v['razon_social']) ?>" required>
                                <?= campo_err($errores, 'razon_social') ?>
                            </div>
                            <div class="cp-campo<?= isset($errores['nombre_representante']) ? ' mal' : '' ?>">
                                <label for="nombre_representante">Representante legal *</label>
                                <input type="text" id="nombre_representante" name="nombre_representante" maxlength="150" value="<?= e($v['nombre_representante']) ?>" required>
                                <?= campo_err($errores, 'nombre_representante') ?>
                            </div>
                            <div class="cp-campo<?= isset($errores['documento_representante']) ? ' mal' : '' ?>">
                                <label for="documento_representante">Documento del representante *</label>
                                <input type="text" id="documento_representante" name="documento_representante" maxlength="12" inputmode="numeric" value="<?= e($v['documento_representante']) ?>" required>
                                <?= campo_err($errores, 'documento_representante') ?>
                            </div>
                            <div class="cp-campo<?= isset($errores['correo_empresa']) ? ' mal' : '' ?>">
                                <label for="correo_empresa">Correo de la empresa *</label>
                                <input type="email" id="correo_empresa" name="correo_empresa" maxlength="100" value="<?= e($v['correo_empresa']) ?>" required>
                                <?= campo_err($errores, 'correo_empresa') ?>
                            </div>
                            <div class="cp-campo<?= isset($errores['telefono_empresa']) ? ' mal' : '' ?>">
                                <label for="telefono_empresa">Teléfono</label>
                                <input type="tel" id="telefono_empresa" name="telefono_empresa" maxlength="20" value="<?= e($v['telefono_empresa']) ?>">
                                <?= campo_err($errores, 'telefono_empresa') ?>
                            </div>
                            <div class="cp-campo<?= isset($errores['direccion']) ? ' mal' : '' ?>">
                                <label for="direccion">Dirección *</label>
                                <input type="text" id="direccion" name="direccion" maxlength="255" placeholder="Calle 10 # 5-20" value="<?= e($v['direccion']) ?>" required>
                                <?= campo_err($errores, 'direccion') ?>
                            </div>
                            <div class="cp-campo<?= isset($errores['id_ciudad']) ? ' mal' : '' ?>">
                                <label for="id_ciudad">Ciudad *</label>
                                <select id="id_ciudad" name="id_ciudad" required>
                                    <?php foreach ($ciudades as $c): ?>
                                        <option value="<?= (int) $c['id_ciudad'] ?>"<?= (int) $c['id_ciudad'] === $v['id_ciudad'] ? ' selected' : '' ?>><?= e($c['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= campo_err($errores, 'id_ciudad') ?>
                            </div>
                        </div>
                    </fieldset>

                    <!-- 3. Cámara de comercio -->
                    <fieldset class="cp-card">
                        <legend><span class="cp-n">3</span> Cámara de comercio</legend>
                        <p class="cp-ayuda">Sube el certificado de existencia y representación legal (reciente). PDF, JPG o PNG, máximo 5 MB.</p>
                        <div class="cp-campo<?= isset($errores['camara_comercio']) ? ' mal' : '' ?>">
                            <label class="cp-subir" for="camara_comercio" id="subirEtiqueta">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span><b id="subirNombre">Elegir archivo</b><small id="subirInfo">o arrástralo aquí</small></span>
                            </label>
                            <input type="file" id="camara_comercio" name="camara_comercio" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="cp-file">
                            <?= campo_err($errores, 'camara_comercio') ?>
                        </div>
                    </fieldset>

                    <!-- 4. Pago -->
                    <fieldset class="cp-card">
                        <legend><span class="cp-n">4</span> Forma de pago</legend>
                        <?php if ($gratis): ?>
                            <div class="cp-gratis"><i class="fa-solid fa-gift"></i><span>El plan <b><?= e($plan['nombre']) ?></b> no tiene costo: no necesitas pagar nada. Solo validamos tu empresa.</span></div>
                        <?php else: ?>
                            <div class="cp-metodos <?= isset($errores['metodo']) ? 'mal' : '' ?>">
                                <?php foreach ($metodos as $clave => $m): ?>
                                    <label class="cp-metodo">
                                        <input type="radio" name="metodo" value="<?= e($clave) ?>"<?= $v['metodo'] === $clave ? ' checked' : '' ?>>
                                        <span class="cp-metodo-in">
                                            <i class="<?= e($m['icono']) ?>"></i>
                                            <span><b><?= e($m['nombre']) ?></b><small><?= e($m['texto']) ?></small></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?= campo_err($errores, 'metodo') ?>
                        <?php endif; ?>
                    </fieldset>

                    <label class="cp-acepto<?= isset($errores['acepto']) ? ' mal' : '' ?>">
                        <input type="checkbox" name="acepto" value="1"<?= !empty($_POST['acepto']) ? ' checked' : '' ?>>
                        <span>Confirmo que los datos son verdaderos y acepto los términos del servicio y el tratamiento de mis datos personales.</span>
                    </label>
                    <a href="#" class="ck-link" data-abrir-tratamiento><i class="fa-solid fa-shield-halved"></i> Conocer el tratamiento de datos</a>
                    <?= campo_err($errores, 'acepto') ?>
                </div>

                <!-- Resumen -->
                <aside class="cp-resumen">
                    <div class="cp-resumen-in">
                        <span class="cp-resumen-et">Resumen de tu compra</span>
                        <h2>Plan <?= e($plan['nombre']) ?></h2>
                        <p class="cp-resumen-desc"><?= e($plan['descripcion']) ?></p>
                        <div class="cp-precio"><?= $gratis ? 'Gratis' : dinero($plan['precio']) ?><?php if (!$gratis): ?><small>COP</small><?php endif; ?></div>
                        <dl class="cp-datos">
                            <div><dt>Vigencia</dt><dd><?= $plan['duracion_dias'] >= 365 ? round($plan['duracion_dias'] / 365) . ($plan['duracion_dias'] >= 730 ? ' años' : ' año') : (int) $plan['duracion_dias'] . ' días' ?></dd></div>
                            <div><dt>Vehículos</dt><dd><?= $plan['limite_vehiculos'] === null ? 'Ilimitados' : 'Hasta ' . (int) $plan['limite_vehiculos'] ?></dd></div>
                        </dl>
                        <ul class="cp-lista">
                            <?php foreach ($plan['caracteristicas'] as $c): ?>
                                <li><i class="fa-solid fa-check"></i> <?= e(texto_caracteristica($c)) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="submit" class="btn btn-teal cp-enviar" id="btnEnviar">
                            <?= $gratis ? 'Enviar solicitud' : 'Confirmar compra' ?> <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <p class="cp-nota"><i class="fa-solid fa-lock"></i> Tus documentos se guardan de forma privada y solo los ve el Super Administrador.</p>
                        <a class="cp-cambiar" href="<?= e(url_landing('planes')) ?>">Cambiar de plan</a>
                    </div>
                </aside>
            </form>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/landing.js?v=<?= filemtime(__DIR__ . '/js/landing.js') ?>"></script>
<script src="js/comprar.js?v=<?= filemtime(__DIR__ . '/js/comprar.js') ?>"></script>
</body>
</html>
