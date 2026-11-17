<?php
// ============================================================
// Configuración del correo (se usa para enviar el PIN de
// recuperación de contraseña)
//
// modo = 'log'  → DESARROLLO: no envía nada, escribe el correo
//                 (con el PIN) en storage/mail.log
// modo = 'smtp' → envía el correo de verdad con PHPMailer
//
// Para Gmail: activa la verificación en 2 pasos y crea una
// "Contraseña de aplicación" (Cuenta Google → Seguridad).
// Esa contraseña de 16 letras va en 'clave'. NO uses tu clave normal.
// ============================================================
return [
    'modo'             => 'smtp',

    'host'             => 'smtp.gmail.com',
    'puerto'           => 587,
    'cifrado'          => 'tls',            // 'tls' (587) o 'ssl' (465)
    'usuario'          => 'lupito67lol@gmail.com',               // tu_correo@gmail.com
    'clave'            => 'ctys nalt zvox tths',               // contraseña de aplicación

    'remitente'        => 'lupito67lol@gmail.com', // en Gmail debe ser igual a 'usuario'
    'nombre_remitente' => 'RutaNexo',
];
