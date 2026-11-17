// superadmin.js — acceso en dos pasos (correo+contraseña → código de 6 dígitos)
document.addEventListener('DOMContentLoaded', function () {
    var card = document.getElementById('saCard');
    if (!card) return;

    var paneles = {};
    card.querySelectorAll('[data-paso-panel]').forEach(function (p) { paneles[p.dataset.pasoPanel] = p; });
    var csrf = card.querySelector('input[name="csrf"]').value;
    var cuenta = null;   // temporizador de reenvío

    function alerta(panel, texto, tipo) {
        var a = panel.querySelector('.sa-alerta');
        if (!texto) { a.hidden = true; a.textContent = ''; return; }
        a.className = 'sa-alerta' + (tipo === 'ok' ? ' ok' : '');
        a.textContent = texto; a.hidden = false;
    }
    function limpiar(panel) {
        alerta(panel, '');
        panel.querySelectorAll('.sa-error').forEach(function (p) { p.textContent = ''; });
        panel.querySelectorAll('.invalido').forEach(function (c) { c.classList.remove('invalido'); });
    }
    function errorCampo(panel, nombre, msg) {
        var t = panel.querySelector('[data-error="' + nombre + '"]');
        if (t) t.textContent = msg;
        var i = panel.querySelector('[name="' + nombre + '"]');
        if (i) (i.closest('.sa-campo') || i).classList.add('invalido');
    }
    function ir(paso) {
        Object.keys(paneles).forEach(function (k) { paneles[k].hidden = (k !== paso); });
        card.dataset.paso = paso;
        var foco = paneles[paso].querySelector('input:not([type=hidden])');
        if (foco) foco.focus();
    }
    function post(datos) {
        var fd = new FormData();
        Object.keys(datos).forEach(function (k) { fd.append(k, datos[k]); });
        fd.append('csrf', csrf);
        return fetch('superadmin.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json().catch(function () { return { ok: false, mensaje: 'Respuesta no válida del servidor.' }; }); })
            .catch(function () { return { ok: false, mensaje: 'No hay conexión con el servidor.' }; });
    }

    // ---- Reenvío con cuenta regresiva ----
    var btnReenviar = card.querySelector('[data-reenviar]');
    function cuentaRegresiva(seg) {
        clearInterval(cuenta);
        btnReenviar.disabled = true;
        (function tick() {
            if (seg <= 0) { btnReenviar.disabled = false; btnReenviar.textContent = 'Reenviar código'; return; }
            btnReenviar.textContent = 'Reenviar código (' + seg + ' s)';
            seg--; cuenta = setTimeout(tick, 1000);
        })();
    }
    if (card.dataset.paso === 'codigo') cuentaRegresiva(0);

    // ---- Mostrar/ocultar contraseña ----
    card.querySelectorAll('[data-ojo]').forEach(function (b) {
        b.addEventListener('click', function () {
            var i = b.parentElement.querySelector('input');
            var ver = i.type === 'password'; i.type = ver ? 'text' : 'password';
            b.querySelector('i').className = ver ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    });

    // ---- Solo números en el código ----
    var inpCodigo = card.querySelector('input[name="codigo"]');
    inpCodigo.addEventListener('input', function () { inpCodigo.value = inpCodigo.value.replace(/\D/g, '').slice(0, 6); });

    // ---- Envío de formularios ----
    card.querySelectorAll('form').forEach(function (f) {
        f.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var panel = f.closest('[data-paso-panel]');
            limpiar(panel);
            var accion = f.dataset.accion, datos = { accion: accion }, ok = true;

            if (accion === 'sa_login') {
                datos.correo = f.correo.value.trim(); datos.password = f.password.value;
                if (!/^\S+@\S+\.\S+$/.test(datos.correo)) { errorCampo(panel, 'correo', 'Escribe un correo válido.'); ok = false; }
                if (!datos.password) { errorCampo(panel, 'password', 'Escribe tu contraseña.'); ok = false; }
            } else {
                datos.codigo = f.codigo.value;
                if (!/^\d{6}$/.test(datos.codigo)) { errorCampo(panel, 'codigo', 'Escribe los 6 dígitos del código.'); ok = false; }
            }
            if (!ok) return;

            var btn = f.querySelector('.sa-btn'); btn.disabled = true;
            post(datos).then(function (r) {
                btn.disabled = false;
                if (r.ok && r.redirigir) { window.location.href = r.redirigir; return; }
                if (r.ok && r.paso === 'codigo') {
                    card.querySelector('[data-correo]').textContent = r.correo_oculto;
                    f.password.value = '';
                    ir('codigo'); cuentaRegresiva(r.espera || 60);
                    return;
                }
                if (r.paso === 'login') { ir('login'); alerta(paneles.login, r.mensaje); return; }
                if (r.errores) { Object.keys(r.errores).forEach(function (k) { errorCampo(panel, k, r.errores[k]); }); inpCodigo.select(); return; }
                alerta(panel, r.mensaje || 'No se pudo completar la acción.');
            });
        });
    });

    btnReenviar.addEventListener('click', function () {
        var panel = paneles.codigo; limpiar(panel);
        btnReenviar.disabled = true;
        post({ accion: 'sa_reenviar' }).then(function (r) {
            if (r.paso === 'login') { ir('login'); alerta(paneles.login, r.mensaje); return; }
            alerta(panel, r.mensaje, r.ok ? 'ok' : 'error');
            cuentaRegresiva(r.espera || 0);
        });
    });

    card.querySelector('[data-volver]').addEventListener('click', function () {
        post({ accion: 'sa_volver' }).then(function () { ir('login'); });
    });
});
