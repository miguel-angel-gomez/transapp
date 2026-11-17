<?php
// ============================================================
// Envío del correo con el PIN de recuperación
// Configuración en config/mail.php
// ============================================================

/** Cuerpo HTML del correo (mismo estilo de marca de la plataforma). */
function html_correo_pin(string $nombre, string $pin): string
{
    $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<div style="font-family:Arial,Helvetica,sans-serif;padding:24px;background:#eef3f6;">
  <div style="max-width:480px;margin:auto;background:#ffffff;padding:32px;border-radius:14px;">
    <h2 style="color:#0b2536;text-align:center;margin:0 0 4px;">Ruta<span style="color:#13c4a3;">Nexo</span></h2>
    <p style="text-align:center;color:#6b7f8c;margin:0 0 24px;font-size:13px;">Recuperación de contraseña</p>
    <p style="color:#0b2536;">Hola {$nombre},</p>
    <p style="color:#0b2536;">Recibimos una solicitud para restablecer tu contraseña. Tu código de verificación es:</p>
    <div style="background:#13c4a3;color:#ffffff;font-size:30px;font-weight:bold;text-align:center;padding:16px;border-radius:10px;letter-spacing:8px;">{$pin}</div>
    <p style="color:#0b2536;margin-top:22px;">Este código vence en <strong>15 minutos</strong>.</p>
    <p style="color:#8a9aa5;font-size:12px;">Si no fuiste tú, ignora este correo: tu contraseña no cambiará.</p>
  </div>
</div>
HTML;
}

/**
 * Envía un correo HTML (SMTP con PHPMailer) o, en modo 'log', lo escribe en storage/mail.log.
 * Devuelve true si se envió (o se registró).
 */
function enviar_correo(string $correo, string $nombre, string $asunto, string $html, string $alt, string $linea_log): bool
{
    $config = require __DIR__ . '/../config/mail.php';

    // ---- Modo desarrollo: se guarda en storage/mail.log ----
    if (($config['modo'] ?? 'log') !== 'smtp') {
        $linea = sprintf("[%s] Para: %s | %s | (modo log, no se envió correo real)\n", date('Y-m-d H:i:s'), $correo, $linea_log);
        return file_put_contents(__DIR__ . '/../storage/mail.log', $linea, FILE_APPEND | LOCK_EX) !== false;
    }

    // ---- Modo real: SMTP con PHPMailer ----
    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['usuario'];
        $mail->Password   = $config['clave'];
        $mail->SMTPSecure = $config['cifrado'] === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $config['puerto'];
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;

        $mail->setFrom($config['remitente'], $config['nombre_remitente']);
        $mail->addAddress($correo, $nombre);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $html;
        $mail->AltBody = $alt;
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('Error enviando correo: ' . $e->getMessage());
        return false;
    }
}

/** ¿El correo está en modo 'log' (no sale a internet)? */
function correo_en_modo_log(): bool
{
    $config = require __DIR__ . '/../config/mail.php';
    return ($config['modo'] ?? 'log') !== 'smtp';
}

/** Envía el PIN de recuperación (8 dígitos). */
function enviar_pin_correo(string $correo, string $nombre, string $pin): bool
{
    return enviar_correo(
        $correo, $nombre,
        'Tu código de recuperación - RutaNexo',
        html_correo_pin($nombre, $pin),
        "Tu código de recuperación de RutaNexo es: $pin (vence en 15 minutos).",
        "PIN: $pin"
    );
}

/** Cuerpo HTML del correo con el código de acceso del Super Administrador. */
function html_correo_codigo_acceso(string $nombre, string $codigo): string
{
    $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<div style="font-family:Arial,Helvetica,sans-serif;padding:24px;background:#eef3f6;">
  <div style="max-width:480px;margin:auto;background:#ffffff;padding:32px;border-radius:14px;">
    <h2 style="color:#0b2536;text-align:center;margin:0 0 4px;">Ruta<span style="color:#13c4a3;">Nexo</span></h2>
    <p style="text-align:center;color:#6b7f8c;margin:0 0 24px;font-size:13px;">Acceso del Super Administrador</p>
    <p style="color:#0b2536;">Hola {$nombre},</p>
    <p style="color:#0b2536;">Alguien inició sesión con tu cuenta de Super Administrador. Tu código de verificación es:</p>
    <div style="background:#0b2536;color:#ffffff;font-size:32px;font-weight:bold;text-align:center;padding:16px;border-radius:10px;letter-spacing:10px;">{$codigo}</div>
    <p style="color:#0b2536;margin-top:22px;">Este código vence en <strong>10 minutos</strong> y solo sirve una vez.</p>
    <p style="color:#8a9aa5;font-size:12px;">Si no fuiste tú, no compartas este código y cambia tu contraseña.</p>
  </div>
</div>
HTML;
}

/** Envía el código de verificación de 6 dígitos del Super Administrador. */
function enviar_codigo_acceso(string $correo, string $nombre, string $codigo): bool
{
    return enviar_correo(
        $correo, $nombre,
        'Tu código de acceso (Super Administrador) - RutaNexo',
        html_correo_codigo_acceso($nombre, $codigo),
        "Tu código de acceso de RutaNexo es: $codigo (vence en 10 minutos).",
        "CODIGO SUPERADMIN: $codigo"
    );
}
