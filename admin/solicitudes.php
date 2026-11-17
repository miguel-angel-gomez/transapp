<?php
// ============================================================
// RutaNexo — Super Administrador: solicitudes de planes
//   Lista las compras, permite revisar la cámara de comercio y el
//   pago, y aprobar o rechazar la solicitud.
// ============================================================
require_once __DIR__ . '/_panel.php';
require_once __DIR__ . '/../includes/compras.php';

$flash = ['ok' => '', 'mal' => ''];

// ------------------------------------------------------------
// Decisión (POST)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $accion = (string) ($_POST['accion'] ?? '');
    $c = $id ? compra_por_id($pdo, $id) : null;
    if (!csrf_valido($_POST['csrf'] ?? null) || !$c) {
        $flash['mal'] = 'La solicitud no existe o la sesión expiró.';
    } elseif ($accion === 'aprobar') {
        if ((int) $c['estado_pago'] !== PAGO_ESTADO_REALIZADO && empty($_POST['confirmo_pago'])) {
            $flash['mal'] = 'Marca la casilla para confirmar que recibiste el pago.';
        } elseif (aprobar_compra($pdo, $id, (int) $u['documento'])) {
            header('Location: solicitudes.php?id=' . $id . '&m=aprobada'); exit;
        } else {
            $flash['mal'] = 'No se pudo aprobar: falta el pago o la solicitud ya fue resuelta.';
        }
    } elseif ($accion === 'rechazar') {
        $motivo = trim((string) ($_POST['motivo'] ?? ''));
        if (mb_strlen($motivo) < 5) {
            $flash['mal'] = 'Escribe el motivo del rechazo (el cliente lo verá).';
        } elseif (rechazar_compra($pdo, $id, (int) $u['documento'], $motivo)) {
            header('Location: solicitudes.php?id=' . $id . '&m=rechazada'); exit;
        } else {
            $flash['mal'] = 'No se pudo rechazar: la solicitud ya fue resuelta.';
        }
    }
    $_GET['id'] = $id;
}
if (($_GET['m'] ?? '') === 'aprobada') { $flash['ok'] = 'Solicitud aprobada: el plan quedó activo y el comprador es ahora Administrador.'; }
if (($_GET['m'] ?? '') === 'rechazada') { $flash['ok'] = 'Solicitud rechazada. El cliente verá el motivo.'; }

$metodos = metodos_pago();
$css_extra = ['css/style_compra.css'];

$detalle = !empty($_GET['id']) ? compra_por_id($pdo, (int) $_GET['id']) : null;

// ------------------------------------------------------------
// Lista
// ------------------------------------------------------------
$filtro = $_GET['f'] ?? 'pendientes';
$mapa_filtro = ['pendientes' => [PAGO_ESTADO_ESPERA], 'activas' => [ESTADO_VIGENTE], 'rechazadas' => [ESTADO_ANULADO], 'todas' => null];
if (!array_key_exists($filtro, $mapa_filtro)) { $filtro = 'pendientes'; }

$cuentas = ['pendientes' => 0, 'activas' => 0, 'rechazadas' => 0, 'todas' => 0];
foreach ($pdo->query('SELECT id_estados, COUNT(*) n FROM suscripcion GROUP BY id_estados')->fetchAll() as $r) {
    $cuentas['todas'] += $r['n'];
    if ((int) $r['id_estados'] === PAGO_ESTADO_ESPERA) { $cuentas['pendientes'] += $r['n']; }
    if ((int) $r['id_estados'] === ESTADO_VIGENTE) { $cuentas['activas'] += $r['n']; }
    if ((int) $r['id_estados'] === ESTADO_ANULADO) { $cuentas['rechazadas'] += $r['n']; }
}

$sql = "SELECT s.id_suscripcion, s.fecha_compra, s.id_estados AS estado_suscripcion,
               e.razon_social, e.nit, p.nombre AS plan, g.metodo, g.valor, g.id_estados AS estado_pago
          FROM suscripcion s
          JOIN empresa e ON e.id_empresa = s.id_empresa
          JOIN plan_suscripcion p ON p.id_plan = s.id_plan
          LEFT JOIN pago g ON g.id_suscripcion = s.id_suscripcion";
$par = [];
if ($mapa_filtro[$filtro] !== null) { $sql .= ' WHERE s.id_estados = ?'; $par[] = $mapa_filtro[$filtro][0]; }
$lista = $pdo->prepare($sql . ' ORDER BY s.id_suscripcion DESC');
$lista->execute($par);
$lista = $lista->fetchAll();

panel_abrir('solicitudes', 'Solicitudes de planes', 'Revisa la cámara de comercio y el pago de cada empresa. Al aprobar, el plan queda activo y el comprador pasa a ser Administrador.');
?>
<div class="cp-pg">
    <div class="contenedor">
            <?php if ($flash['ok']): ?><div class="cp-alerta ok" role="status"><i class="fa-solid fa-circle-check"></i> <?= e($flash['ok']) ?></div><?php endif; ?>
            <?php if ($flash['mal']): ?><div class="cp-alerta" role="alert"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($flash['mal']) ?></div><?php endif; ?>

            <?php if ($detalle):
                $c = $detalle;
                $m = $metodos[$c['metodo']] ?? ['nombre' => 'Sin costo', 'icono' => 'fa-solid fa-gift', 'tipo' => 'gratuito'];
                $abierta = (int) $c['estado_suscripcion'] === PAGO_ESTADO_ESPERA;
                $pago_ok = (int) $c['estado_pago'] === PAGO_ESTADO_REALIZADO;
                [$te, $ce] = texto_estado((int) $c['estado_suscripcion']);
                [$tp, $cpg] = texto_estado((int) $c['estado_pago']);
                $es_img = $c['camara_comercio'] && preg_match('/\.(jpg|png)$/', $c['camara_comercio']);
            ?>
                <a class="cp-volver" href="solicitudes.php"><i class="fa-solid fa-arrow-left"></i> Volver a la lista</a>
                <div class="cp-grid">
                    <div class="cp-col">
                        <div class="cp-card">
                            <h2><i class="fa-solid fa-building"></i> <?= e($c['razon_social']) ?> <span class="cp-badge <?= e($ce) ?>"><?= e($te) ?></span></h2>
                            <dl class="cp-datos grande">
                                <div><dt>NIT</dt><dd><?= e($c['nit']) ?></dd></div>
                                <div><dt>Representante</dt><dd><?= e($c['nombre_representante']) ?></dd></div>
                                <div><dt>Documento</dt><dd><?= e($c['documento_representante']) ?></dd></div>
                                <div><dt>Correo</dt><dd><?= e($c['correo_empresa']) ?></dd></div>
                                <div><dt>Teléfono</dt><dd><?= e($c['telefono_empresa'] ?: '—') ?></dd></div>
                                <div><dt>Dirección</dt><dd><?= e($c['direccion']) ?>, <?= e($c['ciudad']) ?></dd></div>
                                <div><dt>Comprador (usuario)</dt><dd><?= e($c['comprador']) ?> · <?= e($c['doc_comprador']) ?></dd></div>
                            </dl>
                        </div>

                        <div class="cp-card">
                            <h2><i class="fa-solid fa-file-shield"></i> Cámara de comercio</h2>
                            <?php if ($c['camara_comercio']): ?>
                                <?php if ($es_img): ?>
                                    <a href="../archivo.php?t=camara&id=<?= (int) $c['id_suscripcion'] ?>" target="_blank" rel="noopener"><img class="cp-vista" src="../archivo.php?t=camara&id=<?= (int) $c['id_suscripcion'] ?>" alt="Cámara de comercio"></a>
                                <?php endif; ?>
                                <a class="btn btn-borde" href="../archivo.php?t=camara&id=<?= (int) $c['id_suscripcion'] ?>" target="_blank" rel="noopener"><i class="fa-solid fa-up-right-from-square"></i> Abrir documento</a>
                            <?php else: ?><p class="cp-ayuda">No hay archivo.</p><?php endif; ?>
                        </div>

                        <div class="cp-card">
                            <h2><i class="<?= e($m['icono']) ?>"></i> Pago — <?= e($m['nombre']) ?> <span class="cp-badge <?= e($cpg) ?>"><?= e($tp) ?></span></h2>
                            <dl class="cp-datos grande">
                                <div><dt>Referencia</dt><dd><?= e($c['referencia']) ?></dd></div>
                                <div><dt>Valor</dt><dd><?= e(dinero($c['valor'])) ?></dd></div>
                                <?php if ($c['fecha_vencimiento']): ?><div><dt>Vence</dt><dd><?= e(date('d/m/Y', strtotime($c['fecha_vencimiento']))) ?></dd></div><?php endif; ?>
                                <?php if ($c['fecha_pago']): ?><div><dt>Pagado el</dt><dd><?= e(date('d/m/Y H:i', strtotime($c['fecha_pago']))) ?></dd></div><?php endif; ?>
                                <?php if ($c['observacion']): ?><div><dt>Nota</dt><dd><?= e($c['observacion']) ?></dd></div><?php endif; ?>
                            </dl>
                            <?php if ($c['comprobante']): ?>
                                <a class="btn btn-borde" href="../archivo.php?t=comprobante&id=<?= (int) $c['id_suscripcion'] ?>" target="_blank" rel="noopener"><i class="fa-solid fa-receipt"></i> Ver comprobante</a>
                            <?php elseif ($m['tipo'] === 'transferencia' && !$pago_ok): ?>
                                <p class="cp-ayuda">El cliente aún no sube el comprobante.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <aside class="cp-resumen">
                        <div class="cp-resumen-in">
                            <span class="cp-resumen-et">Plan solicitado</span>
                            <h2>Plan <?= e($c['plan']) ?></h2>
                            <div class="cp-precio"><?= (float) $c['precio'] > 0 ? e(dinero($c['precio'])) : 'Gratis' ?></div>
                            <dl class="cp-datos">
                                <div><dt>Vigencia</dt><dd><?= (int) $c['duracion_dias'] ?> días</dd></div>
                                <div><dt>Vehículos</dt><dd><?= $c['limite_vehiculos'] === null ? 'Ilimitados' : (int) $c['limite_vehiculos'] ?></dd></div>
                                <div><dt>Solicitada</dt><dd><?= e(date('d/m/Y H:i', strtotime($c['fecha_compra']))) ?></dd></div>
                            </dl>

                            <?php if ($abierta): ?>
                                <?php if (puede_aprobar($c)): ?>
                                    <form method="post" class="cp-decision">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $c['id_suscripcion'] ?>">
                                        <?php if (!$pago_ok): ?>
                                            <label class="cp-acepto"><input type="checkbox" name="confirmo_pago" value="1"><span>Confirmo que recibí el pago de <b><?= e(dinero($c['valor'])) ?></b> (ref. <?= e($c['referencia']) ?>).</span></label>
                                        <?php endif; ?>
                                        <button class="btn btn-teal cp-enviar" name="accion" value="aprobar">Aprobar y activar plan</button>
                                    </form>
                                <?php else: ?>
                                    <p class="cp-nota"><i class="fa-solid fa-hourglass-half"></i> No se puede aprobar todavía: el cliente aún no paga o no sube el comprobante.</p>
                                <?php endif; ?>
                                <form method="post" class="cp-decision">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $c['id_suscripcion'] ?>">
                                    <label for="motivo" class="cp-mini">Motivo del rechazo</label>
                                    <textarea id="motivo" name="motivo" rows="3" maxlength="255" placeholder="Ej.: El certificado está vencido, sube uno de los últimos 30 días."></textarea>
                                    <button class="btn btn-borde cp-enviar" name="accion" value="rechazar">Rechazar solicitud</button>
                                </form>
                            <?php else: ?>
                                <p class="cp-nota">Esta solicitud ya fue resuelta.</p>
                            <?php endif; ?>
                        </div>
                    </aside>
                </div>

            <?php else: ?>
                <div class="cp-tabs">
                    <?php foreach (['pendientes' => 'Pendientes', 'activas' => 'Activas', 'rechazadas' => 'Rechazadas', 'todas' => 'Todas'] as $k => $t): ?>
                        <a class="cp-tab<?= $filtro === $k ? ' activo' : '' ?>" href="solicitudes.php?f=<?= $k ?>"><?= $t ?> <span><?= (int) $cuentas[$k] ?></span></a>
                    <?php endforeach; ?>
                </div>
                <?php if (!$lista): ?>
                    <div class="cp-vacio"><i class="fa-regular fa-folder-open"></i> No hay solicitudes en esta lista.</div>
                <?php else: ?>
                    <div class="cp-tabla-caja">
                        <table class="cp-tabla">
                            <thead><tr><th>#</th><th>Empresa</th><th>Plan</th><th>Método</th><th>Valor</th><th>Pago</th><th>Estado</th><th>Fecha</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($lista as $r):
                                [$tp, $cpg] = texto_estado((int) $r['estado_pago']);
                                [$te, $ce] = texto_estado((int) $r['estado_suscripcion']); ?>
                                <tr>
                                    <td>#<?= (int) $r['id_suscripcion'] ?></td>
                                    <td><b><?= e($r['razon_social']) ?></b><small><?= e($r['nit']) ?></small></td>
                                    <td><?= e($r['plan']) ?></td>
                                    <td><?= e(($metodos[$r['metodo']]['nombre'] ?? 'Sin costo')) ?></td>
                                    <td><?= e(dinero($r['valor'])) ?></td>
                                    <td><span class="cp-badge <?= e($cpg) ?>"><?= e($tp) ?></span></td>
                                    <td><span class="cp-badge <?= e($ce) ?>"><?= e($te) ?></span></td>
                                    <td><?= e(date('d/m/Y', strtotime($r['fecha_compra']))) ?></td>
                                    <td><a class="btn btn-teal cp-rev" href="solicitudes.php?id=<?= (int) $r['id_suscripcion'] ?>">Revisar</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
    </div>
</div>
<?php panel_cerrar(); ?>
