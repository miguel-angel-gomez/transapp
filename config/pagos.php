<?php
// ============================================================
// Configuración de pagos de planes.
//
// IMPORTANTE: los datos de cuentas son DE EJEMPLO. Cámbialos por los reales
// de la empresa antes de usar el sistema con clientes.
//
// Tipos de método:
//   gratuito       → plan sin costo, no requiere pago
//   pasarela       → pago en línea (PSE). Aquí es una SIMULACIÓN para la demostración;
//                    para producción se conecta una pasarela real (Wompi, ePayco, PayU…)
//   transferencia  → el cliente transfiere y sube el comprobante; el Super Admin lo verifica
//   efectivo       → el cliente paga con una referencia en un punto físico; el Super Admin la confirma
// ============================================================

const PAGO_DIAS_VIGENCIA_REFERENCIA = 5;     // días para pagar en efectivo / transferir
const ARCHIVO_MAX_BYTES             = 5242880; // 5 MB por archivo

const PAGO_ESTADO_ESPERA    = 10;   // estado_registro: En espera
const PAGO_ESTADO_TRAMITE   = 11;   // En trámite (comprobante enviado, por verificar)
const PAGO_ESTADO_REALIZADO = 12;   // Realizada
const ESTADO_ANULADO        = 9;
const ESTADO_DESCARTADA     = 13;
const ESTADO_VIGENTE        = 4;
const ROL_ADMINISTRADOR     = 1;

function metodos_pago(): array
{
    return [
        'pse' => [
            'nombre' => 'PSE — débito desde tu banco', 'icono' => 'fa-solid fa-building-columns', 'tipo' => 'pasarela',
            'texto'  => 'Pagas en línea desde tu cuenta bancaria. El plan se activa cuando el Super Administrador valida tus documentos.',
        ],
        'nequi' => [
            'nombre' => 'Nequi', 'icono' => 'fa-solid fa-mobile-screen-button', 'tipo' => 'transferencia',
            'texto'  => 'Envía el valor del plan al número de Nequi de RutaNexo y sube el comprobante.',
            'datos'  => ['Número Nequi' => '300 000 0000 (ejemplo)', 'Titular' => 'RutaNexo (ejemplo)'],
        ],
        'bancolombia' => [
            'nombre' => 'Bancolombia — transferencia', 'icono' => 'fa-solid fa-money-bill-transfer', 'tipo' => 'transferencia',
            'texto'  => 'Transfiere a la cuenta de RutaNexo y sube el comprobante.',
            'datos'  => ['Cuenta de ahorros' => '000-000000-00 (ejemplo)', 'Titular' => 'RutaNexo (ejemplo)', 'NIT' => '900.000.000-0 (ejemplo)'],
        ],
        'efectivo' => [
            'nombre' => 'Efectivo en punto físico', 'icono' => 'fa-solid fa-store', 'tipo' => 'efectivo',
            'texto'  => 'Generamos una referencia de pago. Pagas en efectivo en el punto y el Super Administrador confirma el pago con esa referencia.',
            'datos'  => ['Punto de pago' => 'Oficina RutaNexo — Ibagué (dirección de ejemplo)', 'Horario' => 'Lunes a viernes, 8:00 a. m. – 5:00 p. m.'],
        ],
    ];
}

/** Estados de pago/suscripción en texto + clase de color. */
function texto_estado(int $id): array
{
    return [
        10 => ['En espera de pago', 'esp'],
        11 => ['En verificación', 'tra'],
        12 => ['Pagado', 'ok'],
        4  => ['Vigente', 'ok'],
        1  => ['Habilitada', 'ok'],
        9  => ['Anulado', 'mal'],
        13 => ['Descartada', 'mal'],
    ][$id] ?? ['Estado ' . $id, 'esp'];
}
