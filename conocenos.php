<?php
// ============================================================
// RutaNexo — Conócenos
// Las fotos se buscan en img/conocenos/ (ver LEEME.txt ahí).
// Si una foto no existe se muestra un recuadro que dice cuál subir.
// ============================================================
require_once 'config/conexion.php';
require_once 'includes/funciones.php';

iniciar_sesion();

/** Pinta una foto, o un recuadro reservado si todavía no está el archivo. */
function foto(string $archivo, string $etiqueta, string $alt, ?string $alterno = null): void
{
    $ruta = "img/conocenos/$archivo";
    if (!is_file(__DIR__ . '/' . $ruta) && $alterno && is_file(__DIR__ . '/' . $alterno)) {
        $ruta = $alterno;
    }
    if (is_file(__DIR__ . '/' . $ruta)) {
        echo '<div class="foto"><img src="' . e($ruta) . '" alt="' . e($alt) . '" loading="lazy"></div>';
    } else {
        echo '<div class="foto pendiente"><div><i class="fa-regular fa-image"></i><b>' . e($etiqueta) . '</b><small>img/conocenos/' . e($archivo) . '</small></div></div>';
    }
}

// Ruta de ejemplo para la tarjeta del encabezado (la primera de la BD, o una de muestra)
$ruta_viva = ['nombre' => 'Ruta 21', 'origen' => 'Terminal de Transportes', 'destino' => 'Jordán',
              'paradas' => [['nombre' => 'Terminal de Transportes'], ['nombre' => 'Parque Murillo Toro'], ['nombre' => 'Jordán']], 'real' => false];
$pdo = (new Database())->conectar();
if ($pdo) {
    try {
        $r = $pdo->query('SELECT id_ruta, nombre_ruta, origen, destino FROM ruta ORDER BY id_ruta LIMIT 1')->fetch();
        if ($r) {
            $st = $pdo->prepare('SELECT nombre FROM paradero WHERE id_ruta = ? ORDER BY orden_parada LIMIT 6');
            $st->execute([$r['id_ruta']]);
            $ruta_viva = ['nombre' => $r['nombre_ruta'], 'origen' => $r['origen'], 'destino' => $r['destino'],
                          'paradas' => $st->fetchAll(), 'real' => true];
        }
    } catch (PDOException $e) {
        error_log('Error en conocenos.php: ' . $e->getMessage());
    }
}

$pagina_activa      = 'nosotros';
$titulo_pagina      = 'Conócenos';
$descripcion_pagina = 'Conoce RutaNexo: la plataforma que organiza el transporte público de Ibagué para pasajeros, conductores y empresas.';
include 'includes/head_publico.php';
include 'includes/navbar.php';
?>
<main>
    <!-- ===== Encabezado ===== -->
    <section class="pg-cab">
        <div class="contenedor hero-conoce">
            <div>
                <nav class="migas" aria-label="Migas de pan"><a href="index.php">Inicio</a> <span>/</span> <span>Conócenos</span></nav>
                <span class="etiqueta">Cómo conocernos</span>
                <h1>Una movilidad más <span class="acento">humana</span> e inteligente para Ibagué</h1>
                <p class="intro">RutaNexo es una plataforma para gestionar el transporte público: rutas, paraderos, vehículos, conductores y mantenimiento, en un solo lugar y al alcance de todos.</p>
            </div>
            <aside class="ruta-viva" aria-label="Ejemplo de ruta">
                <div class="cab"><span><?= $ruta_viva['real'] ? 'Ruta registrada' : 'Ejemplo de ruta' ?></span><span class="estado rec">En servicio</span></div>
                <h3><?= e($ruta_viva['nombre']) ?></h3>
                <p class="trayecto" style="color:var(--muted);margin:4px 0 0"><?= e($ruta_viva['origen']) ?> <i class="fa-solid fa-arrow-right"></i> <?= e($ruta_viva['destino']) ?></p>
                <ol class="paradas">
                    <?php foreach ($ruta_viva['paradas'] as $p): ?><li><b><?= e($p['nombre']) ?></b></li><?php endforeach; ?>
                </ol>
                <div class="salida"><i class="fa-regular fa-clock"></i> Consulta el recorrido completo en la página de rutas</div>
            </aside>
        </div>
    </section>

    <!-- ===== Quiénes somos ===== -->
    <section class="seccion">
        <div class="contenedor fila-dos">
            <div>
                <span class="etiqueta">Quiénes somos</span>
                <h2>Tecnología para el <span class="acento">transporte</span> de nuestra ciudad</h2>
                <p>RutaNexo es una solución tecnológica para gestionar vehículos, conductores, rutas, mantenimiento y documentos del transporte público de Ibagué.</p>
                <ul class="lista-check">
                    <li><i class="fa-solid fa-check"></i><span>Los <b>pasajeros</b> consultan rutas, paraderos y horarios fácilmente.</span></li>
                    <li><i class="fa-solid fa-check"></i><span>Las <b>empresas</b> controlan su flota, sus conductores y sus documentos (SOAT, revisión técnico-mecánica).</span></li>
                    <li><i class="fa-solid fa-check"></i><span>La información llega a tiempo a pasajeros y administradores.</span></li>
                </ul>
            </div>
            <?php foto('quienes.jpg', 'Foto: Ibagué y su transporte', 'Ibagué y su transporte público', 'img/slide-2.jpg'); ?>
        </div>
    </section>

    <!-- ===== Por qué nace ===== -->
    <section class="seccion">
        <div class="contenedor">
            <span class="etiqueta">¿Por qué nace RutaNexo?</span>
            <h2>Porque la información del transporte estaba <span class="acento">dispersa</span></h2>
            <p style="max-width:760px">Muchas empresas llevan sus vehículos, conductores y mantenimientos en papel o en hojas sueltas, y los pasajeros no saben qué ruta tomar ni a qué hora. RutaNexo reúne todo en un solo sistema.</p>
            <div class="tags">
                <span class="tag"><i class="fa-solid fa-folder-open"></i>Información dispersa</span>
                <span class="tag"><i class="fa-solid fa-wrench"></i>Mantenimiento reactivo</span>
                <span class="tag"><i class="fa-regular fa-file-lines"></i>Documentación</span>
                <span class="tag"><i class="fa-solid fa-sliders"></i>Control operativo</span>
            </div>
        </div>
    </section>

    <!-- ===== Qué nos diferencia ===== -->
    <section class="seccion">
        <div class="contenedor">
            <span class="etiqueta">¿Qué nos diferencia?</span>
            <h2>Pensado para <span class="acento">todos</span> los que se mueven por la ciudad</h2>
            <div class="dif-grid">
                <div class="dif"><i class="fa-solid fa-magnifying-glass-location"></i><div><h3>Consultas claras y a la mano</h3><p>Busca rutas, paraderos y tiempos estimados desde cualquier dispositivo.</p></div></div>
                <div class="dif"><i class="fa-solid fa-id-card-clip"></i><div><h3>Conductores verificados</h3><p>Control de licencias y calificaciones de los pasajeros para viajar con confianza.</p></div></div>
                <div class="dif"><i class="fa-solid fa-ticket"></i><div><h3>Pasajes digitales</h3><p>Pagos y recargas digitales para que no dependas del efectivo.</p></div></div>
                <div class="dif"><i class="fa-solid fa-recycle"></i><div><h3>Reciclaje que recompensa</h3><p>Acumula puntos por reciclar y cámbialos por beneficios en tu transporte.</p></div></div>
            </div>
        </div>
    </section>

    <!-- ===== CTA ===== -->
    <section class="seccion cta-seccion">
        <div class="contenedor">
            <div class="cta">
                <div>
                    <h2>¿Listo para conocer las <span>rutas</span>?</h2>
                    <p>Encuentra el recorrido que necesitas en pocos segundos.</p>
                </div>
                <a href="rutas.php" class="btn btn-teal"><i class="fa-solid fa-route"></i> Ver rutas</a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/landing.js?v=<?= filemtime(__DIR__ . '/js/landing.js') ?>"></script>
</body>
</html>
