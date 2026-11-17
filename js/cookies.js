// ================================================================
// cookies.js
//  - Aviso de cookies: sale en cada página hasta que se ACEPTE (cookie rn_cookies).
//    "Rechazar" lo cierra solo en esta página: al cambiar de vista vuelve a salir.
//  - Ventana del tratamiento de datos / política de cookies, que se abre desde
//    cualquier enlace con el atributo data-abrir-tratamiento.
// ================================================================
(function () {
    'use strict';

    var NOMBRE = 'rn_cookies';

    function leerCookie() {
        return document.cookie.split(';').some(function (c) { return c.trim().indexOf(NOMBRE + '=1') === 0; });
    }
    function guardarCookie() {
        var seg = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = NOMBRE + '=1; max-age=31536000; path=/; SameSite=Lax' + seg;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var banner = document.getElementById('ckBanner');
        var modal = document.getElementById('ckModal');
        if (!banner || !modal) return;

        // ---------- Aviso de cookies ----------
        if (!leerCookie()) {
            banner.hidden = false;
            requestAnimationFrame(function () { banner.classList.add('visible'); });
        }
        function cerrarBanner() {
            banner.classList.remove('visible');
            setTimeout(function () { banner.hidden = true; }, 250);
        }
        document.getElementById('ckAceptar').addEventListener('click', function () { guardarCookie(); cerrarBanner(); });
        document.getElementById('ckRechazar').addEventListener('click', cerrarBanner);   // no se guarda nada: vuelve a salir en la siguiente página

        // ---------- Ventana de tratamiento de datos / cookies ----------
        var caja = modal.querySelector('.ck-modal-caja');
        var titulo = document.getElementById('ckModalTit');
        var tabs = modal.querySelectorAll('[data-ck-tab]');
        var paneles = modal.querySelectorAll('[data-ck-panel]');
        var cuerpo = modal.querySelector('.ck-modal-cuerpo');
        var previo = null;
        var TITULOS = { datos: 'Tratamiento de datos personales', cookies: 'Política de cookies' };

        function verPanel(cual) {
            tabs.forEach(function (t) {
                var activo = t.getAttribute('data-ck-tab') === cual;
                t.classList.toggle('activo', activo);
                t.setAttribute('aria-selected', activo ? 'true' : 'false');
            });
            paneles.forEach(function (p) { p.hidden = p.getAttribute('data-ck-panel') !== cual; });
            titulo.textContent = TITULOS[cual];
            cuerpo.scrollTop = 0;
        }
        function abrir(cual) {
            previo = document.activeElement;
            verPanel(cual === 'cookies' ? 'cookies' : 'datos');
            modal.hidden = false;
            document.body.classList.add('ck-sin-scroll');
            caja.focus();
        }
        function cerrar() {
            modal.hidden = true;
            document.body.classList.remove('ck-sin-scroll');
            if (previo && previo.focus) previo.focus();
        }

        document.addEventListener('click', function (e) {
            var a = e.target.closest ? e.target.closest('[data-abrir-tratamiento]') : null;
            if (!a) return;
            e.preventDefault();
            e.stopPropagation();
            abrir(a.getAttribute('data-abrir-tratamiento'));
        });
        tabs.forEach(function (t) { t.addEventListener('click', function () { verPanel(t.getAttribute('data-ck-tab')); }); });
        document.getElementById('ckCerrar').addEventListener('click', cerrar);
        document.getElementById('ckEntendido').addEventListener('click', cerrar);
        modal.addEventListener('mousedown', function (e) { if (e.target === modal) cerrar(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) cerrar();
        });
    });
})();
