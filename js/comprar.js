// ================================================================
// comprar.js — formulario de compra y página de estado
//  - muestra el nombre del archivo elegido y avisa si pesa más de 5 MB
//  - resalta el método de pago elegido
//  - botón "Copiar" de la referencia
//  - evita doble envío
// ================================================================
document.addEventListener('DOMContentLoaded', function () {
    var MAX = 5 * 1024 * 1024;

    document.querySelectorAll('.cp-file').forEach(function (input) {
        var etiqueta = document.querySelector('label[for="' + input.id + '"]');
        var nombre = etiqueta && etiqueta.querySelector('b');
        var info = etiqueta && etiqueta.querySelector('small');
        var textoNombre = nombre ? nombre.textContent : '';
        var textoInfo = info ? info.textContent : '';

        function mostrar() {
            var f = input.files && input.files[0];
            if (!f) { if (nombre) nombre.textContent = textoNombre; if (info) info.textContent = textoInfo; etiqueta.classList.remove('lleno', 'mal'); return; }
            var mb = (f.size / 1048576).toFixed(2).replace('.', ',') + ' MB';
            nombre.textContent = f.name;
            if (f.size > MAX) { info.textContent = 'Pesa ' + mb + ': el máximo es 5 MB'; etiqueta.classList.add('mal'); etiqueta.classList.remove('lleno'); input.value = ''; }
            else { info.textContent = mb; etiqueta.classList.add('lleno'); etiqueta.classList.remove('mal'); }
        }
        input.addEventListener('change', mostrar);

        if (etiqueta) {
            ['dragenter', 'dragover'].forEach(function (ev) { etiqueta.addEventListener(ev, function (e) { e.preventDefault(); etiqueta.classList.add('arrastra'); }); });
            ['dragleave', 'drop'].forEach(function (ev) { etiqueta.addEventListener(ev, function () { etiqueta.classList.remove('arrastra'); }); });
            etiqueta.addEventListener('drop', function (e) {
                e.preventDefault();
                if (e.dataTransfer && e.dataTransfer.files.length) { input.files = e.dataTransfer.files; mostrar(); }
            });
        }
    });

    document.querySelectorAll('.cp-metodo input').forEach(function (radio) {
        function marcar() {
            document.querySelectorAll('.cp-metodo').forEach(function (l) { l.classList.toggle('sel', l.querySelector('input').checked); });
        }
        radio.addEventListener('change', marcar);
        marcar();
    });

    document.querySelectorAll('[data-copiar]').forEach(function (b) {
        b.addEventListener('click', function () {
            var texto = b.getAttribute('data-copiar');
            var listo = function () { var t = b.innerHTML; b.innerHTML = '<i class="fa-solid fa-check"></i> Copiado'; setTimeout(function () { b.innerHTML = t; }, 1600); };
            if (navigator.clipboard) { navigator.clipboard.writeText(texto).then(listo, function () { }); }
        });
    });

    var nit = document.getElementById('nit');
    if (nit) nit.addEventListener('input', function () { nit.value = nit.value.replace(/[^\d.\-\s]/g, ''); });

    var form = document.getElementById('formCompra');
    if (form) form.addEventListener('submit', function () {
        var b = document.getElementById('btnEnviar');
        if (b) { b.disabled = true; b.innerHTML = 'Enviando… <i class="fa-solid fa-spinner fa-spin"></i>'; }
    });
});
