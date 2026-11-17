<?php
// ============================================================
// Entrega los documentos privados (cámara de comercio y comprobantes).
// Solo el dueño de la compra o un Super Administrador.
//   archivo.php?t=camara|comprobante&id=<id_suscripcion>
// ============================================================
require_once 'config/conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/compras.php';

iniciar_sesion();
if (empty($_SESSION['usuario'])) { http_response_code(403); exit('Sin acceso.'); }

$pdo = (new Database())->conectar();
$c = $pdo ? compra_por_id($pdo, (int) ($_GET['id'] ?? 0)) : null;
if (!$c || !puede_ver_compra($pdo, $_SESSION['usuario'], $c)) { http_response_code(404); exit('No encontrado.'); }

$nombre = ($_GET['t'] ?? '') === 'comprobante' ? $c['comprobante'] : $c['camara_comercio'];
if (!$nombre || !preg_match('/^[a-z]+_[0-9a-f]{16}\.(pdf|jpg|png)$/', $nombre) || !is_file(DIR_DOCUMENTOS . '/' . $nombre)) {
    http_response_code(404); exit('No hay archivo.');
}
$tipos = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'];
$ext = pathinfo($nombre, PATHINFO_EXTENSION);
header('Content-Type: ' . $tipos[$ext]);
header('Content-Disposition: inline; filename="' . $nombre . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile(DIR_DOCUMENTOS . '/' . $nombre);
