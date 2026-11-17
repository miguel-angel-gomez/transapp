<?php
// ============================================================
// RutaNexo — Rutas: lista completa con paraderos y estado del servicio
// Estado de cada ruta (tabla viaje): 7 = En ejecución, 6 = Agendado.
// ============================================================
require_once 'config/conexion.php';
require_once 'includes/funciones.php';

iniciar_sesion();

$pdo = (new Database())->conectar();

$rutas = [];
$stats = ['rutas' => 0, 'paraderos' => 0, 'vehiculos' => 0, 'viajes_hoy' => 0];
$error_bd = ($pdo === null);

if ($pdo) {
    try {
        $rutas = $pdo->query(
            "SELECT r.id_ruta, r.nombre_ruta, r.origen, r.destino, r.distancia, r.tiempo_estimado,
                    e.razon_social AS empresa,
                    (SELECT COUNT(*) FROM viaje v WHERE v.id_ruta = r.id_ruta AND v.id_estados = 7) AS en_curso,
                    (SELECT COUNT(*) FROM viaje v WHERE v.id_ruta = r.id_ruta AND v.id_estados = 6
                        AND (v.fecha_salida IS NULL OR v.fecha_salida >= NOW())) AS programados
               FROM ruta r
               LEFT JOIN empresa e ON e.id_empresa = r.id_empresa
              ORDER BY (r.codigo IS NULL OR r.codigo NOT REGEXP '^[0-9]+$'), CAST(r.codigo AS UNSIGNED), r.codigo, r.nombre_ruta"
        )->fetchAll();

        // Paraderos agrupados por ruta
        $paradas = [];
        foreach ($pdo->query('SELECT id_ruta, nombre, ubicacion FROM paradero ORDER BY id_ruta, orden_parada')->fetchAll() as $p) {
            $paradas[$p['id_ruta']][] = $p;
        }
        foreach ($rutas as &$r) {
            $r['paradas'] = $paradas[$r['id_ruta']] ?? [];
            $r['estado']  = $r['en_curso'] > 0 ? 'rec' : ($r['programados'] > 0 ? 'pro' : 'sin');
        }
        unset($r);

        $stats['rutas']      = count($rutas);
        $stats['paraderos']  = (int) $pdo->query('SELECT COUNT(*) FROM paradero')->fetchColumn();
        $stats['vehiculos']  = (int) $pdo->query('SELECT COUNT(*) FROM vehiculo WHERE id_estados = 1')->fetchColumn();
        $stats['viajes_hoy'] = (int) $pdo->query('SELECT COUNT(*) FROM viaje WHERE DATE(fecha_salida) = CURDATE()')->fetchColumn();
    } catch (PDOException $e) {
        error_log('Error en rutas.php: ' . $e->getMessage());
        $error_bd = true;
    }
}

$textos_estado = ['rec' => 'En recorrido', 'pro' => 'Programada', 'sin' => 'Sin viaje'];

$pagina_activa      = 'rutas';
$titulo_pagina      = 'Rutas';
$descripcion_pagina = 'Explora las rutas del transporte público de Ibagué, sus paraderos y el estado del servicio.';
include 'includes/head_publico.php';
include 'includes/navbar.php';
?>
<main>
    <section class="pg-cab">
        <div class="contenedor">
            <nav class="migas" aria-label="Migas de pan"><a href="index.php">Inicio</a> <span>/</span> <span>Rutas</span></nav>
            <span class="etiqueta">Movilidad urbana · Ibagué</span>
            <h1>Explora las <span class="acento">rutas</span> del transporte público</h1>
            <p class="intro">Consulta el recorrido de cada ruta, sus paraderos y si tiene servicio en este momento. La información sale de los registros de las empresas en RutaNexo.</p>

            <div class="contadores">
                <div class="contador"><i class="fa-solid fa-route"></i><div><strong><?= $stats['rutas'] ?></strong><span>Rutas registradas</span></div></div>
                <div class="contador"><i class="fa-solid fa-location-dot"></i><div><strong><?= $stats['paraderos'] ?></strong><span>Paraderos</span></div></div>
                <div class="contador"><i class="fa-solid fa-bus"></i><div><strong><?= $stats['vehiculos'] ?></strong><span>Vehículos habilitados</span></div></div>
                <div class="contador"><i class="fa-regular fa-calendar-check"></i><div><strong><?= $stats['viajes_hoy'] ?></strong><span>Viajes de hoy</span></div></div>
            </div>
        </div>
    </section>

    <section class="rutas-pg">
        <div class="contenedor">
            <?php if ($error_bd): ?>
                <div class="vacio"><i class="fa-solid fa-triangle-exclamation"></i>No hay conexión con la base de datos. Revisa que MySQL esté encendido.</div>
            <?php else: ?>
                <div class="filtros">
                    <div class="tabs" role="tablist" aria-label="Filtrar por estado">
                        <button type="button" class="tab activo" data-filtro="todas">Todas</button>
                        <button type="button" class="tab" data-filtro="rec">En recorrido</button>
                        <button type="button" class="tab" data-filtro="pro">Programadas</button>
                        <button type="button" class="tab" data-filtro="sin">Sin viaje</button>
                    </div>
                    <label class="filtro-buscar"><i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" id="filtroTexto" placeholder="Buscar ruta, barrio o paradero…" maxlength="80" autocomplete="off">
                    </label>
                </div>

                <div class="leyenda">
                    <span><i class="punto rec"></i>En recorrido</span>
                    <span><i class="punto pro"></i>Programada</span>
                    <span><i class="punto sin"></i>Sin viaje</span>
                    <span class="contador-vis" id="contadorVis" aria-live="polite"><?= count($rutas) ?> rutas visibles</span>
                </div>

                <div class="rutas-grid" id="rutasGrid">
                    <?php foreach ($rutas as $r):
                        $busca = mb_strtolower($r['nombre_ruta'] . ' ' . $r['origen'] . ' ' . $r['destino'] . ' ' . ($r['empresa'] ?? '') . ' ' .
                                 implode(' ', array_map(fn($p) => $p['nombre'] . ' ' . $p['ubicacion'], $r['paradas']))); ?>
                        <article class="ruta-card" id="ruta-<?= (int) $r['id_ruta'] ?>" data-estado="<?= e($r['estado']) ?>" data-busca="<?= e($busca) ?>">
                            <div class="ruta-card-cab">
                                <div>
                                    <h3><?= e($r['nombre_ruta']) ?></h3>
                                    <p class="trayecto"><?= e($r['origen'] ?? '—') ?> <i class="fa-solid fa-arrow-right"></i> <?= e($r['destino'] ?? '—') ?></p>
                                </div>
                                <span class="estado <?= e($r['estado']) ?>"><?= e($textos_estado[$r['estado']]) ?></span>
                            </div>
                            <ul class="ruta-datos">
                                <?php if ($r['distancia'] !== null): ?><li><i class="fa-solid fa-ruler"></i><?= e(number_format((float) $r['distancia'], 1, ',', '.')) ?> km</li><?php endif; ?>
                                <?php if ($r['tiempo_estimado']): ?><li><i class="fa-regular fa-clock"></i><?= e($r['tiempo_estimado']) ?></li><?php endif; ?>
                                <li><i class="fa-solid fa-location-dot"></i><?= count($r['paradas']) ?> paraderos</li>
                                <?php if ($r['empresa']): ?><li><i class="fa-regular fa-building"></i><?= e($r['empresa']) ?></li><?php endif; ?>
                            </ul>
                            <?php if ($r['paradas']): ?>
                                <details>
                                    <summary>Ver recorrido <i class="fa-solid fa-chevron-down"></i></summary>
                                    <ol class="paradas">
                                        <?php foreach ($r['paradas'] as $p): ?>
                                            <li><b><?= e($p['nombre'] ?? 'Paradero') ?></b><?php if ($p['ubicacion']): ?><small><?= e($p['ubicacion']) ?></small><?php endif; ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                </details>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                    <div class="vacio" id="sinResultados" <?= $rutas ? 'hidden' : '' ?>>
                        <i class="fa-solid fa-route"></i>
                        <span id="textoVacio"><?= $rutas ? 'No hay rutas que coincidan con tu búsqueda.' : 'Aún no hay rutas registradas. Muy pronto las verás aquí.' ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="cta-seccion">
        <div class="contenedor">
            <div class="cta">
                <div>
                    <h2>¿Tienes una empresa de <span>transporte</span>?</h2>
                    <p>Registra tu flota, tus conductores y tus rutas en RutaNexo y mantén todo en orden.</p>
                </div>
                <a href="conocenos.php" class="btn btn-teal"><i class="fa-solid fa-circle-info"></i> Conócenos</a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/landing.js?v=<?= filemtime(__DIR__ . '/js/landing.js') ?>"></script>
<script src="js/rutas.js?v=<?= filemtime(__DIR__ . '/js/rutas.js') ?>"></script>
</body>
</html>
