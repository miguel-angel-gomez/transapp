// ================================================================
// login.js
//  - Tarjeta que gira al cambiar de formulario
//  - Envío de los formularios por AJAX a login.php (responde JSON)
//  - Validaciones rápidas en el navegador (el servidor valida de nuevo)
// ================================================================
document.addEventListener('DOMContentLoaded', function () {

    var tarjeta = document.getElementById('tarjeta');
    if (!tarjeta) return;

    var paneles = {};
    tarjeta.querySelectorAll('[data-panel]').forEach(function (p) {
        paneles[p.dataset.panel] = p;
    });

    var actual = tarjeta.dataset.inicial || 'login';
    var girando = false;
    var reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ----------------------------------------------------------
    // Alertas y errores por campo
    // ----------------------------------------------------------
    function alertaDe(panel) {
        return panel.querySelector('.alerta');
    }

    function mostrarAlerta(panel, texto, tipo) {
        var caja = alertaDe(panel);
        if (!caja) return;
        if (!texto) { caja.hidden = true; caja.textContent = ''; return; }
        caja.className = 'alerta ' + (tipo === 'ok' ? 'alerta-ok' : 'alerta-error');
        caja.textContent = texto;
        caja.hidden = false;
    }

    function limpiarErrores(panel) {
        mostrarAlerta(panel, '');
        panel.querySelectorAll('.error').forEach(function (p) { p.textContent = ''; });
        panel.querySelectorAll('.campo.invalido').forEach(function (c) { c.classList.remove('invalido'); });
    }

    function errorCampo(form, nombre, mensaje) {
        var input = form.querySelector('[name="' + nombre + '"]');
        var texto = form.querySelector('[data-error="' + nombre + '"]');
        if (texto) texto.textContent = mensaje;
        if (input) {
            var campo = input.closest('.campo');
            if (campo) campo.classList.add('invalido');
        }
    }

    // ----------------------------------------------------------
    // Girar la tarjeta hacia otro panel
    // ----------------------------------------------------------
    function irA(destino, despues) {
        if (!paneles[destino] || destino === actual) {
            if (despues) despues();
            return;
        }
        if (girando) return;
        girando = true;

        function cambiar() {
            paneles[actual].hidden = true;
            limpiarErrores(paneles[destino]);
            paneles[destino].hidden = false;
            actual = destino;
        }
        function terminar() {
            girando = false;
            var primero = paneles[actual].querySelector('input:not([type="hidden"]):not([type="checkbox"])');
            if (primero) primero.focus({ preventScroll: true });
            if (despues) despues();
        }

        if (reducirMovimiento) {
            cambiar();
            terminar();
            return;
        }

        // 1) gira hasta quedar de canto  2) cambia el contenido  3) termina de girar
        tarjeta.classList.add('sale');
        setTimeout(function () {
            cambiar();
            tarjeta.classList.remove('sale');
            tarjeta.classList.add('entra');
            void tarjeta.offsetWidth;            // fuerza al navegador a aplicar el estado inicial
            tarjeta.classList.remove('entra');
            setTimeout(terminar, 290);
        }, 280);
    }

    // Enlaces "Regístrate aquí", "¿Olvidaste tu contraseña?", etc.
    tarjeta.querySelectorAll('[data-ir]').forEach(function (enlace) {
        enlace.addEventListener('click', function (e) {
            e.preventDefault();
            irA(enlace.dataset.ir);
        });
    });

    // ----------------------------------------------------------
    // Mostrar / ocultar contraseña
    // ----------------------------------------------------------
    tarjeta.querySelectorAll('.ojo').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var input = boton.closest('.campo').querySelector('input');
            var visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            boton.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            boton.firstElementChild.className = visible ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        });
    });

    // El código solo acepta números
    var campoPin = document.getElementById('rec2-pin');
    if (campoPin) {
        campoPin.addEventListener('input', function () {
            campoPin.value = campoPin.value.replace(/\D/g, '').slice(0, 8);
        });
    }

    // Quita el error de un campo cuando el usuario vuelve a escribir en él
    tarjeta.addEventListener('input', function (e) {
        var campo = e.target.closest('.campo');
        if (!campo) return;
        campo.classList.remove('invalido');
        var texto = campo.closest('.campo-wrap').querySelector('.error');
        if (texto) texto.textContent = '';
    });

    // ----------------------------------------------------------
    // Validación rápida en el navegador
    // ----------------------------------------------------------
    var regexCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var regexNombre = /^[A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ\s.'-]{1,99}$/;

    function validar(form) {
        var v = function (n) { var i = form.querySelector('[name="' + n + '"]'); return i ? i.value : ''; };
        var errores = {};

        function claveInvalida(valor) {
            if (valor.length < 8) return 'Debe tener mínimo 8 caracteres.';
            if (!/[A-Za-z]/.test(valor) || !/\d/.test(valor)) return 'Debe incluir letras y números.';
            return null;
        }

        switch (form.dataset.accion) {
            case 'login':
                if (!regexCorreo.test(v('correo').trim())) errores.correo = 'Escribe un correo válido.';
                if (v('password') === '') errores.password = 'Escribe tu contraseña.';
                break;

            case 'registro':
                if (!/^\d{5,12}$/.test(v('documento').trim())) errores.documento = 'Solo números, entre 5 y 12 dígitos.';
                if (!regexNombre.test(v('nombre').trim())) errores.nombre = 'Escribe tu nombre (solo letras).';
                if (!regexNombre.test(v('apellido').trim())) errores.apellido = 'Escribe tu apellido (solo letras).';
                if (!regexCorreo.test(v('correo').trim())) errores.correo = 'Escribe un correo válido.';
                if (v('telefono').trim() !== '' && !/^[0-9+\s-]{7,20}$/.test(v('telefono').trim())) errores.telefono = 'Teléfono no válido.';
                var e1 = claveInvalida(v('password'));
                if (e1) errores.password = e1;
                else if (v('password') !== v('password2')) errores.password2 = 'Las contraseñas no coinciden.';
                if (!form.querySelector('[name="acepto"]').checked) errores.acepto = 'Debes aceptar el tratamiento de datos.';
                break;

            case 'recuperar_correo':
                if (!regexCorreo.test(v('correo').trim())) errores.correo = 'Escribe un correo válido.';
                break;

            case 'recuperar_pin':
                if (!/^\d{8}$/.test(v('pin'))) errores.pin = 'Escribe los 8 dígitos del código.';
                break;

            case 'recuperar_nueva':
                var e2 = claveInvalida(v('password'));
                if (e2) errores.password = e2;
                else if (v('password') !== v('password2')) errores.password2 = 'Las contraseñas no coinciden.';
                break;
        }
        return errores;
    }

    // ----------------------------------------------------------
    // Estado "enviando…" del botón
    // ----------------------------------------------------------
    function cargando(form, activo) {
        var boton = form.querySelector('.btn-principal');
        if (!boton) return;
        var icono = boton.querySelector('i');
        if (activo) {
            boton.disabled = true;
            boton.dataset.icono = icono.className;
            icono.className = 'fa-solid fa-circle-notch fa-spin';
        } else {
            boton.disabled = false;
            if (boton.dataset.icono) icono.className = boton.dataset.icono;
        }
    }

    // ----------------------------------------------------------
    // Cuenta regresiva del botón "Reenviar código"
    // ----------------------------------------------------------
    var botonReenviar = document.getElementById('btnReenviar');
    var temporizador = null;

    function iniciarCuentaRegresiva(segundos) {
        if (!botonReenviar) return;
        clearInterval(temporizador);
        var restante = segundos;
        botonReenviar.disabled = true;
        botonReenviar.textContent = 'Reenviar código (' + restante + ' s)';
        temporizador = setInterval(function () {
            restante--;
            if (restante <= 0) {
                clearInterval(temporizador);
                botonReenviar.disabled = false;
                botonReenviar.textContent = 'Reenviar código';
            } else {
                botonReenviar.textContent = 'Reenviar código (' + restante + ' s)';
            }
        }, 1000);
    }

    // ----------------------------------------------------------
    // Enviar a login.php y manejar la respuesta JSON
    // ----------------------------------------------------------
    function procesarRespuesta(form, datos) {
        var panel = form.closest('[data-panel]');

        // Errores por campo
        if (datos.errores) {
            Object.keys(datos.errores).forEach(function (nombre) {
                errorCampo(form, nombre, datos.errores[nombre]);
            });
            var primero = form.querySelector('.campo.invalido input');
            if (primero) primero.focus();
            return;
        }

        // Inicio de sesión correcto
        if (datos.ok && datos.redirigir) {
            window.location.href = datos.redirigir;
            return;
        }

        // Hay que pasar a otro formulario (con o sin error)
        if (datos.panel) {
            var tipo = datos.ok ? 'ok' : 'error';

            if (datos.panel === 'rec2') {
                var spanCorreo = document.getElementById('rec-correo-oculto');
                if (spanCorreo && datos.correo_oculto) spanCorreo.textContent = datos.correo_oculto;
                var campo = paneles.rec2.querySelector('input[name="pin"]');
                if (campo) campo.value = '';
                iniciarCuentaRegresiva(60);
            }
            if (datos.panel === 'login' && datos.correo) {
                paneles.login.querySelector('input[name="correo"]').value = datos.correo;
                paneles.login.querySelector('input[name="password"]').value = '';
            }
            if (datos.panel === 'login' || datos.panel === 'rec1') {
                // Al volver al inicio se limpian las contraseñas y los códigos escritos
                tarjeta.querySelectorAll('input[type="password"], input[name="pin"]').forEach(function (i) { i.value = ''; });
            }

            irA(datos.panel, function () {
                if (datos.mensaje) mostrarAlerta(paneles[datos.panel], datos.mensaje, tipo);
            });
            return;
        }

        // Mensaje simple en el mismo formulario
        mostrarAlerta(panel, datos.mensaje || (datos.ok ? 'Listo.' : 'Ocurrió un error.'), datos.ok ? 'ok' : 'error');
    }

    function enviar(form, panel) {
        cargando(form, true);

        fetch('login.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'fetch' },
            body: new FormData(form),
            credentials: 'same-origin'
        })
            .then(function (respuesta) {
                return respuesta.json().then(
                    function (datos) { return datos; },
                    function () { return { ok: false, mensaje: 'El servidor respondió algo inesperado. Intenta de nuevo.' }; }
                );
            })
            .then(function (datos) {
                cargando(form, false);
                procesarRespuesta(form, datos);
            })
            .catch(function () {
                cargando(form, false);
                mostrarAlerta(panel, 'No pudimos conectar con el servidor. Revisa tu conexión e intenta de nuevo.', 'error');
            });
    }

    tarjeta.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var panel = form.closest('[data-panel]');
            limpiarErrores(panel);

            var errores = validar(form);
            var nombres = Object.keys(errores);
            if (nombres.length) {
                nombres.forEach(function (n) { errorCampo(form, n, errores[n]); });
                var primero = form.querySelector('.campo.invalido input');
                if (primero) primero.focus();
                return;
            }
            enviar(form, panel);
        });
    });

    // Reenviar código (usa el correo guardado en la sesión del servidor)
    if (botonReenviar) {
        botonReenviar.addEventListener('click', function () {
            var form = paneles.rec2.querySelector('form');
            var datos = new FormData();
            datos.append('csrf', form.querySelector('[name="csrf"]').value);
            datos.append('accion', 'recuperar_reenviar');
            botonReenviar.disabled = true;

            fetch('login.php', { method: 'POST', body: datos, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (resp.ok) {
                        mostrarAlerta(paneles.rec2, 'Te enviamos un código nuevo.', 'ok');
                        iniciarCuentaRegresiva(60);
                    } else if (resp.panel) {
                        procesarRespuesta(form, resp);
                    } else {
                        mostrarAlerta(paneles.rec2, resp.mensaje || 'No se pudo reenviar el código.', 'error');
                        botonReenviar.disabled = false;
                    }
                })
                .catch(function () {
                    mostrarAlerta(paneles.rec2, 'No pudimos conectar con el servidor.', 'error');
                    botonReenviar.disabled = false;
                });
        });
    }

    // Foco inicial en el primer campo del panel visible
    var inicio = paneles[actual].querySelector('input:not([type="hidden"]):not([type="checkbox"])');
    if (inicio) inicio.focus({ preventScroll: true });
});
