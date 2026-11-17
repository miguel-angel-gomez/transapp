<?php
// ============================================================
// Aviso de cookies + ventana del "Tratamiento de datos personales".
// Se incluye al final de cada página (desde includes/footer.php, login.php y dash.php).
//   - Cookies: el aviso aparece en cada página hasta que el usuario ACEPTE (cookie rn_cookies).
//     Si rechaza, puede seguir navegando normal, pero el aviso vuelve a salir al cambiar de página.
//   - Cualquier enlace con el atributo  data-abrir-tratamiento  abre la ventana con el texto
//     (data-abrir-tratamiento="cookies" abre la política de cookies).
// Si la página está en una subcarpeta, define antes $raiz = '../'.
// ============================================================
$raiz = $raiz ?? '';
$v_ck = static fn(string $f): int => (int) @filemtime(__DIR__ . '/../' . $f);
?>
<link rel="stylesheet" href="<?= $raiz ?>css/style_cookies.css?v=<?= $v_ck('css/style_cookies.css') ?>">

<!-- ========== Aviso de cookies ========== -->
<div class="ck-banner" id="ckBanner" role="region" aria-label="Aviso de cookies" hidden>
    <div class="ck-banner-in">
        <span class="ck-ic" aria-hidden="true"><i class="fa-solid fa-cookie-bite"></i></span>
        <div class="ck-texto">
            <b>Usamos cookies</b>
            <p>Usamos cookies necesarias para que RutaNexo funcione (inicio de sesión y seguridad) y una cookie para recordar tu elección.
                Puedes seguir navegando sin aceptarlas.
                <a href="#" class="ck-enlace" data-abrir-tratamiento="cookies">Ver política de cookies</a></p>
        </div>
        <div class="ck-botones">
            <button type="button" class="ck-btn ck-rechazar" id="ckRechazar">Rechazar</button>
            <button type="button" class="ck-btn ck-aceptar" id="ckAceptar">Aceptar</button>
        </div>
    </div>
</div>

<!-- ========== Ventana: tratamiento de datos / cookies ========== -->
<div class="ck-modal" id="ckModal" role="dialog" aria-modal="true" aria-labelledby="ckModalTitulo" hidden>
    <div class="ck-modal-caja" tabindex="-1">
        <div class="ck-modal-cab">
            <h2 id="ckModalTitulo"><i class="fa-solid fa-shield-halved"></i> <span id="ckModalTit">Tratamiento de datos personales</span></h2>
            <button type="button" class="ck-cerrar" id="ckCerrar" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="ck-tabs" role="tablist">
            <button type="button" role="tab" class="ck-tab activo" data-ck-tab="datos" aria-selected="true">Tratamiento de datos</button>
            <button type="button" role="tab" class="ck-tab" data-ck-tab="cookies" aria-selected="false">Política de cookies</button>
        </div>

        <div class="ck-modal-cuerpo">
            <!-- ===== Tratamiento de datos ===== -->
            <article data-ck-panel="datos">
                <p class="ck-resumen">Esta política explica qué datos personales recolecta RutaNexo, para qué los usa y cuáles son tus derechos,
                    conforme a la Ley 1581 de 2012 (Habeas Data), el Decreto 1377 de 2013 y demás normas colombianas aplicables.</p>

                <h3>1. Responsable</h3>
                <p>RutaNexo, plataforma de movilidad y gestión del transporte público de Ibagué (Tolima).
                    Contacto: <a href="mailto:contacto@rutanexo.com">contacto@rutanexo.com</a>.</p>

                <h3>2. Datos que recolectamos</h3>
                <ul>
                    <li><b>De tu cuenta:</b> documento de identidad, nombre, apellido, correo electrónico, teléfono y contraseña (se guarda cifrada, nunca en texto claro).</li>
                    <li><b>De tu empresa (si compras un plan):</b> NIT, razón social, representante legal, dirección, ciudad y el certificado de cámara de comercio.</li>
                    <li><b>De tus pagos:</b> método elegido, referencia de pago, valor y el comprobante que subas. No guardamos números de tarjeta ni claves bancarias.</li>
                    <li><b>Técnicos:</b> cookies de sesión y registros mínimos de seguridad (por ejemplo, intentos de inicio de sesión).</li>
                </ul>

                <h3>3. Para qué usamos tus datos</h3>
                <ul>
                    <li>Crear y administrar tu cuenta y permitirte iniciar sesión.</li>
                    <li>Validar tu empresa y activar el plan que compraste.</li>
                    <li>Registrar, verificar y conciliar tus pagos.</li>
                    <li>Recuperar tu contraseña (enviamos un código a tu correo).</li>
                    <li>Informarte sobre el estado de tu solicitud y mejorar el servicio.</li>
                    <li>Cumplir obligaciones legales y atender requerimientos de autoridades.</li>
                </ul>

                <h3>4. Tus derechos como titular</h3>
                <ul>
                    <li>Conocer, actualizar y rectificar tus datos personales.</li>
                    <li>Solicitar prueba de la autorización que nos diste.</li>
                    <li>Ser informado sobre el uso que se ha dado a tus datos.</li>
                    <li>Revocar la autorización y/o solicitar la supresión de tus datos, cuando no exista un deber legal o contractual de conservarlos.</li>
                    <li>Presentar quejas ante la Superintendencia de Industria y Comercio (SIC).</li>
                    <li>Acceder de forma gratuita a tus datos personales.</li>
                </ul>

                <h3>5. Seguridad y terceros</h3>
                <p>Tus documentos se guardan de forma privada: solo pueden verlos tú y el Super Administrador que valida las solicitudes.
                    No vendemos tus datos. Solo los compartimos con terceros cuando es necesario para prestar el servicio
                    (por ejemplo, la entidad que procesa un pago en línea) o cuando la ley lo exige.</p>

                <h3>6. Vigencia</h3>
                <p>Conservamos tus datos mientras tu cuenta esté activa y durante el tiempo que exijan las normas aplicables.</p>

                <h3>7. Cómo ejercer tus derechos</h3>
                <p>Escribe a <a href="mailto:contacto@rutanexo.com">contacto@rutanexo.com</a> indicando tu nombre, documento y lo que necesitas.
                    Al marcar la casilla de aceptación autorizas, de manera previa, expresa e informada, el tratamiento de tus datos en los términos de esta política.</p>
            </article>

            <!-- ===== Cookies ===== -->
            <article data-ck-panel="cookies" hidden>
                <p class="ck-resumen">Las cookies son pequeños archivos que el navegador guarda para que el sitio funcione y recuerde información.</p>

                <h3>Cookies que usamos</h3>
                <div class="ck-tabla-caja">
                    <table class="ck-tabla">
                        <thead><tr><th>Cookie</th><th>Para qué sirve</th><th>Duración</th></tr></thead>
                        <tbody>
                        <tr><td><code>RUTANEXO_SID</code></td><td>Mantener tu sesión iniciada y proteger los formularios (necesaria).</td><td>Hasta cerrar el navegador, o 30 días si marcas «Recordar sesión».</td></tr>
                        <tr><td><code>rn_cookies</code></td><td>Recordar que aceptaste este aviso.</td><td>1 año</td></tr>
                        </tbody>
                    </table>
                </div>

                <h3>Lo que NO hacemos</h3>
                <p>No usamos cookies de publicidad ni de seguimiento de terceros.</p>

                <h3>Servicios externos</h3>
                <p>Para mostrar el mapa, las fuentes y los iconos, el sitio carga recursos de servicios externos (OpenStreetMap/CARTO, Google Fonts y Font Awesome),
                    que pueden recibir tu dirección IP como cualquier sitio web.</p>

                <h3>Tu elección</h3>
                <p>Si pulsas <b>Aceptar</b>, guardamos tu elección por un año y dejamos de mostrarte el aviso. Si pulsas <b>Rechazar</b> puedes seguir navegando con normalidad,
                    pero el aviso volverá a aparecer cuando cambies de página. Las cookies necesarias para iniciar sesión se usan siempre, porque sin ellas el sitio no funciona.</p>
            </article>
        </div>

        <div class="ck-modal-pie">
            <button type="button" class="ck-btn ck-aceptar" id="ckEntendido">Entendido</button>
        </div>
    </div>
</div>

<script src="<?= $raiz ?>js/cookies.js?v=<?= $v_ck('js/cookies.js') ?>"></script>
