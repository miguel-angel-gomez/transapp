<?php
// ============================================================
// RutaNexo — Acceso del Super Administrador (página aparte)
//
// Paso 1: correo + contraseña (solo busca en la tabla super_admin)
// Paso 2: código de 6 dígitos que llega al correo (vence en 10 min, 5 intentos)
// Los administradores y usuarios normales entran por login.php.
// Se llega escribiendo la URL:  index.php/login-superadmin   (también /superadmin.php y /login-superadmin)
// ============================================================
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/config/enlaces.php';

iniciar_sesion();

// Carpeta del proyecto en la URL (p. ej. /transapp/). Se usa como <base> porque esta página
// también se abre como index.php/login-superadmin, donde las rutas relativas se romperían.
$base_url = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';

const HASH_RELLENO_SA = '$2y$10$ljDYoC25ScOLqf27n8RPm.xJEebSpjtaS4V0pG2NJH/MKqH/eHzQO';
const SA_CODIGO_MINUTOS = 10;
const SA_REENVIO_SEG    = 60;

function sa_correo_oculto(string $correo): string
{
    [$u, $d] = array_pad(explode('@', $correo, 2), 2, '');
    return mb_substr($u, 0, 2) . '***@' . $d;
}

function sa_olvidar(): void
{
    unset($_SESSION['sa_pend']);
}

/** Genera el código, lo guarda con vigencia y lo envía. Devuelve null si todo bien o el mensaje de error. */
function sa_enviar_codigo(PDO $pdo, array $cuenta): ?string
{
    // En el servidor el correo debe estar configurado en modo smtp; si no, el código nunca llegaría.
    if (correo_en_modo_log() && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
        return 'El envío de correo no está configurado en el servidor (config/mail.php).';
    }
    $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    guardar_pin($pdo, $cuenta, $codigo, SA_CODIGO_MINUTOS);
    if (!enviar_codigo_acceso($cuenta['correo'], $cuenta['nombre'], $codigo)) {
        borrar_pin($pdo, $cuenta);
        return 'No se pudo enviar el código al correo. Intenta de nuevo.';
    }
    $_SESSION['sa_pend']['enviado'] = time();
    $_SESSION['sa_pend']['intentos'] = 0;
    return null;
}

function sa_error_campos(array $errores, string $mensaje = 'Revisa los campos marcados.'): void
{
    json_salida(['ok' => false, 'mensaje' => $mensaje, 'errores' => $errores], 422);
}

// ------------------------------------------------------------
// Acciones (POST, JSON)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        json_salida(['ok' => false, 'mensaje' => 'La página venció. Recárgala e intenta de nuevo.'], 403);
    }
    $pdo = (new Database())->conectar();
    if (!$pdo) {
        json_salida(['ok' => false, 'mensaje' => 'No hay conexión con la base de datos.'], 503);
    }
    $accion = (string) ($_POST['accion'] ?? '');

    // ---- Paso 1: correo y contraseña ----
    if ($accion === 'sa_login') {
        $correo = mb_strtolower(trim((string) ($_POST['correo'] ?? '')));
        $clave  = (string) ($_POST['password'] ?? '');
        $errores = [];
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) { $errores['correo'] = 'Escribe un correo válido.'; }
        if ($clave === '') { $errores['password'] = 'Escribe tu contraseña.'; }
        if ($errores) { sa_error_campos($errores); }

        if (intentos_recientes('sa_login', 600) >= 5) {
            $min = (int) ceil(segundos_para_reintentar('sa_login', 600) / 60);
            json_salida(['ok' => false, 'mensaje' => "Demasiados intentos fallidos. Intenta de nuevo en $min min."], 429);
        }

        $cuenta   = buscar_super_admin($pdo, $correo);
        $clave_ok = password_verify($clave, $cuenta['clave'] ?? HASH_RELLENO_SA);
        if (!$cuenta || !$clave_ok) {
            registrar_intento('sa_login');
            json_salida(['ok' => false, 'mensaje' => 'Correo o contraseña incorrectos.'], 401);
        }
        if ((int) $cuenta['id_estados'] !== ESTADO_HABILITADO) {
            json_salida(['ok' => false, 'mensaje' => 'Esta cuenta no está habilitada.'], 403);
        }

        session_regenerate_id(true);
        $_SESSION['sa_pend'] = ['documento' => $cuenta['documento'], 'correo' => $cuenta['correo']];
        if ($err = sa_enviar_codigo($pdo, $cuenta)) {
            sa_olvidar();
            json_salida(['ok' => false, 'mensaje' => $err], 500);
        }
        limpiar_intentos('sa_login');
        json_salida(['ok' => true, 'paso' => 'codigo', 'correo_oculto' => sa_correo_oculto($cuenta['correo']),
                     'espera' => SA_REENVIO_SEG]);
    }

    // Los pasos siguientes necesitan haber pasado el paso 1
    $pend = $_SESSION['sa_pend'] ?? null;
    if (!$pend) {
        json_salida(['ok' => false, 'mensaje' => 'La sesión venció. Empieza de nuevo.', 'paso' => 'login'], 409);
    }
    $cuenta = buscar_super_admin($pdo, (string) $pend['correo']);
    if (!$cuenta) {
        sa_olvidar();
        json_salida(['ok' => false, 'mensaje' => 'La sesión venció. Empieza de nuevo.', 'paso' => 'login'], 409);
    }

    // ---- Reenviar código ----
    if ($accion === 'sa_reenviar') {
        $falta = SA_REENVIO_SEG - (time() - (int) ($pend['enviado'] ?? 0));
        if ($falta > 0) {
            json_salida(['ok' => false, 'mensaje' => "Espera $falta s para pedir otro código.", 'espera' => $falta], 429);
        }
        if ($err = sa_enviar_codigo($pdo, $cuenta)) {
            json_salida(['ok' => false, 'mensaje' => $err], 500);
        }
        json_salida(['ok' => true, 'mensaje' => 'Te enviamos un código nuevo.', 'espera' => SA_REENVIO_SEG]);
    }

    // ---- Volver al paso 1 ----
    if ($accion === 'sa_volver') {
        borrar_pin($pdo, $cuenta);
        sa_olvidar();
        json_salida(['ok' => true, 'paso' => 'login']);
    }

    // ---- Paso 2: verificar el código ----
    if ($accion === 'sa_codigo') {
        $codigo = preg_replace('/\D/', '', (string) ($_POST['codigo'] ?? ''));
        if (strlen($codigo) !== 6) {
            sa_error_campos(['codigo' => 'Escribe los 6 dígitos del código.']);
        }
        $guardado = obtener_pin($pdo, $cuenta);
        $_SESSION['sa_pend']['intentos'] = (int) ($_SESSION['sa_pend']['intentos'] ?? 0) + 1;

        // El código de recuperación (8 dígitos) nunca sirve aquí: solo cuenta uno de 6
        if (!$guardado || strlen($guardado['pin']) !== 6 || !hash_equals($guardado['pin'], $codigo)) {
            $quedan = 5 - $_SESSION['sa_pend']['intentos'];
            if ($quedan <= 0) {
                borrar_pin($pdo, $cuenta);
                sa_olvidar();
                json_salida(['ok' => false, 'mensaje' => 'Demasiados intentos. Inicia sesión de nuevo.', 'paso' => 'login'], 429);
            }
            sa_error_campos(['codigo' => "Código incorrecto. Te quedan $quedan " . ($quedan === 1 ? 'intento.' : 'intentos.')], 'Código incorrecto.');
        }
        if (!$guardado['vigente']) {
            borrar_pin($pdo, $cuenta);
            json_salida(['ok' => false, 'mensaje' => 'El código venció. Pide uno nuevo.', 'vencido' => true], 410);
        }

        // Código correcto: ahora sí se abre la sesión del Super Administrador
        borrar_pin($pdo, $cuenta);
        sa_olvidar();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $cuenta['documento'];
        $_SESSION['usuario'] = [
            'documento' => $cuenta['documento'],
            'nombre'    => $cuenta['nombre'],
            'apellido'  => $cuenta['apellido'],
            'correo'    => $cuenta['correo'],
            'id_rol'    => (int) $cuenta['id_rol'],
            'origen'    => 'super_admin',
        ];
        json_salida(['ok' => true, 'redirigir' => $base_url . 'admin/index.php']);
    }

    json_salida(['ok' => false, 'mensaje' => 'Acción no válida.'], 400);
}

// ------------------------------------------------------------
// Página
// ------------------------------------------------------------
// Si ya es Super Administrador con sesión abierta, va directo a su panel
if (($_SESSION['usuario']['origen'] ?? '') === 'super_admin') {
    header('Location: ' . $base_url . 'admin/index.php');
    exit;
}

$csrf = csrf_token();
// Si dejó el código a medias, se retoma en el paso 2
$pend = $_SESSION['sa_pend'] ?? null;
$paso_inicial = $pend ? 'codigo' : 'login';
$correo_pend  = $pend ? sa_correo_oculto((string) $pend['correo']) : '';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <base href="<?= e($base_url) ?>">
    <title>RutaNexo — Acceso Super Administrador</title>
    <link rel="icon" type="image/png" href="img/logo-pin.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/base.css?v=<?= filemtime(__DIR__ . '/css/base.css') ?>">
    <link rel="stylesheet" href="css/style_superadmin.css?v=<?= filemtime(__DIR__ . '/css/style_superadmin.css') ?>">
</head>
<body class="sa-body">
<main class="sa-wrap">
    <a href="index.php" class="sa-logo" aria-label="RutaNexo, ir al inicio"><img src="img/logo.png" alt="RutaNexo"></a>

    <section class="sa-card" id="saCard" data-paso="<?= e($paso_inicial) ?>">
        <div class="sa-icono"><i class="fa-solid fa-user-shield"></i></div>
        <span class="sa-etiqueta">Acceso restringido</span>

        <!-- ===== Paso 1: correo y contraseña ===== -->
        <div data-paso-panel="login" <?= $paso_inicial === 'login' ? '' : 'hidden' ?>>
            <h1>Super <span class="acento">Administrador</span></h1>
            <p class="sa-sub">Ingresa con tu cuenta. Después te enviaremos un código de 6 dígitos a tu correo.</p>
            <div class="sa-alerta" role="alert" hidden></div>
            <form data-accion="sa_login" novalidate>
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="accion" value="sa_login">
                <div class="sa-campo-wrap">
                    <label for="sa-correo">Correo electrónico</label>
                    <div class="sa-campo"><i class="fa-regular fa-envelope"></i>
                        <input type="email" id="sa-correo" name="correo" autocomplete="username" placeholder="correo@dominio.com" required></div>
                    <p class="sa-error" data-error="correo"></p>
                </div>
                <div class="sa-campo-wrap">
                    <label for="sa-pass">Contraseña</label>
                    <div class="sa-campo"><i class="fa-solid fa-lock"></i>
                        <input type="password" id="sa-pass" name="password" autocomplete="current-password" placeholder="Tu contraseña" required>
                        <button type="button" class="sa-ojo" data-ojo aria-label="Mostrar contraseña"><i class="fa-regular fa-eye"></i></button></div>
                    <p class="sa-error" data-error="password"></p>
                </div>
                <button class="sa-btn" type="submit">Continuar <i class="fa-solid fa-arrow-right"></i></button>
            </form>
        </div>

        <!-- ===== Paso 2: código de 6 dígitos ===== -->
        <div data-paso-panel="codigo" <?= $paso_inicial === 'codigo' ? '' : 'hidden' ?>>
            <h1>Código de <span class="acento">verificación</span></h1>
            <p class="sa-sub">Enviamos un código de 6 dígitos a <b data-correo><?= e($correo_pend) ?></b>. Vence en <?= SA_CODIGO_MINUTOS ?> minutos.</p>
            <?php if (correo_en_modo_log() && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)): ?>
                <p class="sa-sub"><small>Modo local: el correo no se envía, el código queda escrito en <code>storage/mail.log</code>. Para recibirlo en tu correo configura <code>config/mail.php</code> en modo <code>smtp</code>.</small></p>
            <?php endif; ?>
            <div class="sa-alerta" role="alert" hidden></div>
            <form data-accion="sa_codigo" novalidate>
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="accion" value="sa_codigo">
                <div class="sa-campo-wrap">
                    <label for="sa-codigo" class="sr-only">Código de 6 dígitos</label>
                    <input type="text" id="sa-codigo" name="codigo" class="sa-codigo" inputmode="numeric" pattern="[0-9]*"
                           maxlength="6" autocomplete="one-time-code" placeholder="••••••" required>
                    <p class="sa-error" data-error="codigo"></p>
                </div>
                <button class="sa-btn" type="submit"><i class="fa-solid fa-shield-halved"></i> Verificar y entrar</button>
            </form>
            <div class="sa-pie-form">
                <button type="button" class="sa-link" data-reenviar>Reenviar código</button>
                <button type="button" class="sa-link" data-volver><i class="fa-solid fa-arrow-left"></i> Cambiar de cuenta</button>
            </div>
        </div>
    </section>

    <a href="index.php" class="sa-volver"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>
</main>
<script src="js/superadmin.js?v=<?= filemtime(__DIR__ . '/js/superadmin.js') ?>"></script>
</body>
</html>
