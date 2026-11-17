<?php
// ============================================================
// Funciones compartidas: sesión, CSRF, respuestas JSON, límites
// de intentos y consultas de cuentas (usuario / super_admin).
// ============================================================

const SESION_RECORDAR_SEGUNDOS = 2592000;   // 30 días ("Recordar sesión")
const ESTADO_HABILITADO        = 1;         // estado_registro.id_estados = 1 (Habilitado)
const ROL_USUARIO              = 5;         // rol.id_rol = 5 (Usuario / pasajero)

/**
 * Inicia la sesión con un nombre propio (así no se mezcla con otros
 * proyectos PHP que corran en localhost) y con cookies seguras.
 */
function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('RUTANEXO_SID');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string) SESION_RECORDAR_SEGUNDOS);
    session_set_cookie_params([
        'lifetime' => 0,                              // se borra al cerrar el navegador
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Escapa texto para imprimirlo en HTML. */
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valido(?string $token): bool
{
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

// ---------------------------------------------------------------
// Respuestas JSON (las usa login.php y el buscador del index)
// ---------------------------------------------------------------
function json_salida(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ---------------------------------------------------------------
// Límite de intentos (guardado en la sesión)
// ---------------------------------------------------------------
function intentos_recientes(string $clave, int $ventana_seg): int
{
    $ahora = time();
    $lista = array_filter($_SESSION['rl'][$clave] ?? [], fn($t) => $t > $ahora - $ventana_seg);
    $_SESSION['rl'][$clave] = array_values($lista);
    return count($lista);
}

function registrar_intento(string $clave): void
{
    $_SESSION['rl'][$clave][] = time();
}

function limpiar_intentos(string $clave): void
{
    unset($_SESSION['rl'][$clave]);
}

/** Segundos que faltan para que el intento más antiguo salga de la ventana. */
function segundos_para_reintentar(string $clave, int $ventana_seg): int
{
    $lista = $_SESSION['rl'][$clave] ?? [];
    return $lista ? max(1, min($lista) + $ventana_seg - time()) : 0;
}

// ---------------------------------------------------------------
// Cuentas: la plataforma tiene dos tablas con credenciales
//   - usuario      (pasajeros, conductores, técnicos, propietarios, admin)
//   - super_admin  (Super Administrador, rol 6)
// ---------------------------------------------------------------

/**
 * Busca una cuenta por correo en `usuario` y, si no está, en `super_admin`.
 * Devuelve un arreglo con los mismos nombres de clave sin importar la tabla.
 */
function buscar_cuenta(PDO $pdo, string $correo): ?array
{
    $stmt = $pdo->prepare(
        "SELECT documento, nombre, apellido, correo, contrasena AS clave, id_rol, id_estados, 'usuario' AS origen
           FROM usuario WHERE correo = ? LIMIT 1"
    );
    $stmt->execute([$correo]);
    $cuenta = $stmt->fetch();
    if ($cuenta) {
        return $cuenta;
    }

    $stmt = $pdo->prepare(
        "SELECT documento, name AS nombre, COALESCE(apellido, '') AS apellido, email AS correo,
                password AS clave, id_rol, id_estados, 'super_admin' AS origen
           FROM super_admin WHERE email = ? LIMIT 1"
    );
    $stmt->execute([$correo]);
    return $stmt->fetch() ?: null;
}

/** Busca SOLO en `super_admin` (el login del Super Administrador es aparte: superadmin.php). */
function buscar_super_admin(PDO $pdo, string $correo): ?array
{
    $stmt = $pdo->prepare(
        "SELECT documento, name AS nombre, COALESCE(apellido, '') AS apellido, email AS correo,
                password AS clave, id_rol, id_estados, 'super_admin' AS origen
           FROM super_admin WHERE email = ? LIMIT 1"
    );
    $stmt->execute([$correo]);
    return $stmt->fetch() ?: null;
}

/** ¿El correo ya existe en alguna de las dos tablas? */
function correo_existe(PDO $pdo, string $correo): bool
{
    return buscar_cuenta($pdo, $correo) !== null;
}

/** Nombres de tabla/columna seguros según el origen de la cuenta (lista blanca). */
function columnas_cuenta(string $origen): array
{
    return $origen === 'super_admin'
        ? ['tabla' => 'super_admin', 'correo' => 'email', 'clave' => 'password']
        : ['tabla' => 'usuario',     'correo' => 'correo', 'clave' => 'contrasena'];
}

/** Guarda el PIN (8 dígitos) con vigencia de 15 minutos medida con el reloj de MySQL. */
function guardar_pin(PDO $pdo, array $cuenta, string $pin, int $minutos = 15): void
{
    $c = columnas_cuenta($cuenta['origen']);
    $stmt = $pdo->prepare("UPDATE {$c['tabla']} SET pin = ?, pin_expira = DATE_ADD(NOW(), INTERVAL " . (int) $minutos . " MINUTE) WHERE documento = ?");
    $stmt->execute([$pin, $cuenta['documento']]);
}

/** Devuelve ['pin' => ..., 'vigente' => bool] o null si no hay PIN guardado. */
function obtener_pin(PDO $pdo, array $cuenta): ?array
{
    $c = columnas_cuenta($cuenta['origen']);
    $stmt = $pdo->prepare("SELECT pin, (pin_expira IS NOT NULL AND pin_expira > NOW()) AS vigente FROM {$c['tabla']} WHERE documento = ?");
    $stmt->execute([$cuenta['documento']]);
    $fila = $stmt->fetch();
    if (!$fila || $fila['pin'] === null) {
        return null;
    }
    return ['pin' => (string) $fila['pin'], 'vigente' => (bool) $fila['vigente']];
}

function borrar_pin(PDO $pdo, array $cuenta): void
{
    $c = columnas_cuenta($cuenta['origen']);
    $pdo->prepare("UPDATE {$c['tabla']} SET pin = NULL, pin_expira = NULL WHERE documento = ?")
        ->execute([$cuenta['documento']]);
}

/** Cambia la contraseña y limpia el PIN. */
function cambiar_clave(PDO $pdo, array $cuenta, string $clave_nueva): void
{
    $c    = columnas_cuenta($cuenta['origen']);
    $hash = password_hash($clave_nueva, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE {$c['tabla']} SET {$c['clave']} = ?, pin = NULL, pin_expira = NULL WHERE documento = ?")
        ->execute([$hash, $cuenta['documento']]);
}

/** Valida la fuerza mínima de una contraseña. Devuelve el mensaje de error o null. */
function error_clave(string $clave): ?string
{
    if (strlen($clave) < 8) {
        return 'Debe tener mínimo 8 caracteres.';
    }
    if (strlen($clave) > 72) {
        return 'Debe tener máximo 72 caracteres.';
    }
    if (!preg_match('/[A-Za-z]/', $clave) || !preg_match('/\d/', $clave)) {
        return 'Debe incluir letras y números.';
    }
    return null;
}

/** A dónde se manda a cada rol después de iniciar sesión. */
function ruta_inicio_por_rol(int $id_rol): string
{
    if ($id_rol === 6) { return 'admin/index.php'; }
    return $id_rol === 1 ? 'administrador/index.php' : 'dash.php';
}

/**
 * Destino tras iniciar sesión: si venía de "Comprar" en la landing (plan elegido)
 * y es un usuario normal, va directo al formulario de compra.
 */
function destino_tras_login(int $id_rol, string $origen): string
{
    $plan = (int) ($_SESSION['plan_deseado'] ?? 0);
    if ($plan > 0 && $origen === 'usuario' && $id_rol === ROL_USUARIO) {
        return 'comprar.php?plan=' . $plan;
    }
    return ruta_inicio_por_rol($id_rol);
}
