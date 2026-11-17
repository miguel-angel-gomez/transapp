<?php
// ============================================================
// RutaNexo — Autenticación (una sola página con tarjeta que gira)
//
// Paneles (formularios):
//   login · registro · rec1 (pedir correo) · rec2 (PIN) · rec3 (nueva contraseña)
//
// Los formularios se envían por AJAX a este mismo archivo (POST con
// 'accion') y responden JSON. La parte de arriba es la lógica y la de
// abajo el HTML, como en el proyecto cfv.
// ============================================================
require_once 'config/conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/mailer.php';

iniciar_sesion();

// Hash de relleno: se usa cuando el correo no existe para que el login
// tarde lo mismo y no revele qué correos están registrados.
const HASH_RELLENO = '$2y$10$ljDYoC25ScOLqf27n8RPm.xJEebSpjtaS4V0pG2NJH/MKqH/eHzQO';

// ------------------------------------------------------------
// Utilidades de las acciones
// ------------------------------------------------------------

/** Respuesta de error de validación (422) con mensajes por campo. */
function error_campos(array $errores, string $mensaje = 'Revisa los campos marcados.'): void
{
    json_salida(['ok' => false, 'mensaje' => $mensaje, 'errores' => $errores], 422);
}

/** Oculta parte del correo: juan.perez@gmail.com → ju***@gmail.com */
function correo_oculto(string $correo): string
{
    [$usuario, $dominio] = array_pad(explode('@', $correo, 2), 2, '');
    return mb_substr($usuario, 0, 2) . '***@' . $dominio;
}

/** Borra todo el estado de recuperación guardado en la sesión. */
function olvidar_recuperacion(): void
{
    unset($_SESSION['rec_correo'], $_SESSION['rec_intentos'], $_SESSION['rec_ok']);
}

// ------------------------------------------------------------
// ACCIÓN: iniciar sesión
// ------------------------------------------------------------
function accion_login(PDO $pdo): void
{
    $correo   = mb_strtolower(trim((string) ($_POST['correo'] ?? '')));
    $clave    = (string) ($_POST['password'] ?? '');
    $recordar = !empty($_POST['recordar']);

    $errores = [];
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if ($clave === '') {
        $errores['password'] = 'Escribe tu contraseña.';
    }
    if ($errores) {
        error_campos($errores);
    }

    // Máximo 5 intentos fallidos cada 10 minutos
    if (intentos_recientes('login', 600) >= 5) {
        $min = (int) ceil(segundos_para_reintentar('login', 600) / 60);
        json_salida(['ok' => false, 'mensaje' => "Demasiados intentos fallidos. Intenta de nuevo en $min min."], 429);
    }

    $cuenta = buscar_cuenta($pdo, $correo);
    // El Super Administrador NO entra por aquí: tiene su propio acceso (index.php/login-superadmin) con código por correo.
    if ($cuenta && $cuenta['origen'] === 'super_admin') {
        $cuenta = null;
    }
    $clave_ok = password_verify($clave, $cuenta['clave'] ?? HASH_RELLENO);

    if (!$cuenta || !$clave_ok) {
        registrar_intento('login');
        json_salida(['ok' => false, 'mensaje' => 'Correo o contraseña incorrectos.'], 401);
    }

    if ((int) $cuenta['id_estados'] !== ESTADO_HABILITADO) {
        json_salida(['ok' => false, 'mensaje' => 'Tu cuenta no está habilitada. Comunícate con el administrador.'], 403);
    }

    // Todo correcto: nueva sesión (evita fijación de sesión)
    session_regenerate_id(true);
    if ($recordar) {
        setcookie(session_name(), session_id(), [
            'expires'  => time() + SESION_RECORDAR_SEGUNDOS,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    $_SESSION['user_id'] = $cuenta['documento'];
    $_SESSION['usuario'] = [
        'documento' => $cuenta['documento'],
        'nombre'    => $cuenta['nombre'],
        'apellido'  => $cuenta['apellido'],
        'correo'    => $cuenta['correo'],
        'id_rol'    => (int) $cuenta['id_rol'],
        'origen'    => $cuenta['origen'],
    ];
    limpiar_intentos('login');
    olvidar_recuperacion();

    json_salida(['ok' => true, 'redirigir' => destino_tras_login((int) $cuenta['id_rol'], $cuenta['origen'])]);
}

// ------------------------------------------------------------
// ACCIÓN: crear cuenta (rol Usuario / pasajero)
// ------------------------------------------------------------
function accion_registro(PDO $pdo): void
{
    $documento = preg_replace('/\s+/', '', (string) ($_POST['documento'] ?? ''));
    $nombre    = trim((string) ($_POST['nombre'] ?? ''));
    $apellido  = trim((string) ($_POST['apellido'] ?? ''));
    $correo    = mb_strtolower(trim((string) ($_POST['correo'] ?? '')));
    $telefono  = trim((string) ($_POST['telefono'] ?? ''));
    $clave     = (string) ($_POST['password'] ?? '');
    $clave2    = (string) ($_POST['password2'] ?? '');
    $acepta    = !empty($_POST['acepto']);

    $errores = [];
    if (!preg_match('/^\d{5,12}$/', $documento)) {
        $errores['documento'] = 'Solo números, entre 5 y 12 dígitos.';
    }
    if (!preg_match('/^[\p{L}][\p{L}\s.\'-]{1,99}$/u', $nombre)) {
        $errores['nombre'] = 'Escribe tu nombre (solo letras).';
    }
    if (!preg_match('/^[\p{L}][\p{L}\s.\'-]{1,99}$/u', $apellido)) {
        $errores['apellido'] = 'Escribe tu apellido (solo letras).';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 100) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if ($telefono !== '' && !preg_match('/^[0-9+\s-]{7,20}$/', $telefono)) {
        $errores['telefono'] = 'Teléfono no válido.';
    }
    if ($msg = error_clave($clave)) {
        $errores['password'] = $msg;
    }
    if (!isset($errores['password']) && $clave !== $clave2) {
        $errores['password2'] = 'Las contraseñas no coinciden.';
    }
    if (!$acepta) {
        $errores['acepto'] = 'Debes aceptar el tratamiento de datos.';
    }
    if ($errores) {
        error_campos($errores);
    }

    if (intentos_recientes('registro', 3600) >= 10) {
        json_salida(['ok' => false, 'mensaje' => 'Demasiados registros desde este navegador. Intenta más tarde.'], 429);
    }
    registrar_intento('registro');

    // Duplicados (documento en usuario o super_admin, correo en cualquiera de las dos tablas)
    $stmt = $pdo->prepare('SELECT 1 FROM usuario WHERE documento = ? UNION SELECT 1 FROM super_admin WHERE documento = ?');
    $stmt->execute([$documento, $documento]);
    if ($stmt->fetchColumn()) {
        error_campos(['documento' => 'Este documento ya está registrado.']);
    }
    if (correo_existe($pdo, $correo)) {
        error_campos(['correo' => 'Este correo ya está registrado.']);
    }

    try {
        $pdo->prepare(
            'INSERT INTO usuario (documento, nombre, apellido, correo, contrasena, telefono, id_rol, id_estados)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $documento, $nombre, $apellido, $correo,
            password_hash($clave, PASSWORD_DEFAULT),
            $telefono !== '' ? $telefono : null,
            ROL_USUARIO, ESTADO_HABILITADO,
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {   // choque de llave única (dos registros a la vez)
            error_campos(['correo' => 'El documento o el correo ya están registrados.']);
        }
        throw $e;
    }

    json_salida([
        'ok'      => true,
        'mensaje' => '¡Cuenta creada! Ya puedes iniciar sesión.',
        'panel'   => 'login',
        'correo'  => $correo,
    ]);
}

// ------------------------------------------------------------
// RECUPERAR CONTRASEÑA — paso 1: generar y enviar el PIN
// ------------------------------------------------------------
function enviar_pin(PDO $pdo, string $correo): void
{
    // Espera mínima entre envíos y tope de envíos por sesión
    $espera = 60 - (time() - (int) ($_SESSION['rec_ultimo_envio'] ?? 0));
    if ($espera > 0) {
        json_salida(['ok' => false, 'mensaje' => "Espera $espera s antes de pedir otro código."], 429);
    }
    if (intentos_recientes('rec_envios', 900) >= 5) {
        json_salida(['ok' => false, 'mensaje' => 'Pediste demasiados códigos. Intenta de nuevo en unos minutos.'], 429);
    }
    registrar_intento('rec_envios');

    $cuenta = buscar_cuenta($pdo, $correo);

    // Si el correo no existe respondemos igual (no revelamos qué correos están registrados)
    if ($cuenta && (int) $cuenta['id_estados'] === ESTADO_HABILITADO) {
        $pin = (string) random_int(10000000, 99999999);
        guardar_pin($pdo, $cuenta, $pin);

        if (!enviar_pin_correo($cuenta['correo'], $cuenta['nombre'], $pin)) {
            borrar_pin($pdo, $cuenta);
            json_salida(['ok' => false, 'mensaje' => 'No pudimos enviar el correo. Intenta de nuevo en unos minutos.'], 500);
        }
    }

    $_SESSION['rec_correo']       = $correo;
    $_SESSION['rec_intentos']     = 0;
    $_SESSION['rec_ultimo_envio'] = time();
    unset($_SESSION['rec_ok']);

    json_salida([
        'ok'            => true,
        'mensaje'       => 'Si el correo está registrado, te enviamos un código de 8 dígitos.',
        'panel'         => 'rec2',
        'correo_oculto' => correo_oculto($correo),
    ]);
}

function accion_recuperar_correo(PDO $pdo): void
{
    $correo = mb_strtolower(trim((string) ($_POST['correo'] ?? '')));
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        error_campos(['correo' => 'Escribe un correo válido.']);
    }
    enviar_pin($pdo, $correo);
}

function accion_recuperar_reenviar(PDO $pdo): void
{
    $correo = $_SESSION['rec_correo'] ?? '';
    if ($correo === '') {
        json_salida(['ok' => false, 'mensaje' => 'Tu solicitud expiró. Escribe tu correo de nuevo.', 'panel' => 'rec1'], 409);
    }
    enviar_pin($pdo, $correo);
}

// ------------------------------------------------------------
// RECUPERAR CONTRASEÑA — paso 2: verificar el PIN
// ------------------------------------------------------------
function accion_recuperar_pin(PDO $pdo): void
{
    $correo = $_SESSION['rec_correo'] ?? '';
    if ($correo === '') {
        json_salida(['ok' => false, 'mensaje' => 'Tu solicitud expiró. Escribe tu correo de nuevo.', 'panel' => 'rec1'], 409);
    }

    $pin = preg_replace('/\D/', '', (string) ($_POST['pin'] ?? ''));
    if (strlen($pin) !== 8) {
        error_campos(['pin' => 'Escribe los 8 dígitos del código.']);
    }

    $cuenta = buscar_cuenta($pdo, $correo);

    $_SESSION['rec_intentos'] = (int) ($_SESSION['rec_intentos'] ?? 0) + 1;
    $guardado = $cuenta ? obtener_pin($pdo, $cuenta) : null;

    if (!$guardado || !hash_equals($guardado['pin'], $pin)) {
        // Máximo 5 intentos por código: al quinto error el código se invalida
        $quedan = 5 - $_SESSION['rec_intentos'];
        if ($quedan <= 0) {
            if ($cuenta) {
                borrar_pin($pdo, $cuenta);
            }
            olvidar_recuperacion();
            json_salida(['ok' => false, 'mensaje' => 'Demasiados intentos. Pide un código nuevo.', 'panel' => 'rec1'], 429);
        }
        error_campos(['pin' => "Código incorrecto. Te quedan $quedan " . ($quedan === 1 ? 'intento.' : 'intentos.')],
            'Código incorrecto.');
    }

    if (!$guardado['vigente']) {
        borrar_pin($pdo, $cuenta);
        olvidar_recuperacion();
        json_salida(['ok' => false, 'mensaje' => 'El código venció. Solicita uno nuevo.', 'panel' => 'rec1'], 410);
    }

    // PIN correcto: se guarda en la sesión para comprobar en el paso 3 que sigue siendo el mismo
    session_regenerate_id(true);
    $_SESSION['rec_ok'] = $pin;

    json_salida(['ok' => true, 'panel' => 'rec3']);
}

// ------------------------------------------------------------
// RECUPERAR CONTRASEÑA — paso 3: guardar la nueva contraseña
// ------------------------------------------------------------
function accion_recuperar_nueva(PDO $pdo): void
{
    $correo = $_SESSION['rec_correo'] ?? '';
    $pin_ok = $_SESSION['rec_ok'] ?? '';
    if ($correo === '' || $pin_ok === '') {
        json_salida(['ok' => false, 'mensaje' => 'Tu solicitud expiró. Empieza de nuevo.', 'panel' => 'rec1'], 409);
    }

    $clave  = (string) ($_POST['password'] ?? '');
    $clave2 = (string) ($_POST['password2'] ?? '');

    $errores = [];
    if ($msg = error_clave($clave)) {
        $errores['password'] = $msg;
    } elseif ($clave !== $clave2) {
        $errores['password2'] = 'Las contraseñas no coinciden.';
    }
    if ($errores) {
        error_campos($errores);
    }

    $cuenta   = buscar_cuenta($pdo, $correo);
    $guardado = $cuenta ? obtener_pin($pdo, $cuenta) : null;

    if (!$guardado || !$guardado['vigente'] || !hash_equals($guardado['pin'], (string) $pin_ok)) {
        olvidar_recuperacion();
        json_salida(['ok' => false, 'mensaje' => 'El código ya no es válido. Solicita uno nuevo.', 'panel' => 'rec1'], 410);
    }

    cambiar_clave($pdo, $cuenta, $clave);
    olvidar_recuperacion();
    limpiar_intentos('login');

    json_salida([
        'ok'      => true,
        'mensaje' => 'Contraseña actualizada. Ya puedes iniciar sesión.',
        'panel'   => 'login',
        'correo'  => $correo,
    ]);
}

// ------------------------------------------------------------
// ENRUTADOR DE PETICIONES AJAX (POST)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valido($_POST['csrf'] ?? null)) {
        json_salida(['ok' => false, 'mensaje' => 'Tu sesión expiró. Recarga la página e inténtalo de nuevo.'], 419);
    }

    $pdo = (new Database())->conectar();
    if (!$pdo) {
        json_salida(['ok' => false, 'mensaje' => 'No hay conexión con la base de datos. Intenta más tarde.'], 503);
    }

    try {
        switch ($_POST['accion'] ?? '') {
            case 'login':               accion_login($pdo);               break;
            case 'registro':            accion_registro($pdo);            break;
            case 'recuperar_correo':    accion_recuperar_correo($pdo);    break;
            case 'recuperar_reenviar':  accion_recuperar_reenviar($pdo);  break;
            case 'recuperar_pin':       accion_recuperar_pin($pdo);       break;
            case 'recuperar_nueva':     accion_recuperar_nueva($pdo);     break;
            default:
                json_salida(['ok' => false, 'mensaje' => 'Acción no válida.'], 400);
        }
    } catch (PDOException $e) {
        error_log('Error en login.php: ' . $e->getMessage());
        json_salida(['ok' => false, 'mensaje' => 'Ocurrió un error inesperado. Intenta de nuevo.'], 500);
    }
    exit;
}

// ------------------------------------------------------------
// GET: si ya hay sesión se va directo al panel
// ------------------------------------------------------------
// ?plan=ID viene del botón "Comprar" de la landing: se recuerda para llevar al formulario de compra
if (isset($_GET['plan'])) {
    $plan_get = (int) $_GET['plan'];
    if ($plan_get > 0) { $_SESSION['plan_deseado'] = $plan_get; } else { unset($_SESSION['plan_deseado']); }
}
if (!empty($_SESSION['usuario'])) {
    $us = $_SESSION['usuario'];
    header('Location: ' . destino_tras_login((int) $us['id_rol'], (string) ($us['origen'] ?? 'usuario')));
    exit;
}

// Nombre del plan elegido (para avisar en pantalla)
$plan_nombre = null;
$pdo = !empty($_SESSION['plan_deseado']) ? (new Database())->conectar() : null;
if ($pdo) {
    $sp = $pdo->prepare('SELECT nombre FROM plan_suscripcion WHERE id_plan = ? AND id_estados = 1');
    $sp->execute([(int) $_SESSION['plan_deseado']]);
    $plan_nombre = $sp->fetchColumn() ?: null;
}

// Panel con el que arranca la tarjeta (?modo=registro, ?modo=recuperar)
$modo = $_GET['modo'] ?? 'login';
$panel_inicial = ['registro' => 'registro', 'recuperar' => 'rec1'][$modo] ?? 'login';

$csrf = csrf_token();

// Imagen del lado izquierdo: si pones img/login.jpg (o .png/.webp) se usa en lugar de la ilustración
$img_login = 'img/login-fondo.jpg';
// (para cambiarla, reemplaza img/login-bus.jpg)

header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RutaNexo — Ingresar</title>
    <link rel="icon" type="image/png" href="img/logo-pin.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="css/base.css?v=<?= filemtime(__DIR__ . '/css/base.css') ?>">
    <link rel="stylesheet" href="css/style_login.css?v=<?= filemtime(__DIR__ . '/css/style_login.css') ?>">
</head>

<body class="auth-body">

<div class="auth" style="background-image: url('<?= e($img_login) ?>')">
  <div class="auth-escenario">

    <!-- Logo sobre la imagen (izquierda) y "Volver al inicio" arriba a la derecha -->
    <a href="index.php" class="auth-logo-izq" aria-label="RutaNexo, ir al inicio"><img src="img/logo.png" alt="RutaNexo — Movilidad inteligente para Ibagué"></a>
    <a href="index.php" class="auth-volver"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>

    <!-- El recuadro blanco de la imagen de fondo es donde va el formulario -->
    <main class="auth-card">
        <div class="auth-escena">
            <div class="tarjeta" id="tarjeta" data-inicial="<?= e($panel_inicial) ?>">

                <noscript><p class="alerta alerta-error">Activa JavaScript para usar el formulario.</p></noscript>

                <!-- ============ INICIAR SESIÓN ============ -->
                <section class="panel" data-panel="login" <?= $panel_inicial === 'login' ? '' : 'hidden' ?>>
                    <img src="img/logo-pin.png" alt="" class="panel-logo">
                                        <h1>Iniciar <span class="acento">sesión</span></h1>
                    <p class="panel-sub">Ingresa a tu cuenta para acceder a todas las funcionalidades de RutaNexo.</p>
                    <?php if ($plan_nombre): ?>
                        <div class="alerta alerta-plan"><i class="fa-solid fa-bag-shopping"></i> Inicia sesión para continuar con la compra del plan <b><?= e($plan_nombre) ?></b>.</div>
                    <?php endif; ?>

                    <div class="alerta" role="alert" hidden></div>

                    <form data-accion="login" novalidate>
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="accion" value="login">

                        <div class="campo-wrap">
                            <div class="campo">
                                <span class="campo-icono"><i class="fa-regular fa-envelope"></i></span>
                                <div class="campo-cuerpo">
                                    <label for="login-correo">Correo electrónico</label>
                                    <input type="email" id="login-correo" name="correo" placeholder="ejemplo@correo.com" autocomplete="email" required>
                                </div>
                            </div>
                            <p class="error" data-error="correo"></p>
                        </div>

                        <div class="campo-wrap">
                            <div class="campo">
                                <span class="campo-icono"><i class="fa-solid fa-lock"></i></span>
                                <div class="campo-cuerpo">
                                    <label for="login-password">Contraseña</label>
                                    <input type="password" id="login-password" name="password" placeholder="Ingresa tu contraseña" autocomplete="current-password" required>
                                </div>
                                <button type="button" class="ojo" aria-label="Mostrar contraseña"><i class="fa-regular fa-eye"></i></button>
                            </div>
                            <p class="error" data-error="password"></p>
                        </div>

                        <div class="fila-opciones">
                            <label class="check"><input type="checkbox" name="recordar" value="1" checked> <span>Recordar sesión</span></label>
                            <a href="login.php?modo=recuperar" data-ir="rec1" class="enlace">¿Olvidaste tu contraseña?</a>
                        </div>

                        <button type="submit" class="btn-principal"><i class="fa-solid fa-right-to-bracket"></i> <span>Ingresar</span></button>
                    </form>

                    <div class="separador"><span>o inicia sesión con</span></div>
                    <div class="sociales">
                        <button type="button" class="social" disabled title="Próximamente" aria-label="Google (próximamente)"><i class="fa-brands fa-google"></i></button>
                        <button type="button" class="social" disabled title="Próximamente" aria-label="Microsoft (próximamente)"><i class="fa-brands fa-microsoft"></i></button>
                        <button type="button" class="social" disabled title="Próximamente" aria-label="Apple (próximamente)"><i class="fa-brands fa-apple"></i></button>
                    </div>

                    <div class="cambio">
                        <span class="cambio-icono"><i class="fa-regular fa-user"></i></span>
                        <div>¿No tienes una cuenta?<br><a href="login.php?modo=registro" data-ir="registro" class="enlace">Regístrate aquí <i class="fa-solid fa-chevron-right"></i></a></div>
                    </div>
                </section>

                <!-- ============ CREAR CUENTA ============ -->
                <section class="panel" data-panel="registro" <?= $panel_inicial === 'registro' ? '' : 'hidden' ?>>
                                        <h1>Crear <span class="acento">cuenta</span></h1>
                    <p class="panel-sub">Regístrate para consultar rutas y acumular puntos por reciclar.</p>

                    <div class="alerta" role="alert" hidden></div>

                    <form data-accion="registro" novalidate>
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="accion" value="registro">

                        <div class="campo-wrap">
                            <div class="campo">
                                <span class="campo-icono"><i class="fa-regular fa-id-card"></i></span>
                                <div class="campo-cuerpo">
                                    <label for="reg-documento">Número de documento</label>
                                    <input type="text" id="reg-documento" name="documento" inputmode="numeric" maxlength="12" placeholder="1234567890" autocomplete="off" required>
                                </div>
                            </div>
                            <p class="error" data-error="documento"></p>
                        </div>

                        <div class="fila-2">
                            <div class="campo-wrap">
                                <div class="campo">
                                    <div class="campo-cuerpo">
                                        <label for="reg-nombre">Nombre</label>
                                        <input type="text" id="reg-nombre" name="nombre" maxlength="100" placeholder="Tu nombre" autocomplete="given-name" required>
                                    </div>
                                </div>
                                <p class="error" data-error="nombre"></p>
                            </div>
                            <div class="campo-wrap">
                                <div class="campo">
                                    <div class="campo-cuerpo">
                                        <label for="reg-apellido">Apellido</label>
                                        <input type="text" id="reg-apellido" name="apellido" maxlength="100" placeholder="Tu apellido" autocomplete="family-name" required>
                                    </div>
                                </div>
                                <p class="error" data-error="apellido"></p>
                            </div>
                        </div>

                        <div class="campo-wrap">
                            <div class="campo">
                                <span class="campo-icono"><i class="fa-regular fa-envelope"></i></span>
                                <div class="campo-cuerpo">
                                    <label for="reg-correo">Correo electrónico</label>
                                    <input type="email" id="reg-correo" name="correo" maxlength="100" placeholder="ejemplo@correo.com" autocomplete="email" required>
                                </div>
                            </div>
                            <p class="error" data-error="correo"></p>
                        </div>

                        <div class="campo-wrap">
                            <div class="campo">
                                <span class="campo-icono"><i class="fa-solid fa-phone"></i></span>
                                <div class="campo-cuerpo">
                                    <label for="reg-telefono">Teléfono <small>(opcional)</small></label>
                                    <input type="tel" id="reg-telefono" name="telefono" maxlength="20" placeholder="3001234567" autocomplete="tel">
                                </div>
                            </div>
                            <p class="error" data-error="telefono"></p>
                        </div>

                        <div class="campo-wrap">
                            <div class="campo">
                                <span class="campo-icono"><i class="fa-solid fa-lock"></i></span>
                                <div class="campo-cuerpo">
                                    <label for="reg-password">Contraseña</label>
                                    <input type="password" id="reg-password" name="password" placeholder="Mínimo 8, con letras y números" autocomplete="new-password" required>
                                </div>
                                <button type="button" class="ojo" aria-label="Mostrar contraseña"><i class="fa-regular fa-eye"></i></button>
                            </div>
                            <p class="error" data-error="password"></p>
                        </div>

                        <div class="campo-wrap">
                            <div class="campo">
                                <span class="campo-icono"><i class="fa-solid fa-lock"></i></span>
                                <div class="campo-cuerpo">
                                    <label for="reg-password2">Confirmar contraseña</label>
                                    <input type="password" id="reg-password2" name="password2" placeholder="Repite tu contraseña" autocomplete="new-password" required>
                                </div>
                                <button type="button" class="ojo" aria-label="Mostrar contraseña"><i class="fa-regular fa-eye"></i></button>
                            </div>
                            <p class="error" data-error="password2"></p>
                        </div>

                        <div class="campo-wrap">
                            <label class="check"><input type="checkbox" name="acepto" value="1"> <span>Acepto el tratamiento de mis datos personales.</span></label>
                            <a href="#" class="ck-link" data-abrir-tratamiento><i class="fa-solid fa-shield-halved"></i> Conocer el tratamiento de datos</a>
                            <p class="error" data-error="acepto"></p>
                        </div>

                        <button type="submit" class="btn-principal"><i class="fa-solid fa-user-plus"></i> <span>Crear cuenta</span></button>
                    </form>

                    <div class="cambio">
                        <span class="cambio-icono"><i class="fa-regular fa-user"></i></span>
                        <div>¿Ya tienes una cuenta?<br><a href="login.php" data-ir="login" class="enlace">Inicia sesión <i class="fa-solid fa-chevron-right"></i></a></div>
                    </div>
                </section>

                <!-- ============ RECUPERAR · PASO 1: CORREO ============ -->
                <section class="panel" data-panel="rec1" <?= $panel_inicial === 'rec1' ? '' : 'hidden' ?>>
                    <span class="panel-icono"><i class="fa-solid fa-key"></i></span>
                    <h1>Recuperar <span class="acento">contraseña</span></h1>
                    <p class="panel-sub">Escribe el correo de tu cuenta y te enviaremos un código de 8 dígitos.</p>

                    <div class="alerta" role="alert" hidden></div>

                    <form data-accion="recuperar_correo" novalidate>
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="accion" value="recuperar_correo">

                        <div class="campo-wrap">
                            <div class="campo">
                                <span class="campo-icono"><i class="fa-regular fa-envelope"></i></span>
                                <div class="campo-cuerpo">
                                    <label for="rec1-correo">Correo electrónico</label>
                                    <input type="email" id="rec1-correo" name="correo" placeholder="ejemplo@correo.com" autocomplete="email" required>
                                </div>
                            </div>
                            <p class="error" data-error="correo"></p>
                        </div>

                        <button type="submit" class="btn-principal"><i class="fa-regular fa-paper-plane"></i> <span>Enviar código</span></button>
                    </form>

                    <div class="cambio">
                        <span class="cambio-icono"><i class="fa-solid fa-arrow-left"></i></span>
                        <div>¿Ya la recordaste?<br><a href="login.php" data-ir="login" class="enlace">Volver a iniciar sesión <i class="fa-solid fa-chevron-right"></i></a></div>
                    </div>
                </section>

                <!-- ============ RECUPERAR · PASO 2: PIN ============ -->
                <section class="panel" data-panel="rec2" hidden>
                    <span class="panel-icono"><i class="fa-solid fa-envelope-circle-check"></i></span>
                    <h1>Revisa tu <span class="acento">correo</span></h1>
                    <p class="panel-sub">Enviamos un código de 8 dígitos a <strong id="rec-correo-oculto">tu correo</strong>. Vence en 15 minutos.</p>

                    <div class="alerta" role="alert" hidden></div>

                    <form data-accion="recuperar_pin" novalidate>
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="accion" value="recuperar_pin">

                        <div class="campo-wrap">
                            <div class="campo">
                                <span class="campo-icono"><i class="fa-solid fa-hashtag"></i></span>
                                <div class="campo-cuerpo">
                                    <label for="rec2-pin">Código de 8 dígitos</label>
                                    <input type="text" id="rec2-pin" name="pin" class="entrada-pin" inputmode="numeric" maxlength="8" placeholder="00000000" autocomplete="one-time-code" required>
                                </div>
                            </div>
                            <p class="error" data-error="pin"></p>
                        </div>

                        <button type="submit" class="btn-principal"><i class="fa-solid fa-circle-check"></i> <span>Validar código</span></button>
                    </form>

                    <div class="reenviar">
                        ¿No te llegó? <button type="button" class="enlace-boton" id="btnReenviar" disabled>Reenviar código</button>
                    </div>

                    <div class="cambio">
                        <span class="cambio-icono"><i class="fa-solid fa-arrow-left"></i></span>
                        <div>¿Escribiste mal el correo?<br><a href="login.php?modo=recuperar" data-ir="rec1" class="enlace">Cambiar correo <i class="fa-solid fa-chevron-right"></i></a></div>
                    </div>
                </section>

                <!-- ============ RECUPERAR · PASO 3: NUEVA CONTRASEÑA ============ -->
                <section class="panel" data-panel="rec3" hidden>
                    <span class="panel-icono"><i class="fa-solid fa-lock-open"></i></span>
                    <h1>Nueva <span class="acento">contraseña</span></h1>
                    <p class="panel-sub">Código verificado. Elige una contraseña nueva para tu cuenta.</p>

                    <div class="alerta" role="alert" hidden></div>

                    <form data-accion="recuperar_nueva" novalidate>
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="accion" value="recuperar_nueva">

                        <div class="campo-wrap">
                            <div class="campo">
                                <span class="campo-icono"><i class="fa-solid fa-lock"></i></span>
                                <div class="campo-cuerpo">
                                    <label for="rec3-password">Nueva contraseña</label>
                                    <input type="password" id="rec3-password" name="password" placeholder="Mínimo 8, con letras y números" autocomplete="new-password" required>
                                </div>
                                <button type="button" class="ojo" aria-label="Mostrar contraseña"><i class="fa-regular fa-eye"></i></button>
                            </div>
                            <p class="error" data-error="password"></p>
                        </div>

                        <div class="campo-wrap">
                            <div class="campo">
                                <span class="campo-icono"><i class="fa-solid fa-lock"></i></span>
                                <div class="campo-cuerpo">
                                    <label for="rec3-password2">Confirmar contraseña</label>
                                    <input type="password" id="rec3-password2" name="password2" placeholder="Repite la contraseña" autocomplete="new-password" required>
                                </div>
                                <button type="button" class="ojo" aria-label="Mostrar contraseña"><i class="fa-regular fa-eye"></i></button>
                            </div>
                            <p class="error" data-error="password2"></p>
                        </div>

                        <button type="submit" class="btn-principal"><i class="fa-solid fa-floppy-disk"></i> <span>Guardar contraseña</span></button>
                    </form>
                </section>

            </div>
        </div>

    </main>
  </div>
</div>

<script src="js/login.js?v=<?= filemtime(__DIR__ . '/js/login.js') ?>"></script>
<?php include __DIR__ . '/includes/cookies.php'; ?>
</body>

</html>
