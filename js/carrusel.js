// Carrusel de "¿Qué es RutaNexo?": pasa solo cada 5 s, con flechas y puntos.
(function () {
    var cont = document.getElementById('carrusel');
    if (!cont) return;
    var slides = cont.querySelectorAll('.carrusel-slide');
    var puntos = cont.querySelector('.carrusel-puntos');
    if (slides.length < 2) { cont.querySelectorAll('.carrusel-flecha').forEach(function (b) { b.hidden = true; }); return; }

    var actual = 0, timer = null;
    var botones = [];
    slides.forEach(function (_, i) {
        var b = document.createElement('button');
        b.type = 'button'; b.setAttribute('aria-label', 'Ir a la imagen ' + (i + 1));
        b.addEventListener('click', function () { ir(i); reiniciar(); });
        puntos.appendChild(b); botones.push(b);
    });

    function ir(n) {
        slides[actual].classList.remove('activo'); botones[actual].classList.remove('activo');
        actual = (n + slides.length) % slides.length;
        slides[actual].classList.add('activo'); botones[actual].classList.add('activo');
    }
    function reiniciar() { clearInterval(timer); timer = setInterval(function () { ir(actual + 1); }, 5000); }

    cont.querySelector('.ant').addEventListener('click', function () { ir(actual - 1); reiniciar(); });
    cont.querySelector('.sig').addEventListener('click', function () { ir(actual + 1); reiniciar(); });
    cont.addEventListener('mouseenter', function () { clearInterval(timer); });
    cont.addEventListener('mouseleave', reiniciar);
    ir(0); reiniciar();
})();
