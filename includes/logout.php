<?php
// Cierra la sesión de forma segura y devuelve cada perfil a su propio acceso.
require_once __DIR__ . '/funciones.php';
iniciar_sesion();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$origen = (string) ($_SESSION['usuario']['origen'] ?? '');
$destino = ($origen === 'super_admin') ? '../superadmin.php' : '../login.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido($_POST['csrf'] ?? null)) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

// Eliminar todos los datos de autenticación y cualquier otro dato de la sesión.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $p['path'],
        'domain' => $p['domain'],
        'secure' => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => $p['samesite'] ?? 'Lax',
    ]);
}
session_destroy();

header('Location: ' . $destino, true, 303);
exit;
