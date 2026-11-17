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
    global $secciones, $raiz, $titulo_pagina, $descripcion_pagina, $css_extra, $pagina_activa, $yo;
    $raiz = '../';
    $titulo_pagina = $titulo;
    $descripcion_pagina = $intro ?: 'Panel del administrador de empresa en RutaNexo.';
    $css_extra = ['css/style_panel.css'];
    $pagina_activa = '';
    include __DIR__ . '/../includes/head_publico.php';
    include __DIR__ . '/../includes/navbar.php';
    ?>
<main>
    <section class="pg-cab">
        <div class="contenedor">
            <span class="etiqueta">Administrador · <?= e($yo['razon_social']) ?></span>
            <h1><?= e($titulo) ?></h1>
            <?php if ($intro): ?><p class="intro"><?= e($intro) ?></p><?php endif; ?>
        </div>
    </section>
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
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="../js/landing.js"></script>
</body>

</html>
<?php
}
