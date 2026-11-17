<?php
// ============================================================
// Enlaces externos del proyecto.
//
// La landing (página de presentación) NO está dentro de este proyecto: es una página
// estática aparte (carpeta Landing_RutaNexo del portafolio). Aquí se dice dónde está.
//
// Si se deja vacía, esos enlaces llevan al index.php del proyecto. Cuando se sepa la URL real de la landing, ponla aquí. Ejemplos:
//   'https://dominio-del-portafolio/Landing_RutaNexo/index.html'
//   '../Landing_RutaNexo/index.html'   (si la carpeta está junto a este proyecto, p. ej. en htdocs)
// ============================================================
const URL_LANDING = '';   // vacío = los enlaces a "Planes" llevan al index del proyecto

/** URL de la landing, con ancla opcional (ej.: url_landing('planes') → ...index.html#planes) */
function url_landing(string $ancla = ''): string
{
    return (URL_LANDING !== '' ? URL_LANDING : 'index.php') . ($ancla !== '' ? '#' . $ancla : '');
}

// ------------------------------------------------------------
// Acceso del Super Administrador (login aparte, con código de 6 dígitos por correo).
// Se abre en:  <proyecto>/index.php/login-superadmin   (o  <proyecto>/login-superadmin  con .htaccess)
// Para cambiar el nombre de la URL, cambia solo esta línea (y la regla de .htaccess).
// ------------------------------------------------------------
const RUTA_SUPERADMIN = 'login-superadmin';

/** Enlace al login del Super Administrador. $raiz = '' o '../' según la carpeta de la página. */
function url_superadmin(string $raiz = ''): string
{
    return $raiz . 'index.php/' . RUTA_SUPERADMIN;
}
