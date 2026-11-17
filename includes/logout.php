<?php
// Cierra la sesión (solo por POST con token CSRF) y devuelve cada perfil a su propio acceso
require_once __DIR__ . '/funciones.php';
iniciar_sesion();

// Guardar el tipo de cuenta antes de destruir la sesión.
$origen = (string) ($_SESSION['usuario']['origen'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf'] ?? null)) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// El Super Administrador tiene un acceso independiente; los demás perfiles usan login.php.
$destino = ($origen === 'super_admin') ? '../superadmin.php' : '../login.php';
header('Location: ' . $destino);
exit;
