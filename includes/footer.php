<?php require_once __DIR__ . '/../config/enlaces.php'; ?>
<footer class="pie">
    <div class="contenedor">
        <div class="pie-in">
            <a href="<?= $raiz ?? '' ?>index.php" class="marca" aria-label="RutaNexo, inicio">
                <img src="<?= $raiz ?? '' ?>img/logo.png" alt="RutaNexo — Movilidad inteligente para Ibagué">
            </a>

            <nav class="pie-links" aria-label="Enlaces del pie de página">
                <a href="<?= $raiz ?? '' ?>index.php">Inicio</a>
                <a href="<?= $raiz ?? '' ?>rutas.php">Rutas</a>
                <a href="<?= $raiz ?? '' ?>conocenos.php">Conócenos</a>
                <a href="mailto:contacto@rutanexo.com">Contacto</a>
                <a href="#" data-abrir-tratamiento>Tratamiento de datos</a>
                <a href="#" data-abrir-tratamiento="cookies">Cookies</a>
                <a href="<?= e(url_superadmin($raiz ?? '')) ?>" rel="nofollow">Super Admin</a>
            </nav>

            <div class="pie-redes">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-square-facebook"></i></a>
                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
                <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
            </div>

            <div class="pie-ciudad">
                <i class="fa-solid fa-mountain-sun"></i>
                <div>Ibagué<small>Tolima</small></div>
            </div>
        </div>

        <div class="pie-base">
            <span>&copy; <?= date('Y') ?> RutaNexo. Todos los derechos reservados.</span>
            <span class="lema">Juntos por una mejor movilidad</span>
        </div>
    </div>
</footer>
<?php include __DIR__ . '/cookies.php'; ?>
