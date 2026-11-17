<?php
// ============================================================
// RutaNexo — Exportación de usuarios a PDF (Super Administrador)
// Respeta los filtros de búsqueda, rol y estado de usuarios.php.
// ============================================================
require __DIR__ . '/_panel.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('No está instalada la librería Dompdf. Ejecuta: composer require dompdf/dompdf');
}
require_once $autoload;

if (!class_exists(\Dompdf\Dompdf::class)) {
    http_response_code(500);
    exit('No se pudo cargar Dompdf. Verifica la instalación de la librería.');
}

$q  = trim((string) ($_GET['q'] ?? ''));
$fr = (int) ($_GET['rol'] ?? 0);
$fe = (int) ($_GET['estado'] ?? 0);

$donde = ' WHERE 1=1';
$par = [];
if ($q !== '') {
    $donde .= ' AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.correo LIKE ? OR CAST(u.documento AS CHAR) LIKE ?)';
    $par = array_fill(0, 4, '%' . $q . '%');
}
if ($fr > 0) {
    $donde .= ' AND u.id_rol = ?';
    $par[] = $fr;
}
if ($fe > 0) {
    $donde .= ' AND u.id_estados = ?';
    $par[] = $fe;
}

$sql = 'SELECT u.documento, u.nombre, u.apellido, u.correo, u.telefono,
               u.fecha_registro, u.id_estados, r.nombre_rol, e.razon_social,
               s.nombre AS estado
        FROM usuario u
        JOIN rol r ON r.id_rol = u.id_rol
        JOIN estado_registro s ON s.id_estados = u.id_estados
        LEFT JOIN empresa e ON e.id_empresa = u.id_empresa'
        . $donde . ' ORDER BY u.fecha_registro DESC, u.documento DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($par);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Escapa los valores antes de incorporarlos al HTML que procesa Dompdf.
$h = static function ($valor): string {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$filtrado = [];
if ($q !== '') $filtrado[] = 'Búsqueda: ' . $q;
if ($fr > 0) {
    $rolStmt = $pdo->prepare('SELECT nombre_rol FROM rol WHERE id_rol = ?');
    $rolStmt->execute([$fr]);
    $nombreRol = $rolStmt->fetchColumn();
    if ($nombreRol !== false) $filtrado[] = 'Rol: ' . $nombreRol;
}
if ($fe === 1) $filtrado[] = 'Estado: Habilitado';
if ($fe === 2) $filtrado[] = 'Estado: Deshabilitado';

$filas = '';
foreach ($usuarios as $usuario) {
    $fecha = '';
    if (!empty($usuario['fecha_registro'])) {
        $marcaTiempo = strtotime($usuario['fecha_registro']);
        $fecha = $marcaTiempo ? date('d/m/Y', $marcaTiempo) : (string) $usuario['fecha_registro'];
    }
    $filas .= '<tr>'
        . '<td>' . $h(trim($usuario['nombre'] . ' ' . $usuario['apellido'])) . '</td>'
        . '<td>' . $h($usuario['documento']) . '</td>'
        . '<td>' . $h($usuario['correo']) . '</td>'
        . '<td>' . $h($usuario['telefono'] ?: '—') . '</td>'
        . '<td>' . $h($usuario['nombre_rol']) . '</td>'
        . '<td>' . $h($usuario['razon_social'] ?: '—') . '</td>'
        . '<td>' . $h($fecha) . '</td>'
        . '<td>' . $h($usuario['estado']) . '</td>'
        . '</tr>';
}
if ($filas === '') {
    $filas = '<tr><td colspan="8" class="vacio">No se encontraron usuarios con los filtros seleccionados.</td></tr>';
}

$detalleFiltros = $filtrado
    ? '<p class="filtros">' . $h(implode(' · ', $filtrado)) . '</p>'
    : '<p class="filtros">Todos los usuarios registrados</p>';

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
    @page { margin: 105px 25px 55px 25px; }
    body { font-family: DejaVu Sans, sans-serif; color: #173247; font-size: 8px; }
    header { position: fixed; top: -82px; left: 0; right: 0; height: 70px; }
    h1 { margin: 0 0 5px; color: #12364b; font-size: 19px; }
    .marca { color: #16716f; font-size: 9px; font-weight: bold; margin-bottom: 5px; }
    .meta { color: #637684; font-size: 8px; }
    .filtros { margin: 8px 0 12px; padding: 7px; background: #edf4f5; color: #315568; }
    table { width: 100%; border-collapse: collapse; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    th { background: #173e55; color: #fff; font-size: 7px; text-align: left; padding: 7px 5px; }
    td { border-bottom: 1px solid #dce5e9; padding: 6px 5px; vertical-align: top; }
    tbody tr:nth-child(even) { background: #f4f8f9; }
    .vacio { text-align: center; padding: 18px; color: #637684; }
    footer { position: fixed; bottom: -35px; left: 0; right: 0; text-align: center; color: #718391; font-size: 7px; }
    .pagina:after { content: counter(page); }
</style></head><body>'
    . '<header><div class="marca">RUTANEXO · SUPER ADMINISTRACIÓN</div>'
    . '<h1>Listado de usuarios</h1>'
    . '<div class="meta">Generado el ' . $h(date('d/m/Y H:i')) . ' · Total: ' . count($usuarios) . ' usuario(s)</div>'
    . $detalleFiltros . '</header>'
    . '<table><thead><tr><th>Usuario</th><th>Documento</th><th>Correo</th><th>Teléfono</th><th>Rol</th><th>Empresa</th><th>Registro</th><th>Estado</th></tr></thead>'
    . '<tbody>' . $filas . '</tbody></table>'
    . '<footer>RutaNexo · Documento generado desde el panel administrativo · Página <span class="pagina"></span></footer>'
    . '</body></html>';

$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('rutanexo_usuarios_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
exit;
