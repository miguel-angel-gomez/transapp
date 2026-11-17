<?php
// ============================================================
// Funciones de la compra de planes (comprar.php, mi_compra.php,
// archivo.php y admin/solicitudes.php).
// ============================================================
require_once __DIR__ . '/../config/pagos.php';

const DIR_DOCUMENTOS = __DIR__ . '/../storage/documentos';

/** Formato de dinero colombiano: 1490000 → $1.490.000 */
function dinero($n): string
{
    return '$' . number_format((float) $n, 0, ',', '.');
}

/** Plan habilitado por id (o null). */
function plan_por_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM plan_suscripcion WHERE id_plan = ? AND id_estados = 1');
    $stmt->execute([$id]);
    $plan = $stmt->fetch();
    if ($plan) {
        $plan['caracteristicas'] = json_decode((string) $plan['caracteristicas'], true) ?: [];
    }
    return $plan ?: null;
}

/** Textos de las características (claves del JSON) para mostrarlas al usuario. */
function texto_caracteristica(string $clave): string
{
    return [
        'rutas_viajes'                => 'Rutas y viajes',
        'consulta_mantenimientos'     => 'Consulta de mantenimientos',
        'reportes_basicos_limitados'  => 'Reportes básicos (limitados)',
        'mantenimientos_tecnicos'     => 'Mantenimientos técnicos',
        'asignacion_conductores'      => 'Asignación de conductores',
        'reportes_incidentes'         => 'Reporte de incidentes',
        'reportes_avanzados_limitados'=> 'Reportes avanzados (limitados)',
        'reportes_avanzados'          => 'Reportes avanzados',
        'pagos_digitales'             => 'Pagos digitales',
        'programa_puntos'             => 'Programa de puntos',
        'reciclaje'                   => 'Reciclaje',
        'canje_puntos'                => 'Canje de puntos',
    ][$clave] ?? ucfirst(str_replace('_', ' ', $clave));
}

/** Referencia única de pago: RN-261019-K3F9QZ */
function generar_referencia(): string
{
    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $r = '';
    for ($i = 0; $i < 6; $i++) {
        $r .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
    }
    return 'RN-' . date('ymd') . '-' . $r;
}

/**
 * Guarda un archivo subido (PDF, JPG o PNG, máx. 5 MB) en storage/documentos.
 * Devuelve ['ok'=>true,'nombre'=>...] o ['ok'=>false,'error'=>...].
 */
function guardar_archivo(?array $f, string $prefijo): array
{
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Adjunta el archivo.'];
    }
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'error' => 'El archivo pesa más de 5 MB.'];
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return ['ok' => false, 'error' => 'No se pudo recibir el archivo. Intenta de nuevo.'];
    }
    if ($f['size'] > ARCHIVO_MAX_BYTES) {
        return ['ok' => false, 'error' => 'El archivo pesa más de 5 MB.'];
    }
    // El tipo se detecta por el contenido, no por lo que diga el navegador
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext  = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
    if (!$ext) {
        return ['ok' => false, 'error' => 'Solo se aceptan archivos PDF, JPG o PNG.'];
    }
    if (!is_dir(DIR_DOCUMENTOS) && !mkdir(DIR_DOCUMENTOS, 0775, true)) {
        return ['ok' => false, 'error' => 'No se pudo guardar el archivo (permisos de la carpeta storage).'];
    }
    $nombre = $prefijo . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], DIR_DOCUMENTOS . '/' . $nombre)) {
        return ['ok' => false, 'error' => 'No se pudo guardar el archivo (permisos de la carpeta storage).'];
    }
    return ['ok' => true, 'nombre' => $nombre];
}

function borrar_archivo(?string $nombre): void
{
    if ($nombre && preg_match('/^[a-z]+_[0-9a-f]{16}\.(pdf|jpg|png)$/', $nombre)) {
        @unlink(DIR_DOCUMENTOS . '/' . $nombre);
    }
}

/** Última compra de un usuario (por su empresa): empresa + suscripción + plan + pago. */
function compra_del_usuario(PDO $pdo, $documento): ?array
{
    $stmt = $pdo->prepare(
        "SELECT s.id_suscripcion FROM usuario u
           JOIN suscripcion s ON s.id_empresa = u.id_empresa
          WHERE u.documento = ? ORDER BY s.id_suscripcion DESC LIMIT 1"
    );
    $stmt->execute([$documento]);
    $id = $stmt->fetchColumn();
    return $id ? compra_por_id($pdo, (int) $id) : null;
}

/** Compra completa por id de suscripción (o null). */
function compra_por_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        "SELECT s.id_suscripcion, s.id_empresa, s.id_plan, s.fecha_compra, s.fecha_inicio, s.fecha_fin,
                s.valor_pagado, s.id_estados AS estado_suscripcion,
                e.nit, e.razon_social, e.nombre_representante, e.documento_representante, e.correo AS correo_empresa,
                e.telefono AS telefono_empresa, e.direccion, e.camara_comercio, e.id_estados AS estado_empresa,
                c.nombre AS ciudad,
                p.nombre AS plan, p.precio, p.duracion_dias, p.limite_vehiculos, p.caracteristicas,
                g.id_pago, g.metodo, g.referencia, g.valor, g.comprobante, g.observacion,
                g.id_estados AS estado_pago, g.fecha_vencimiento, g.fecha_pago, g.fecha_verificacion,
                (SELECT CONCAT(u.nombre, ' ', u.apellido) FROM usuario u WHERE u.id_empresa = e.id_empresa
                  ORDER BY u.fecha_registro LIMIT 1) AS comprador,
                (SELECT u.documento FROM usuario u WHERE u.id_empresa = e.id_empresa
                  ORDER BY u.fecha_registro LIMIT 1) AS doc_comprador
           FROM suscripcion s
           JOIN empresa e ON e.id_empresa = s.id_empresa
           JOIN plan_suscripcion p ON p.id_plan = s.id_plan
           LEFT JOIN ciudad c ON c.id_ciudad = e.id_ciudad
           LEFT JOIN pago g ON g.id_suscripcion = s.id_suscripcion
          WHERE s.id_suscripcion = ?
          ORDER BY g.id_pago DESC LIMIT 1"
    );
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if (!$c) {
        return null;
    }
    $c['caracteristicas'] = json_decode((string) $c['caracteristicas'], true) ?: [];
    return $c;
}

/** Exige sesión iniciada; si no, manda al login conservando el plan elegido. */
function exigir_sesion(?int $plan = null): array
{
    if (empty($_SESSION['usuario'])) {
        header('Location: login.php' . ($plan ? '?plan=' . $plan : ''));
        exit;
    }
    return $_SESSION['usuario'];
}

// ---------------------------------------------------------------
// Decisión del Super Administrador
// ---------------------------------------------------------------

/** ¿Se puede aprobar? PSE y transferencia necesitan pago/comprobante; el efectivo lo confirma el admin. */
function puede_aprobar(array $c): bool
{
    return (int) $c['estado_suscripcion'] === PAGO_ESTADO_ESPERA
        && ((int) $c['estado_pago'] >= PAGO_ESTADO_TRAMITE && (int) $c['estado_pago'] <= PAGO_ESTADO_REALIZADO
            || $c['metodo'] === 'efectivo');
}

/** Aprueba: pago Realizada, suscripción Vigente, empresa Habilitada y el comprador pasa a Administrador. */
function aprobar_compra(PDO $pdo, int $id_suscripcion, int $admin_doc): bool
{
    $c = compra_por_id($pdo, $id_suscripcion);
    if (!$c || !puede_aprobar($c)) {
        return false;
    }
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE pago SET id_estados = ?, fecha_pago = COALESCE(fecha_pago, NOW()), fecha_verificacion = NOW(), verificado_por = ?, observacion = NULL WHERE id_pago = ?')
            ->execute([PAGO_ESTADO_REALIZADO, $admin_doc, $c['id_pago']]);
        $pdo->prepare('UPDATE suscripcion SET fecha_inicio = NOW(), fecha_fin = DATE_ADD(NOW(), INTERVAL ? DAY), valor_pagado = ?, id_estados = ? WHERE id_suscripcion = ?')
            ->execute([(int) $c['duracion_dias'], $c['valor'], ESTADO_VIGENTE, $id_suscripcion]);
        $pdo->prepare('UPDATE empresa SET id_estados = ? WHERE id_empresa = ?')
            ->execute([ESTADO_HABILITADO, $c['id_empresa']]);
        $pdo->prepare('UPDATE usuario SET id_rol = ? WHERE id_empresa = ? AND id_rol = ?')
            ->execute([ROL_ADMINISTRADOR, $c['id_empresa'], ROL_USUARIO]);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('aprobar_compra: ' . $e->getMessage());
        return false;
    }
}

/** Rechaza la solicitud con un motivo que verá el cliente. */
function rechazar_compra(PDO $pdo, int $id_suscripcion, int $admin_doc, string $motivo): bool
{
    $c = compra_por_id($pdo, $id_suscripcion);
    if (!$c || (int) $c['estado_suscripcion'] !== PAGO_ESTADO_ESPERA) {
        return false;
    }
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE pago SET id_estados = ?, fecha_verificacion = NOW(), verificado_por = ?, observacion = ? WHERE id_pago = ?')
            ->execute([ESTADO_ANULADO, $admin_doc, mb_substr($motivo, 0, 255), $c['id_pago']]);
        $pdo->prepare('UPDATE suscripcion SET id_estados = ? WHERE id_suscripcion = ?')->execute([ESTADO_ANULADO, $id_suscripcion]);
        $pdo->prepare('UPDATE empresa SET id_estados = ? WHERE id_empresa = ?')->execute([ESTADO_DESCARTADA, $c['id_empresa']]);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('rechazar_compra: ' . $e->getMessage());
        return false;
    }
}

/** ¿El usuario de la sesión puede ver esta compra? (dueño o Super Administrador) */
function puede_ver_compra(PDO $pdo, array $u, array $c): bool
{
    if (($u['origen'] ?? '') === 'super_admin') {
        return true;
    }
    $s = $pdo->prepare('SELECT 1 FROM usuario WHERE documento = ? AND id_empresa = ?');
    $s->execute([$u['documento'], $c['id_empresa']]);
    return (bool) $s->fetchColumn();
}
