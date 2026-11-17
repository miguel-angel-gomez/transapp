// rutas.js — filtra las rutas por estado y por texto, sin recargar la página.
(function () {
    var grid = document.getElementById('rutasGrid');
    if (!grid) return;

    var tarjetas = Array.prototype.slice.call(grid.querySelectorAll('.ruta-card'));
    var tabs = document.querySelectorAll('.tab');
    var input = document.getElementById('filtroTexto');
    var contador = document.getElementById('contadorVis');
    var vacio = document.getElementById('sinResultados');
    var estado = 'todas';

    function quitarTildes(t) {
        return t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    }

    function aplicar() {
        var q = quitarTildes(input.value.trim());
        var visibles = 0;
        tarjetas.forEach(function (c) {
            var ok = (estado === 'todas' || c.dataset.estado === estado) &&
                     (q === '' || quitarTildes(c.dataset.busca).indexOf(q) !== -1);
            c.hidden = !ok;
            if (ok) visibles++;
        });
        contador.textContent = visibles + (visibles === 1 ? ' ruta visible' : ' rutas visibles');
        if (tarjetas.length) vacio.hidden = visibles !== 0;
    }

    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            tabs.forEach(function (x) { x.classList.remove('activo'); });
            t.classList.add('activo');
            estado = t.dataset.filtro;
            aplicar();
        });
    });
    input.addEventListener('input', aplicar);

    // Si llegan con #ruta-ID, abre el recorrido de esa ruta
    if (location.hash.indexOf('#ruta-') === 0) {
        var objetivo = document.getElementById(location.hash.slice(1));
        var det = objetivo && objetivo.querySelector('details');
        if (det) det.open = true;
    }
})();
