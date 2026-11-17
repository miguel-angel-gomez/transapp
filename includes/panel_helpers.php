<?php
// ============================================================
// Ayudas compartidas por los paneles (Super Administrador y Administrador):
// mensajes flash, redirección, CSRF, texto de formularios y etiquetas de estado.
// ============================================================

const ESTADO_DESHABILITADO = 2;   // estado_registro.id_estados = 2

/** Guarda un mensaje para mostrarlo en la siguiente carga (patrón POST → redirección → GET). */
function flash(string $tipo, string $texto): void
{
    $_SESSION['flash_panel'] = ['tipo' => $tipo, 'texto' => $texto];
}

function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** true si la petición es POST con token CSRF válido. */
function post_valido(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf'] ?? null);
}

/** Campo oculto con el token CSRF para los formularios. */
function campo_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Texto limpio de un campo POST. */
function post_texto(string $campo, int $max = 255): string
{
    return mb_substr(trim((string) ($_POST[$campo] ?? '')), 0, $max);
}

/** Etiqueta de estado (Habilitado, Deshabilitado, En espera…) con color. */
function etiqueta_estado(int $id, string $nombre): string
{
    $clase = [1 => 'ok', 4 => 'ok', 12 => 'ok', 2 => 'mal', 9 => 'mal', 13 => 'mal', 10 => 'esp', 11 => 'esp'][$id] ?? 'neutro';
    return '<span class="pn-tag ' . $clase . '">' . e($nombre) . '</span>';
}
