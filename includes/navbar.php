<?php
// ============================================================
// Barra de navegación (la usan las páginas públicas)
// Antes de incluirla: iniciar_sesion() y, opcional,
//   $pagina_activa = 'inicio' | 'rutas' | 'nosotros';
// ============================================================
$pagina_activa = $pagina_activa ?? 'inicio';
$sesion_activa = !empty($_SESSION['usuario']);
$raiz = $raiz ?? '';
?>
<header class="navbar" id="navbar">
    <div class="contenedor navbar-in">
        <a href="<?= $raiz ?>index.php" class="marca" aria-label="RutaNexo, inicio">
            <img src="<?= $raiz ?>img/logo.png" alt="RutaNexo — Movilidad inteligente para Ibagué">
        </a>

        <button class="nav-hamb" id="navHamb" type="button" aria-label="Abrir menú" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>

        <ul class="nav-links">
            <li><a href="<?= $raiz ?>index.php" class="<?= $pagina_activa === 'inicio' ? 'activo' : '' ?>">Inicio</a></li>
            <li><a href="<?= $raiz ?>rutas.php" class="<?= $pagina_activa === 'rutas' ? 'activo' : '' ?>">Rutas</a></li>
            <li><a href="<?= $raiz ?>conocenos.php" class="<?= $pagina_activa === 'nosotros' ? 'activo' : '' ?>">Conócenos</a></li>
        </ul>

        <div class="nav-acciones">
            <?php if ($sesion_activa): ?>
                <a href="<?= $raiz ?>dash.php" class="btn btn-borde-claro"><i class="fa-regular fa-user"></i> Mi panel</a>
            <?php else: ?>
                <a href="<?= $raiz ?>login.php" class="btn btn-borde-claro"><i class="fa-regular fa-user"></i> Iniciar sesión</a>
                <a href="<?= $raiz ?>login.php?modo=registro" class="btn btn-teal"><i class="fa-solid fa-user-plus"></i> Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</header>
