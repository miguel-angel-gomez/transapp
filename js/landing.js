// ================================================================
// landing.js — menú móvil y resaltado del enlace activo al hacer scroll
// ================================================================
document.addEventListener('DOMContentLoaded', function () {

    // ---- Menú hamburguesa (pantallas pequeñas) ----
    var navbar = document.getElementById('navbar');
    var boton = document.getElementById('navHamb');

    if (navbar && boton) {
        boton.addEventListener('click', function () {
            var abierto = navbar.classList.toggle('abierto');
            boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        });

        // Al elegir un enlace se cierra el menú
        navbar.querySelectorAll('.nav-links a').forEach(function (enlace) {
            enlace.addEventListener('click', function () {
                navbar.classList.remove('abierto');
                boton.setAttribute('aria-expanded', 'false');
            });
        });
    }
});
