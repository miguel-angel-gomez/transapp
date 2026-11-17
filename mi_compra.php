<?php
// ============================================================
// RutaNexo — Estado de mi compra
//   Muestra el avance (solicitud → pago → validación → plan activo),
//   las instrucciones de pago según el método y permite subir el
//   comprobante o simular el pago PSE (demostración).
// ============================================================
require_once 'config/conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/compras.php';

iniciar_sesion();
header('Cache-Control: no-store, no-cache, must-revalidate');
$u = exigir_sesion();

$pdo = (new Database())->conectar();
if (!$pdo) { http_response_code(503); exit('No hay conexión con la base de datos.'); }
if (($u['origen'] ?? '') === 'super_admin') { header('Location: admin/solicitudes.php'); exit; }

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$c  = $id ? compra_por_id($pdo, $id) : compra_del_usuario($pdo, $u['documento']);
if (!$c || !puede_ver_compra($pdo, $u, $c)) {
    header('Location: comprar.php');
    exit;
}
$id = (int) $c['id_suscripcion'];
$metodos = metodos_pago();
$m = $metodos[$c['metodo']] ?? ['nombre' => 'Sin costo', 'icono' => 'fa-solid fa-gift', 'tipo' => 'gratuito', 'texto' => ''];

$aviso = ''; $error = '';
$BANCOS = ['Bancolombia', 'Banco de Bogotá', 'Davivienda', 'BBVA', 'Banco de Occidente', 'Banco Popular', 'Banco AV Villas', 'Nequi'];

// ------------------------------------------------------------
// Acciones
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string) ($_POST['accion'] ?? '');
    $abierta = (int) $c['estado_suscripcion'] === PAGO_ESTADO_ESPERA;

    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'La sesión del formulario expiró. Recarga la página.';
    } elseif (!$abierta) {
        $error = 'Esta solicitud ya fue resuelta.';
    } elseif ($accion === 'comprobante' && $m['tipo'] === 'transferencia' && (int) $c['estado_pago'] !== PAGO_ESTADO_REALIZADO) {
        $r = guardar_archivo($_FILES['comprobante'] ?? null, 'comprobante');
        if (!$r['ok']) {
            $error = $r['error'];
        } else {
            $pdo->prepare('UPDATE pago SET comprobante = ?, id_estados = ? WHERE id_pago = ?')
                ->execute([$r['nombre'], PAGO_ESTADO_TRAMITE, $c['id_pago']]);
            borrar_archivo($c['comprobante']);
            $aviso = 'Recibimos tu comprobante. Un Super Administrador lo verificará pronto.';
        }
    } elseif (($accion === 'pse_ok' || $accion === 'pse_mal') && $m['tipo'] === 'pasarela' && (int) $c['estado_pago'] === PAGO_ESTADO_ESPERA) {
        // SIMULACIÓN de la pasarela PSE (para la demostración)
        if (!in_array((string) ($_POST['banco'] ?? ''), $BANCOS, true)) {
            $error = 'Elige tu banco.';
        } elseif ($accion === 'pse_ok') {
            $pdo->prepare('UPDATE pago SET id_estados = ?, fecha_pago = NOW(), observacion = ? WHERE id_pago = ?')
                ->execute([PAGO_ESTADO_REALIZADO, 'PSE simulado — ' . $_POST['banco'], $c['id_pago']]);
            $aviso = '¡Pago recibido! Ahora el Super Administrador validará tu cámara de comercio.';
        } else {
            $error = 'El banco rechazó la transacción (simulación). Puedes intentarlo de nuevo.';
        }
    }
    $c = compra_por_id($pdo, $id);
}

if (!empty($_GET['nuevo']) && !$aviso && !$error) {
    $aviso = '¡Solicitud enviada! Guarda tu referencia de pago.';
}

// ------------------------------------------------------------
// Estado general
// ------------------------------------------------------------
$susp  = (int) $c['estado_suscripcion'];
$pago  = (int) $c['estado_pago'];
$vigente  = $susp === ESTADO_VIGENTE;
$rechazada = $susp === ESTADO_ANULADO;
$pagado   = $pago === PAGO_ESTADO_REALIZADO;
// Pasos de la línea de tiempo: 0 pendiente, 1 actual, 2 hecho
$pasos = [
    ['Solicitud enviada', 'fa-file-circle-check', 2],
    ['Pago', 'fa-wallet', $pagado ? 2 : ($rechazada ? 0 : 1)],
    ['Validación', 'fa-user-shield', $vigente ? 2 : ($pagado && !$rechazada ? 1 : 0)],
    ['Plan activo', 'fa-circle-check', $vigente ? 2 : 0],
];
[$txt_estado, $cls_estado] = $vigente ? ['Plan activo', 'ok'] : ($rechazada ? ['Rechazada', 'mal'] : ($pagado ? ['En validación', 'tra'] : texto_estado($pago)));

$titulo_pagina      = 'Mi compra';
$descripcion_pagina = 'Estado de tu compra del plan de RutaNexo.';
$css_extra          = ['css/style_compra.css'];
$pagina_activa      = '';
include 'includes/head_publico.php';
include 'includes/navbar.php';
?>
<main>
    <section class="pg-cab">
        <div class="contenedor">
            <nav class="migas" aria-label="Migas de pan"><a href="dash.php">Mi panel</a><span>/</span><span>Mi compra</span></nav>
            <span class="etiqueta">Compra #<?= (int) $c['id_suscripcion'] ?></span>
            <h1>Plan <span class="acento"><?= e($c['plan']) ?></span> · <?= e($c['razon_social']) ?></h1>
            <p class="intro"><span class="cp-badge <?= e($cls_estado) ?>"><?= e($txt_estado) ?></span></p>
        </div>
    </section>

    <section class="cp-pg">
        <div class="contenedor">
            <?php if ($aviso): ?><div class="cp-alerta ok" role="status"><i class="fa-solid fa-circle-check"></i> <?= e($aviso) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="cp-alerta" role="alert"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>

            <ol class="cp-pasos" aria-label="Avance de la compra">
                <?php foreach ($pasos as $i => [$t, $ic, $est]): ?>
                    <li class="<?= $est === 2 ? 'hecho' : ($est === 1 ? 'actual' : '') ?>">
                        <span class="cp-paso-ic"><i class="fa-solid <?= $est === 2 ? 'fa-check' : $ic ?>"></i></span>
                        <b><?= e($t) ?></b>
                    </li>
                <?php endforeach; ?>
            </ol>

            <div class="cp-grid">
                <div class="cp-col">
                    <?php if ($vigente): ?>
                        <div class="cp-card cp-exito">
                            <h2><i class="fa-solid fa-circle-check"></i> ¡Tu plan está activo!</h2>
                            <p>Ya eres <b>Administrador</b> de <?= e($c['razon_social']) ?>. Tu plan tiene acceso a:</p>
                            <ul class="cp-lista dos">
                                <?php foreach ($c['caracteristicas'] as $car): ?><li><i class="fa-solid fa-check"></i> <?= e(texto_caracteristica($car)) ?></li><?php endforeach; ?>
                            </ul>
                            <p class="cp-ayuda">Vigente desde <b><?= e(date('d/m/Y', strtotime($c['fecha_inicio']))) ?></b> hasta <b><?= e(date('d/m/Y', strtotime($c['fecha_fin']))) ?></b>.
                                Cierra sesión y vuelve a entrar para ver tu nuevo rol.</p>
                            <a class="btn btn-teal" href="dash.php">Ir a mi panel <i class="fa-solid fa-arrow-right"></i></a>
                        </div>

                    <?php elseif ($rechazada): ?>
                        <div class="cp-card cp-rechazo">
                            <h2><i class="fa-solid fa-circle-xmark"></i> Solicitud rechazada</h2>
                            <p><?= e($c['observacion'] ?: 'El Super Administrador no pudo validar tu solicitud.') ?></p>
                            <p class="cp-ayuda">Puedes corregir los datos y volver a enviarla.</p>
                            <a class="btn btn-teal" href="comprar.php?plan=<?= (int) $c['id_plan'] ?>">Volver a enviar la solicitud</a>
                        </div>

                    <?php else: ?>
                        <!-- ===== Instrucciones de pago ===== -->
                        <div class="cp-card">
                            <h2><i class="<?= e($m['icono']) ?>"></i> <?= e($m['nombre']) ?></h2>

                            <?php if ($m['tipo'] === 'gratuito'): ?>
                                <p>Tu plan no tiene costo. Solo falta que el Super Administrador valide tu cámara de comercio.</p>

                            <?php elseif ($pagado): ?>
                                <p><i class="fa-solid fa-circle-check" style="color:var(--teal-d)"></i> Pago registrado. Ahora el Super Administrador valida tu cámara de comercio; te avisaremos aquí cuando tu plan quede activo.</p>

                            <?php elseif ($m['tipo'] === 'pasarela'): ?>
                                <p>Serás redirigido a tu banco para pagar <b><?= e(dinero($c['valor'])) ?></b>.</p>
                                <div class="cp-sim">
                                    <span class="cp-sim-et"><i class="fa-solid fa-flask"></i> Pasarela de pruebas — simulación para la demostración</span>
                                    <form method="post" class="cp-sim-form">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <label for="banco">Elige tu banco</label>
                                        <select name="banco" id="banco" required>
                                            <option value="">Selecciona…</option>
                                            <?php foreach ($BANCOS as $b): ?><option><?= e($b) ?></option><?php endforeach; ?>
                                        </select>
                                        <div class="cp-sim-bot">
                                            <button name="accion" value="pse_ok" class="btn btn-teal">Pagar <?= e(dinero($c['valor'])) ?></button>
                                            <button name="accion" value="pse_mal" class="btn btn-borde" formnovalidate>Simular rechazo</button>
                                        </div>
                                    </form>
                                </div>

                            <?php else: ?>
                                <p><?= e($m['texto']) ?></p>
                                <div class="cp-ref">
                                    <span>Tu referencia de pago</span>
                                    <b id="refTexto"><?= e($c['referencia']) ?></b>
                                    <button type="button" class="btn btn-borde cp-copiar" data-copiar="<?= e($c['referencia']) ?>"><i class="fa-regular fa-copy"></i> Copiar</button>
                                </div>
                                <dl class="cp-datos grande">
                                    <div><dt>Valor a pagar</dt><dd><?= e(dinero($c['valor'])) ?></dd></div>
                                    <?php foreach ($m['datos'] ?? [] as $k => $val): ?>
                                        <div><dt><?= e($k) ?></dt><dd><?= e($val) ?></dd></div>
                                    <?php endforeach; ?>
                                    <?php if ($c['fecha_vencimiento']): ?>
                                        <div><dt>Paga antes del</dt><dd><?= e(date('d/m/Y', strtotime($c['fecha_vencimiento']))) ?></dd></div>
                                    <?php endif; ?>
                                </dl>
                                <?php if ($m['tipo'] === 'transferencia'): ?>
                                    <p class="cp-ayuda">Escribe la referencia en la descripción de la transferencia.</p>
                                    <?php if ($pago === PAGO_ESTADO_TRAMITE): ?>
                                        <p class="cp-ok-linea"><i class="fa-solid fa-hourglass-half"></i> Comprobante enviado, en verificación.
                                            <a href="archivo.php?t=comprobante&id=<?= $id ?>" target="_blank" rel="noopener">Ver comprobante</a></p>
                                    <?php endif; ?>
                                    <form method="post" enctype="multipart/form-data" class="cp-comprobante">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <input type="hidden" name="accion" value="comprobante">
                                        <label class="cp-subir" for="comprobante">
                                            <i class="fa-solid fa-receipt"></i>
                                            <span><b id="subirNombre"><?= $pago === PAGO_ESTADO_TRAMITE ? 'Cambiar comprobante' : 'Subir comprobante' ?></b><small id="subirInfo">PDF, JPG o PNG, máx. 5 MB</small></span>
                                        </label>
                                        <input type="file" id="comprobante" name="comprobante" class="cp-file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                                        <button class="btn btn-teal" type="submit">Enviar comprobante</button>
                                    </form>
                                <?php else: ?>
                                    <p class="cp-ayuda">Cuando pagues, el Super Administrador confirmará el pago con tu referencia y activará tu plan.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="cp-resumen">
                    <div class="cp-resumen-in">
                        <span class="cp-resumen-et">Resumen</span>
                        <h2>Plan <?= e($c['plan']) ?></h2>
                        <div class="cp-precio"><?= (float) $c['precio'] > 0 ? e(dinero($c['precio'])) : 'Gratis' ?><?php if ((float) $c['precio'] > 0): ?><small>COP</small><?php endif; ?></div>
                        <dl class="cp-datos">
                            <div><dt>Empresa</dt><dd><?= e($c['razon_social']) ?></dd></div>
                            <div><dt>NIT</dt><dd><?= e($c['nit']) ?></dd></div>
                            <div><dt>Método</dt><dd><?= e($m['nombre']) ?></dd></div>
                            <div><dt>Referencia</dt><dd><?= e($c['referencia']) ?></dd></div>
                            <div><dt>Fecha</dt><dd><?= e(date('d/m/Y H:i', strtotime($c['fecha_compra']))) ?></dd></div>
                        </dl>
                        <a class="cp-cambiar" href="dash.php">Volver a mi panel</a>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/landing.js?v=<?= filemtime(__DIR__ . '/js/landing.js') ?>"></script>
<script src="js/comprar.js?v=<?= filemtime(__DIR__ . '/js/comprar.js') ?>"></script>
</body>
</html>
