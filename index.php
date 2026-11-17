<?php
// ============================================================
// RutaNexo — Landing page
//  - Búsqueda de rutas por AJAX (POST 'q' → JSON)
// ============================================================
require_once __DIR__ . '/config/enlaces.php';

// Acceso del Super Administrador:  index.php/login-superadmin  (su propio login con código de 6 dígitos)
$ruta_pedida = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
if (substr($ruta_pedida, -strlen('/index.php/' . RUTA_SUPERADMIN)) === '/index.php/' . RUTA_SUPERADMIN) {
    require __DIR__ . '/superadmin.php';
    exit;
}

require_once 'config/conexion.php';
require_once 'includes/funciones.php';

iniciar_sesion();

$db  = new Database();
$pdo = $db->conectar();   // puede ser null si MySQL está apagado

// ------------------------------------------------------------
// Consulta de rutas (la usan la búsqueda AJAX y la lista inicial)
// ------------------------------------------------------------
function consultar_rutas(PDO $pdo, string $termino, int $limite): array
{
    $limite = max(1, min(50, $limite));
    // Escapa los comodines de LIKE para que "%" o "_" se busquen como texto normal
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $termino) . '%';

    // Sin término de búsqueda: todas las rutas. Se decide en PHP (comparar un parámetro con '' en SQL
    // da "Illegal mix of collations" en algunos servidores).
    $busca = ($termino !== '');
    $donde = $busca
        ? "WHERE (r.nombre_ruta LIKE ? OR r.origen LIKE ? OR r.destino LIKE ?
                OR EXISTS (SELECT 1 FROM paradero p2
                            WHERE p2.id_ruta = r.id_ruta AND (p2.nombre LIKE ? OR p2.ubicacion LIKE ?)))"
        : '';

    $sql = "SELECT r.id_ruta, r.nombre_ruta, r.codigo, r.color, r.descripcion, r.origen, r.destino, r.distancia, r.tiempo_estimado,
                   e.razon_social AS empresa,
                   (SELECT COUNT(*) FROM paradero p WHERE p.id_ruta = r.id_ruta) AS paraderos,
                   (SELECT p.nombre FROM paradero p
                     WHERE p.id_ruta = r.id_ruta AND (p.nombre LIKE ? OR p.ubicacion LIKE ?)
                     ORDER BY p.orden_parada LIMIT 1) AS pasa_por
              FROM ruta r
              LEFT JOIN empresa e ON e.id_empresa = r.id_empresa
             {$donde}
             ORDER BY (r.codigo IS NULL OR r.codigo NOT REGEXP '^[0-9]+$'), CAST(r.codigo AS UNSIGNED), r.codigo, r.nombre_ruta
             LIMIT {$limite}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($busca ? [$like, $like, $like, $like, $like, $like, $like] : [$like, $like]);
    $rutas = $stmt->fetchAll();

    // Paraderos en orden (con coordenadas) para dibujar el recorrido en el mapa
    $paradas = [];
    if ($rutas) {
        $ids = array_column($rutas, 'id_ruta');
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $sp = $pdo->prepare("SELECT id_ruta, nombre, lat, lng FROM paradero
                              WHERE id_ruta IN ($marcas) ORDER BY id_ruta, orden_parada");
        $sp->execute($ids);
        foreach ($sp->fetchAll() as $p) {
            $paradas[$p['id_ruta']][] = [
                'nombre' => $p['nombre'],
                'lat'    => $p['lat'] !== null ? (float) $p['lat'] : null,
                'lng'    => $p['lng'] !== null ? (float) $p['lng'] : null,
            ];
        }
    }

    foreach ($rutas as &$r) {
        $r['paradas'] = $paradas[$r['id_ruta']] ?? [];
        if ($termino === '') {
            $r['pasa_por'] = null;           // sin búsqueda no tiene sentido "pasa por"
        }
        $r['paraderos'] = (int) $r['paraderos'];
        $r['distancia'] = $r['distancia'] !== null ? (float) $r['distancia'] : null;
    }
    return $rutas;
}

// ------------------------------------------------------------
// BÚSQUEDA AJAX
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['q'])) {
    if (!$pdo) {
        json_salida(['ok' => false, 'mensaje' => 'No hay conexión con la base de datos.'], 503);
    }
    $termino = trim(strip_tags((string) $_POST['q']));
    $termino = mb_substr($termino, 0, 80);
    $limite  = ($_POST['todas'] ?? '') === '1' ? 50 : 20;

    try {
        $total = (int) $pdo->query('SELECT COUNT(*) FROM ruta')->fetchColumn();
        json_salida(['ok' => true, 'total' => $total, 'rutas' => consultar_rutas($pdo, $termino, $limite)]);
    } catch (PDOException $e) {
        error_log('Error búsqueda rutas: ' . $e->getMessage());
        json_salida(['ok' => false, 'mensaje' => 'No se pudo realizar la búsqueda.'], 500);
    }
}

// ------------------------------------------------------------
// Datos para pintar la página
// ------------------------------------------------------------
$rutas_iniciales = ['total' => 0, 'rutas' => []];

if ($pdo) {
    try {
        $rutas_iniciales['total'] = (int) $pdo->query('SELECT COUNT(*) FROM ruta')->fetchColumn();
        $rutas_iniciales['rutas'] = consultar_rutas($pdo, '', 6);
    } catch (PDOException $e) {
        error_log('Error cargando landing: ' . $e->getMessage());
    }
}

// Imagen del hero: si pones img/hero.jpg (o .png/.webp) se usa en lugar de la ilustración
$img_hero = 'img/hero.jpg';

// Carrusel de "¿Qué es RutaNexo?": cualquier img/slide-N.jpg que exista
$slides = [];
for ($i = 1; $i <= 8; $i++) {
    if (is_file(__DIR__ . "/img/slide-$i.jpg")) { $slides[] = "img/slide-$i.jpg"; }
}

$pagina_activa = 'inicio';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RutaNexo — Movilidad inteligente para Ibagué</title>
    <meta name="description" content="Consulta rutas y paraderos del transporte público de Ibagué y gestiona tu flota desde un solo lugar.">
    <link rel="icon" type="image/png" href="img/logo-pin.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="libs/leaflet/leaflet.css">
    <link rel="stylesheet" href="css/base.css?v=<?= filemtime(__DIR__ . '/css/base.css') ?>">
    <link rel="stylesheet" href="css/style_index.css?v=<?= filemtime(__DIR__ . '/css/style_index.css') ?>">
</head>

<body>

<?php include 'includes/navbar.php'; ?>

<main>

    <!-- ==================== HERO ==================== -->
    <section class="hero">
        <div class="hero-arte" style="background-image: url('<?= e($img_hero) ?>')" aria-hidden="true">
        </div>
        <div class="contenedor hero-in">
            <div class="hero-texto">
                <p class="hero-lugar"><i class="fa-solid fa-location-dot"></i> Ibagué, Tolima</p>
                <h1>Tu ciudad en <span class="acento">movimiento</span></h1>
                <p class="hero-desc">
                    <strong>RutaNexo</strong> es la plataforma que te conecta con el transporte público de Ibagué.
                    Consulta rutas y paraderos, y gestiona tu operación desde un solo lugar.
                </p>
                <div class="hero-botones">
                    <a href="#buscador" class="btn btn-teal"><i class="fa-solid fa-magnifying-glass"></i> Explorar rutas</a>
                    <a href="conocenos.php" class="btn btn-borde"><i class="fa-solid fa-circle-info"></i> Conócenos</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== APARTADOS ==================== -->
    <section class="apartados" aria-label="Ventajas de RutaNexo">
        <div class="contenedor apartados-in">
            <div class="apartado">
                <span class="apartado-icono ic-teal"><i class="fa-solid fa-location-dot"></i></span>
                <div>
                    <h3>Rutas organizadas</h3>
                    <p>Encuentra fácilmente la ruta que necesitas.</p>
                </div>
            </div>
            <div class="apartado">
                <span class="apartado-icono ic-purple"><i class="fa-solid fa-shield-halved"></i></span>
                <div>
                    <h3>Más seguridad</h3>
                    <p>Información confiable y actualizada.</p>
                </div>
            </div>
            <div class="apartado">
                <span class="apartado-icono ic-green"><i class="fa-solid fa-leaf"></i></span>
                <div>
                    <h3>Un Ibagué más sostenible</h3>
                    <p>Reciclaje y puntos para un mejor futuro.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== BUSCADOR ==================== -->
    <section class="buscador" id="buscador">
        <div class="contenedor">
            <div class="buscador-caja">
                <label class="etiqueta" for="searchInput">Busca tu ruta</label>
                <div class="buscador-campo">
                    <span class="punto-verde" aria-hidden="true"></span>
                    <input type="search" id="searchInput" maxlength="80" autocomplete="off"
                           placeholder="¿A dónde vas? Busca tu ruta, barrio o paradero…">
                    <button type="button" id="searchBtn" class="buscador-btn" aria-label="Buscar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <button type="button" id="rutasToggle" class="buscador-flecha" aria-label="Ver todas las rutas"
                            aria-haspopup="listbox" aria-expanded="false" aria-controls="rutasDesplegable">
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                </div>

                <!-- Lista desplegable con todas las rutas -->
                <div id="rutasDesplegable" class="rutas-desplegable" role="listbox" aria-label="Todas las rutas de Ibagué" hidden>
                    <div class="rd-cab"><span>Todas las rutas</span><small id="rdTotal"></small></div>
                    <div class="rd-lista" id="rdLista"></div>
                </div>
            </div>

            <!-- Mapa del recorrido de la ruta elegida -->
            <section class="mapa-panel" id="mapaPanel" aria-label="Recorrido de la ruta" hidden>
                <div class="mapa-cab">
                    <span class="ruta-num" id="mapaCodigo"></span>
                    <div class="mapa-titulo">
                        <h3 id="mapaNombre"></h3>
                        <p id="mapaTrayecto"></p>
                    </div>
                    <button type="button" class="mapa-btn" id="mapaRepetir" aria-label="Repetir animación" title="Repetir animación"><i class="fa-solid fa-rotate-right"></i></button>
                    <button type="button" class="mapa-btn" id="mapaCerrar" aria-label="Cerrar mapa" title="Cerrar"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="mapa-cuerpo">
                    <div class="mapa-caja">
                        <div id="mapaRuta" class="mapa-ruta"></div>
                        <div class="mapa-estado" id="mapaEstado" hidden></div>
                    </div>
                    <aside class="mapa-lado">
                        <h4>Paraderos en orden</h4>
                        <ol class="mapa-paradas" id="mapaParadas"></ol>
                        <p class="mapa-nota" id="mapaPasa"></p>
                        <p class="mapa-nota mapa-aviso"><i class="fa-solid fa-circle-info"></i> Recorrido de referencia: las rutas pueden cambiar y la ubicación de los paraderos es aproximada.</p>
                    </aside>
                </div>
            </section>

            <div class="rutas-panel">
                <div class="rutas-cabecera">
                    <span class="etiqueta" id="rutasTitulo">Rutas disponibles</span>
                    <span class="rutas-contador" id="rutasContador"></span>
                </div>
                <div id="resultadosRutas" class="rutas-lista" aria-live="polite"
                     data-inicial="<?= e(json_encode($rutas_iniciales, JSON_UNESCAPED_UNICODE)) ?>"
                     data-conexion="<?= $pdo ? '1' : '0' ?>"></div>
                <a href="rutas.php" id="verTodas" class="ver-todas" hidden>Ver todas las rutas <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- ==================== ¿QUÉ ES RUTANEXO? ==================== -->
    <section class="que-es" id="que-es">
        <div class="contenedor">

            <div class="que-es-fila">
                <div class="carrusel" id="carrusel" role="region" aria-label="Imágenes de Ibagué">
                    <div class="carrusel-pista">
                        <?php foreach ($slides as $n => $src): ?>
                            <img src="<?= e($src) ?>" alt="Ibagué, imagen <?= $n + 1 ?>" class="carrusel-slide<?= $n === 0 ? ' activo' : '' ?><?= $n === 2 ? ' izq' : '' ?>"<?= $n ? ' loading="lazy"' : '' ?>>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="carrusel-flecha ant" aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" class="carrusel-flecha sig" aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                    <div class="carrusel-puntos"></div>
                </div>

                <div class="que-es-texto">
                    <h2>¿Qué es Ruta<span class="acento">Nexo</span>?</h2>
                    <p>
                        Es un sistema de gestión del transporte público de Ibagué. Reúne en un solo lugar las
                        rutas, los paraderos, los vehículos, los conductores y el mantenimiento de la flota,
                        para que los pasajeros encuentren su ruta fácilmente y las empresas operen con orden.
                    </p>
                    <p>
                        Cada empresa registra su flota, asigna conductores a los vehículos, programa viajes y
                        lleva el control de documentos como SOAT y revisión técnico-mecánica. Los pasajeros
                        consultan rutas, califican a los conductores y acumulan puntos por reciclar.
                    </p>

                    <ul class="principios">
                        <li><span><i class="fa-solid fa-gear"></i></span>
                            <div><strong>Tecnología y control</strong>Información centralizada y gestión eficiente de la operación.</div></li>
                        <li><span><i class="fa-regular fa-user"></i></span>
                            <div><strong>Para todos</strong>Pasajeros, conductores, técnicos, propietarios y empresas.</div></li>
                        <li><span><i class="fa-regular fa-heart"></i></span>
                            <div><strong>Comprometidos con Ibagué</strong>Un transporte público más organizado y accesible.</div></li>
                    </ul>
                </div>
            </div>

            <div class="modulos-cabecera">
                <h3>¿Qué puedes hacer en RutaNexo?</h3>
            </div>
            <div class="modulos">
                <article class="modulo"><i class="fa-solid fa-route"></i>
                    <h4>Rutas y paraderos</h4>
                    <p>Consulta recorridos, paraderos en orden, distancia y tiempo estimado de cada ruta.</p></article>
                <article class="modulo"><i class="fa-solid fa-bus"></i>
                    <h4>Flota y conductores</h4>
                    <p>Registra vehículos, licencias y asigna conductores a cada vehículo.</p></article>
                <article class="modulo"><i class="fa-solid fa-calendar-check"></i>
                    <h4>Viajes y tiquetes</h4>
                    <p>Programa salidas, controla el paso por paraderos y gestiona los tiquetes.</p></article>
                <article class="modulo"><i class="fa-solid fa-screwdriver-wrench"></i>
                    <h4>Mantenimiento</h4>
                    <p>Servicios, repuestos y tareas de los técnicos, siempre con su historial.</p></article>
                <article class="modulo"><i class="fa-solid fa-file-shield"></i>
                    <h4>Documentos al día</h4>
                    <p>Controla el vencimiento del SOAT, la tecnomecánica y el seguro de cada vehículo.</p></article>
                <article class="modulo"><i class="fa-solid fa-recycle"></i>
                    <h4>Reciclaje y puntos</h4>
                    <p>Recicla en los centros de acopio, acumula puntos y canjéalos por beneficios.</p></article>
            </div>

        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>

<script src="js/carrusel.js?v=<?= filemtime(__DIR__ . '/js/carrusel.js') ?>"></script>
<script src="js/landing.js?v=<?= filemtime(__DIR__ . '/js/landing.js') ?>"></script>
<script src="js/buscar_rutas_index.js?v=<?= filemtime(__DIR__ . '/js/buscar_rutas_index.js') ?>"></script>
<script src="libs/leaflet/leaflet.js"></script>
<script src="js/mapa_ruta.js?v=<?= filemtime(__DIR__ . '/js/mapa_ruta.js') ?>"></script>
</body>

</html>
