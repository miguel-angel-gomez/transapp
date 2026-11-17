# RutaNexo — Parte 1: Landing + Autenticación + Rutas + Conócenos

PHP nativo + MySQL (PDO). Sin framework. Estructura basada en el proyecto `cfv`.

## Cómo instalarlo (XAMPP)

1. Copia la carpeta `transapp` dentro de `C:\xampp\htdocs\`.
2. Enciende **Apache** y **MySQL** en XAMPP.
3. En phpMyAdmin crea la base `rutanexo` (cotejamiento utf8mb4_unicode_ci) → entra a ella → **Importar** → elige `rutanexo.sql` (todas las tablas, los 4 planes y las 35 rutas de Ibagué con sus paraderos; es el único SQL del proyecto).
4. Abre `http://localhost/transapp/` (en el servidor: https://transapp.myjob.solutions/).

## Compra de planes

Landing → **Comprar** → `login.php?plan=ID` → (usuario normal) `comprar.php` → `mi_compra.php`.
- `comprar.php`: datos del usuario + datos de la empresa (NIT, razón social, representante…) + **cámara de comercio** (PDF/JPG/PNG ≤ 5 MB, guardada en `storage/documentos/`, privada) + forma de pago. Crea `empresa` (En espera) + `suscripcion` (con `id_plan`) + `pago`.
- Formas de pago (`config/pagos.php`): **PSE** (simulado para la demo; para producción se conecta Wompi/ePayco/PayU), **Nequi** y **Bancolombia** (transferencia + comprobante) y **efectivo** (referencia `RN-…` para pagar en un punto físico). Los datos de cuentas son de ejemplo: cámbialos.
- **Acceso del Super Administrador**: página aparte en **`login-superadmin`** (también `/login-superadmin` y `superadmin.php`; el nombre se cambia en `config/enlaces.php`), con correo + contraseña y luego un **código de 6 dígitos enviado al correo** (vence en 10 min, 5 intentos). El Super Administrador NO puede entrar por `login.php`. Hay un enlace discreto "Super Admin" en el pie de página. Para que el código llegue hay que configurar `config/mail.php` en modo `smtp`.
- **Panel del Super Administrador** (`admin/index.php`, se abre al iniciar sesión en `login-superadmin`):
  - `admin/rutas.php`: crear, editar y borrar **rutas** y sus **paraderos** (orden, latitud y longitud). Borrar una ruta borra sus paraderos; si tiene viajes registrados, la BD lo impide y se avisa.
  - `admin/empresas.php`: ver empresas (datos, ciudad, suscripciones) y **habilitar / deshabilitar**.
  - `admin/planes.php`: ver, crear, editar, borrar y habilitar / deshabilitar planes (precio, duración, límite de vehículos y funciones incluidas). Un plan con suscripciones no se puede borrar: se deshabilita.
  - `admin/usuarios.php`: buscar/filtrar usuarios por rol y estado y **habilitar / deshabilitar** (un usuario deshabilitado no puede iniciar sesión).
  - Todas las acciones usan POST con token CSRF y consultas preparadas; los estilos están en `css/style_panel.css`.
- **Panel del Administrador de empresa** (`administrador/index.php`; entra por `login.php` con rol Administrador y empresa Habilitada). Todo se limita a **su empresa**:
  - `administrador/usuarios.php`: crear, editar, habilitar/deshabilitar y eliminar usuarios (con búsqueda y filtros por rol y estado). Al crear se pone una contraseña inicial.
  - `administrador/roles.php`: asignar roles (Administrador, Conductor, Técnico, Propietario, Usuario). Al elegir Conductor, Técnico o Propietario se crea su registro en `conductor`, `tecnico` o `titular_vehiculo`.
  - Reglas: nadie cambia su propio rol, ni se deshabilita o elimina a sí mismo (así la empresa nunca se queda sin administrador); un usuario con viajes, tiquetes o vehículos no se puede eliminar, se deshabilita.
- `admin/solicitudes.php` (Super Administrador): revisa cámara y pago, **aprueba** (pago Realizada, suscripción Vigente con fechas, empresa Habilitada, comprador → Administrador) o **rechaza** con motivo (el cliente puede reintentar).
- `archivo.php` entrega los documentos solo al dueño o al Super Administrador.
- La tabla `pago` ya viene en `rutanexo.sql`.

## Rutas y mapa

- Tabla `ruta`: `codigo` (90, 82, 31…), `color`, `origen`, `destino`, `descripcion` ("Pasa por…"). Tabla `paradero`: orden y `lat`/`lng`.
- En el inicio, la **flecha** junto a la lupa despliega todas las rutas; al elegir una se abre el mapa (Leaflet + OpenStreetMap/CARTO, en `libs/leaflet/`) con la línea animada de origen a destino y los paraderos.
- El mapa necesita internet (mosaicos del mapa y OSRM para ajustar el trazo a las calles; sin OSRM dibuja líneas rectas).
- **Ojo:** las rutas cambian (hubo ajustes el 19-oct-2026) y las coordenadas de los paraderos son **aproximadas**. Se corrigen editando `paradero.lat` y `paradero.lng`.
- Fuente de las rutas: guía de busetas de Ibagué (Qhubo, dic-2025) y notas de prensa locales.

Si tu MySQL no usa el puerto 3306 o tiene clave para `root`, cámbialo en `config/conexion.php`.

## Estructura

```
rutanexo/
├── index.php            Página principal: hero, buscador de rutas, ¿Qué es RutaNexo? (con navbar)
├── login.php            Login, registro y recuperar contraseña (tarjeta que gira)
├── rutas.php            Lista de rutas con paraderos y estado (filtros en vivo)
├── conocenos.php        Página "Conócenos"
├── dash.php             Panel temporal (solo entra con sesión)
├── config/
│   ├── conexion.php     Clase Database (PDO)
│   └── mail.php         Configuración del correo (modo log o smtp)
├── includes/
│   ├── funciones.php    Sesión, CSRF, JSON, límites de intentos, consultas de cuentas
│   ├── mailer.php       Envío del PIN por correo (PHPMailer)
│   ├── navbar.php / footer.php
│   └── logout.php       Cierra sesión (POST + CSRF)
├── css/                 base.css · style_index.css · style_login.css · style_paginas.css
├── js/                  landing.js (menú) · cookies.js · carrusel.js · buscar_rutas_index.js · mapa_ruta.js · rutas.js · login.js
├── img/                 logo*.png · hero.jpg · login-fondo.jpg · slide-1..3.jpg
│   └── conocenos/       quienes.jpg (opcional, ver LEEME.txt)
├── libs/PHPMailer/      Librería de correo (ya incluida, no hace falta Composer)
├── libs/leaflet/        Librería del mapa (ya incluida)
├── storage/             mail.log (correos en modo desarrollo) — protegido con .htaccess
└── rutanexo.sql         Base de datos completa (tablas, planes y rutas de Ibagué)
```

## Páginas

- La landing NO va en este proyecto (es una página estática aparte, `Landing_RutaNexo`). Su URL se configura en `config/enlaces.php` (`URL_LANDING`).
  El botón **Conocer el proyecto** y **Ir al sistema** llevan a `index.php`.
- `index.php` — hero, apartados, **buscador** (consulta `ruta`, `paradero` y `empresa`) y ¿Qué es RutaNexo? con carrusel.
  Menú: Inicio · Rutas · Conócenos · Iniciar sesión.
- `rutas.php` y `conocenos.php` — páginas internas.
- Los planes de suscripción ya no se muestran (la tabla `plan_suscripcion` sigue en la BD para más adelante).

## Autenticación

Todo ocurre en `login.php` (se monta sobre `img/login-fondo.jpg`, en el recuadro blanco de la imagen): los formularios se envían por AJAX y la tarjeta gira entre
**Iniciar sesión · Crear cuenta · Recuperar (correo → PIN → nueva contraseña)**.
Enlaces directos: `login.php?modo=registro` y `login.php?modo=recuperar`.

- El registro crea cuentas con rol **Usuario (5)**. Los demás roles se crearán desde el panel de administración.
- `login.php` es solo para cuentas de `usuario`; el Super Administrador entra por `login-superadmin`.
- La contraseña debe tener mínimo 8 caracteres con letras y números.
- Seguridad incluida: contraseñas con `password_hash`, token CSRF, `session_regenerate_id`, consultas preparadas,
  máximo 5 intentos de login por 10 min, PIN de 8 dígitos que vence en 15 min y se invalida a los 5 errores,
  y mensajes que no revelan si un correo existe.
- Los botones de Google / Microsoft / Apple están desactivados ("Próximamente").

### Correo del PIN

Por defecto (`config/mail.php` → `'modo' => 'log'`) **no se envía correo real**: el PIN queda escrito en
`storage/mail.log`. Es lo que necesitas para probar en tu computador.

Para enviar correos reales con Gmail: activa la verificación en 2 pasos, crea una *Contraseña de aplicación*
en tu cuenta de Google y llena `config/mail.php` con `'modo' => 'smtp'`, tu correo en `usuario` y `remitente`,
y esa contraseña de 16 letras en `clave`.

## Imágenes

- `img/logo.png` (para fondos oscuros), `img/logo-oscuro.png` (para fondos claros) y `img/logo-pin.png` (solo el pin / favicon).
- `img/login-fondo.jpg` → fondo del login.
- `img/hero.jpg` → banner de index.php. `img/slide-1.jpg`…`slide-3.jpg` → carrusel (hasta `slide-8.jpg`).

## Pendiente / a tener en cuenta

- Las contraseñas de los usuarios de prueba que vienen en el `.sql` están con hash y no se conocen:
  crea una cuenta nueva desde el registro o usa "Recuperar contraseña" (con modo `log`).
- `dash.php` es solo una página de prueba; los paneles por rol van en las siguientes partes.

## Cookies y tratamiento de datos
- `includes/cookies.php` (se incluye desde `footer.php`, `login.php` y `dash.php`) + `js/cookies.js` + `css/style_cookies.css`.
- **Cookies:** el aviso sale en cada página hasta que el usuario **acepte** (cookie `rn_cookies`, 1 año). Si **rechaza**, sigue navegando normal pero el aviso vuelve a salir al cambiar de vista. Iniciar sesión nunca depende de aceptar cookies.
- **Tratamiento de datos (Ley 1581 de 2012):** es **obligatorio** aceptarlo para **registrarse** y para **comprar un plan**. El botón verde "Conocer el tratamiento de datos" abre la ventana con el texto (también está en el pie de página, junto a "Cookies"). Cualquier enlace con `data-abrir-tratamiento` abre esa ventana.
- El texto es un modelo general para el proyecto académico; para uso comercial debe revisarlo un abogado.
