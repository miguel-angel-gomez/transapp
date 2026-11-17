<?php
// ============================================================
// RutaNexo — Panel del Administrador de empresa (base compartida)
//   Solo entra un usuario con rol Administrador (1) cuya empresa esté
//   Habilitada. Todo lo que gestiona se limita a SU empresa.
//   Cada página la incluye con:  require __DIR__ . '/_panel.php';
// ============================================================
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/pagos.php';          // ROL_ADMINISTRADOR
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/panel_helpers.php';

iniciar_sesion();
header('Cache-Control: no-store, no-cache, must-revalidate');

$u = $_SESSION['usuario'] ?? null;
if (!$u) { header('Location: ../login.php'); exit; }
if (($u['origen'] ?? '') !== 'usuario') { http_response_code(403); exit('Este panel es para administradores de empresa.'); }

$pdo = (new Database())->conectar();
if (!$pdo) { http_response_code(503); exit('No hay conexión con la base de datos.'); }

// Datos frescos desde la BD (el rol o el estado pudieron cambiar después de iniciar sesión)
$st = $pdo->prepare('SELECT u.documento, u.nombre, u.apellido, u.correo, u.id_rol, u.id_estados, u.id_empresa,
                            e.razon_social, e.nit, e.id_estados AS estado_empresa
                       FROM usuario u LEFT JOIN empresa e ON e.id_empresa = u.id_empresa
                      WHERE u.documento = ?');
$st->execute([$u['documento']]);
$yo = $st->fetch();
if (!$yo || (int) $yo['id_rol'] !== ROL_ADMINISTRADOR || (int) $yo['id_estados'] !== ESTADO_HABILITADO) {
    http_response_code(403);
    exit('Solo el Administrador de una empresa puede entrar aquí.');
}
if (!$yo['id_empresa'] || (int) $yo['estado_empresa'] !== ESTADO_HABILITADO) {
    http_response_code(403);
    exit('Tu empresa aún no está habilitada. Cuando se apruebe tu plan podrás gestionarla desde aquí.');
}
$id_empresa = (int) $yo['id_empresa'];

/** Usuario de ESTA empresa por documento (o null si no existe o es de otra empresa). */
function usuario_de_empresa(PDO $pdo, int $id_empresa, string $doc): ?array
{
    if (!ctype_digit($doc)) { return null; }
    $q = $pdo->prepare('SELECT * FROM usuario WHERE documento = ? AND id_empresa = ?');
    $q->execute([$doc, $id_empresa]);
    return $q->fetch() ?: null;
}

/** Roles que el administrador puede asignar (todos menos Super Administrador): id => [nombre_rol, descripcion]. */
function roles_asignables(PDO $pdo): array
{
    $out = [];
    foreach ($pdo->query('SELECT id_rol, nombre_rol, descripcion FROM rol WHERE id_rol <> 6 ORDER BY id_rol')->fetchAll() as $r) {
        $out[(int) $r['id_rol']] = $r;
    }
    return $out;
}

/**
 * Cambia el rol de un usuario de la empresa y deja listas las tablas propias del rol:
 *   Conductor → fila en `conductor` · Técnico → fila en `tecnico` · Propietario → fila en `titular_vehiculo`.
 * Al dejar de ser Conductor, su fila de `conductor` pasa a Deshabilitado (no se borra: puede tener historial).
 */
function asignar_rol(PDO $pdo, int $id_empresa, string $doc, int $rol_nuevo): bool
{
    $actual = usuario_de_empresa($pdo, $id_empresa, $doc);
    if (!$actual) { return false; }
    $rol_anterior = (int) $actual['id_rol'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE usuario SET id_rol = ? WHERE documento = ? AND id_empresa = ?')->execute([$rol_nuevo, $doc, $id_empresa]);

        $existe = function (string $tabla) use ($pdo, $doc): bool {
            $q = $pdo->prepare("SELECT 1 FROM $tabla WHERE documento = ? LIMIT 1");
            $q->execute([$doc]);
            return (bool) $q->fetchColumn();
        };
        if ($rol_nuevo === 2) {
            if ($existe('conductor')) {
                $pdo->prepare('UPDATE conductor SET id_estados = ?, id_empresa = ? WHERE documento = ?')->execute([ESTADO_HABILITADO, $id_empresa, $doc]);
            } else {
                $pdo->prepare('INSERT INTO conductor (id_conductor, id_empresa, documento, id_estados) SELECT COALESCE(MAX(id_conductor), 0) + 1, ?, ?, ? FROM conductor')->execute([$id_empresa, $doc, ESTADO_HABILITADO]);
            }
        } elseif ($rol_anterior === 2) {
            $pdo->prepare('UPDATE conductor SET id_estados = ? WHERE documento = ?')->execute([ESTADO_DESHABILITADO, $doc]);
        }
        if ($rol_nuevo === 3 && !$existe('tecnico')) {
            $pdo->prepare('INSERT INTO tecnico (id_tecnico, id_empresa, documento) SELECT COALESCE(MAX(id_tecnico), 0) + 1, ?, ? FROM tecnico')->execute([$id_empresa, $doc]);
        }
        if ($rol_nuevo === 4 && !$existe('titular_vehiculo')) {
            $pdo->prepare('INSERT INTO titular_vehiculo (id_propietario, documento) SELECT COALESCE(MAX(id_propietario), 0) + 1, ? FROM titular_vehiculo')->execute([$doc]);
        }
        $pdo->commit();
        return true;
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('asignar_rol: ' . $ex->getMessage());
        return false;
    }
}

$secciones = [
    'resumen'  => ['index.php',    'fa-gauge-high', 'Resumen'],
    'usuarios' => ['usuarios.php', 'fa-users',      'Usuarios'],
    'roles'    => ['roles.php',    'fa-user-tag',   'Roles'],
];

/** Abre la página: <head>, navbar, título y menú del panel. */
function panel_abrir(string $activa, string $titulo, string $intro = ''): void
{
    global $secciones, $yo, $u;
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> — Administración | RutaNexo</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="../img/logo-pin.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/style_panel.css">
    <style>
      body{margin:0;background:#f3f7f8;color:#173247;font-family:Inter,sans-serif}
      .panel-top{background:#102d43;color:#fff;padding:15px 4%;display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap}
      .panel-brand{display:flex;align-items:center;gap:12px;color:#fff;text-decoration:none;font:800 1.15rem Outfit,sans-serif}
      .panel-brand img{width:42px;height:42px;object-fit:contain;background:#fff;border-radius:12px;padding:4px}
      .panel-brand small{display:block;color:#b9d0dc;font:500 .75rem Inter,sans-serif}
      .panel-user{display:flex;align-items:center;gap:14px;color:#dce8ee;font-size:.88rem}
      .panel-logout{border:1px solid #547184;border-radius:9px;padding:9px 12px;color:#fff;background:transparent;font-weight:600;cursor:pointer}
      .panel-logout:hover{background:#c43c3c;border-color:#c43c3c}
      .panel-heading{background:linear-gradient(115deg,#153c55,#176c70);color:#fff;padding:30px 4%}
      .panel-heading .eyebrow{font-size:.76rem;text-transform:uppercase;letter-spacing:.12em;color:#bce9df;font-weight:700}
      .panel-heading h1{font:800 clamp(1.6rem,3vw,2.2rem) Outfit,sans-serif;margin:7px 0}
      .panel-heading p{margin:0;color:#d9e9ed;max-width:760px}
      .pn-pg{padding:26px 4% 70px;background:#f3f7f8;min-height:60vh}
      .pn-pg>.contenedor{max-width:1400px;margin:auto}
      .pn-menu{padding:12px;background:#fff;border:1px solid #e0e9ed;border-radius:14px;box-shadow:0 5px 20px #1732470b}
      .panel-footer{text-align:center;padding:18px;color:#718391;font-size:.8rem;background:#fff;border-top:1px solid #e3eaed}
      @media(max-width:600px){.panel-top{padding:12px 4%}.panel-user{width:100%;justify-content:space-between}.panel-heading{padding:24px 5%}}
    </style>
</head>
<body>
<header class="panel-top">
    <a class="panel-brand" href="index.php"><img src="../img/logo.png" alt=""><span>RutaNexo<small>Centro de control · <?= e($yo['razon_social']) ?></small></span></a>
    <div class="panel-user"><span><i class="fa-solid fa-user-tie"></i> <?= e(trim(($yo['nombre'] ?? '') . ' ' . ($yo['apellido'] ?? ''))) ?></span>
      <form method="post" action="../includes/logout.php" style="margin:0"><?= campo_csrf() ?><button class="panel-logout" type="submit"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</button></form>
    </div>
</header>
<main>
    <section class="panel-heading"><span class="eyebrow">Administrador de empresa · <?= e($yo['razon_social']) ?></span><h1><?= e($titulo) ?></h1><?php if ($intro): ?><p><?= e($intro) ?></p><?php endif; ?></section>
    <section class="pn-pg">
        <div class="contenedor">
            <nav class="pn-menu" aria-label="Secciones del panel">
                <?php foreach ($secciones as $clave => [$url, $icono, $texto]): ?>
                    <a href="<?= $url ?>" class="<?= $clave === $activa ? 'activo' : '' ?>"><i class="fa-solid <?= $icono ?>"></i> <?= e($texto) ?></a>
                <?php endforeach; ?>
            </nav>
            <?php if (!empty($_SESSION['flash_panel'])): $f = $_SESSION['flash_panel']; unset($_SESSION['flash_panel']); ?>
                <div class="pn-alerta <?= $f['tipo'] === 'ok' ? 'ok' : 'mal' ?>" role="<?= $f['tipo'] === 'ok' ? 'status' : 'alert' ?>">
                    <i class="fa-solid <?= $f['tipo'] === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i> <?= e($f['texto']) ?>
                </div>
            <?php endif; ?>
<?php
}

/** Cierra la página. */
function panel_cerrar(): void
{
    global $raiz;
    ?>
        </div>
    </section>
</main>
<footer class="panel-footer">RutaNexo · Panel privado de empresa</footer>
</body>

</html>
<?php
}
