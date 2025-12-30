<?php
/**
 * EJEMPLOS DE USO - EmailService con Múltiples Destinatarios
 * 
 * Mostrando todas las formas de enviar emails a uno o múltiples destinatarios
 */

use libs\EmailService;

// ============================================================================
// EJEMPLO 1: UN SOLO DESTINATARIO (Compatible con versión anterior)
// ============================================================================

$emailService = new EmailService('COMPUTRONSA');

// Forma más simple - un solo email
$emailService->enviar(
    'cliente@ejemplo.com',
    'Tu Factura ha sido Creada',
    'factura',
    ['numero_factura' => '001-001-000000001']
);

// ============================================================================
// EJEMPLO 2: MÚLTIPLES DESTINATARIOS - Array Simple
// ============================================================================

$emailService->enviar(
    // Array de emails simples
    [
        'cliente@ejemplo.com',
        'contacto@ejemplo.com',
        'gerente@ejemplo.com'
    ],
    'Notificación de Factura',
    'factura',
    ['numero_factura' => '001-001-000000001']
);

// ============================================================================
// EJEMPLO 3: MÚLTIPLES DESTINATARIOS - Array Asociativo (Con Nombres)
// ============================================================================

$emailService->enviar(
    // Array asociativo: email => nombre
    [
        'cliente@ejemplo.com' => 'John Doe',
        'contacto@ejemplo.com' => 'Jane Smith',
        'gerente@ejemplo.com' => 'Manager Boss'
    ],
    'Notificación de Factura',
    'factura',
    ['numero_factura' => '001-001-000000001']
);

// ============================================================================
// EJEMPLO 4: MÚLTIPLES DESTINATARIOS + CC
// ============================================================================

$emailService->enviar(
    // Destinatarios principales
    ['cliente@ejemplo.com', 'contacto@ejemplo.com'],
    'Notificación Importante',
    'generico',
    ['mensaje' => 'Esta es una notificación importante'],
    // plantilla personalizada (null = usar plantilla)
    null,
    // CC
    ['gerente@ejemplo.com' => 'Gerente', 'admin@ejemplo.com']
);

// ============================================================================
// EJEMPLO 5: MÚLTIPLES DESTINATARIOS + CC + BCC
// ============================================================================

$emailService->enviar(
    // Destinatarios principales
    ['cliente@ejemplo.com' => 'Cliente Principal'],
    'Confirmación de Pago',
    'confirmacion',
    ['monto' => '$1000.00'],
    // Sin HTML personalizado
    null,
    // CC (visible para todos)
    ['supervisor@ejemplo.com' => 'Supervisor'],
    // BCC (oculto)
    ['archivo@ejemplo.com' => 'Archivo de Auditoría']
);

// ============================================================================
// EJEMPLO 6: CONSTRUIR LISTA DE DESTINATARIOS DINÁMICAMENTE
// ============================================================================

// Simulando obtener emails de una base de datos
$clientes = [
    ['email' => 'cliente1@ejemplo.com', 'nombre' => 'Cliente 1'],
    ['email' => 'cliente2@ejemplo.com', 'nombre' => 'Cliente 2'],
    ['email' => 'cliente3@ejemplo.com', 'nombre' => 'Cliente 3']
];

// Convertir a array asociativo
$destinatarios = [];
foreach ($clientes as $cliente) {
    $destinatarios[$cliente['email']] = $cliente['nombre'];
}

$emailService->enviar(
    $destinatarios,
    'Promoción Especial',
    'generico',
    ['oferta' => '30% de descuento']
);

// ============================================================================
// EJEMPLO 7: MIX DE FORMATOS
// ============================================================================

$gerentes = ['gerente1@ejemplo.com', 'gerente2@ejemplo.com'];
$supervisores = [
    'supervisor1@ejemplo.com' => 'Supervisor 1',
    'supervisor2@ejemplo.com' => 'Supervisor 2'
];

$emailService->enviar(
    // Mezclar emails simples de una variable
    array_merge(['cliente@ejemplo.com'], $gerentes),
    'Reporte Semanal',
    'factura',
    ['numero_factura' => '001-001-000000002'],
    null,
    // CC con nombres específicos
    $supervisores
);

// ============================================================================
// EJEMPLO 8: EMAILS DESDE CONSULTA A BASE DE DATOS
// ============================================================================

// Simulando una consulta a base de datos
$sql = "SELECT EMAIL, CLIENTE_NOMBRE FROM CLIENTES WHERE ID_EMPRESA = 1";
// $resultado = $this->db->query($sql);

// Convertir resultados a array asociativo
$destinatarios = [];
// foreach ($resultado as $fila) {
//     $destinatarios[$fila['EMAIL']] = $fila['CLIENTE_NOMBRE'];
// }

// $emailService->enviar(
//     $destinatarios,
//     'Factura ' . $factura['FACTURA_SECUENCIA'],
//     'factura',
//     ['numero_factura' => $factura['FACTURA_SECUENCIA']]
// );

// ============================================================================
// EJEMPLO 9: VALIDACIÓN ANTES DE ENVIAR (BUENA PRÁCTICA)
// ============================================================================

$emails = ['cliente@ejemplo.com', '', 'invalido', 'otro@ejemplo.com'];

// Filtrar emails válidos
$emailsValidos = array_filter($emails, function($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
});

if (!empty($emailsValidos)) {
    $resultado = $emailService->enviar(
        $emailsValidos,
        'Confirmación',
        'generico',
        ['mensaje' => 'Tu solicitud fue recibida']
    );
    
    if ($resultado) {
        echo "Email enviado a " . count($emailsValidos) . " destinatarios";
    }
}

// ============================================================================
// EJEMPLO 10: USAR EN CONTROLADOR - CASO REAL
// ============================================================================

/*
class NuevaFacturaController {
    public function setGuardarFactura() {
        try {
            // ... código para guardar factura ...
            
            $emailService = new EmailService('COMPUTRONSA');
            
            // Obtener emails de clientes relacionados
            $emails = [
                $factura['CLIENTE_EMAIL'] => $factura['CLIENTE_NOMBRE'],
                'contabilidad@empresa.com' => 'Departamento Contabilidad'
            ];
            
            // Enviar a múltiples destinatarios
            $resultado = $emailService->enviar(
                $emails,
                'Factura ' . $factura['FACTURA_SECUENCIA'],
                'factura',
                [
                    'numero_factura' => $factura['FACTURA_SECUENCIA'],
                    'cliente' => $factura['CLIENTE_NOMBRE'],
                    'total' => '$' . number_format($factura['TOTAL'], 2)
                ],
                null,
                // CC a supervisores
                ['supervisor@empresa.com' => 'Supervisor'],
                // BCC para auditoría
                ['auditoria@empresa.com' => 'Auditoría']
            );
            
            if (!$resultado) {
                error_log("Fallo al enviar factura");
            }
            
            // ... resto del código ...
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
        }
    }
}
*/

// ============================================================================
// NOTAS IMPORTANTES
// ============================================================================

/*
1. COMPATIBILIDAD HACIA ATRÁS:
   - Puedes seguir usando un email simple: enviar('email@ejemplo.com', ...)
   - Los nuevos parámetros (CC, BCC) son opcionales

2. FORMATOS SOPORTADOS:
   - String: 'email@ejemplo.com'
   - Array simple: ['email1@ejemplo.com', 'email2@ejemplo.com']
   - Array asociativo: ['email1@ejemplo.com' => 'Nombre 1', 'email2@ejemplo.com' => 'Nombre 2']

3. VALIDACIÓN:
   - Los emails inválidos se ignoran automáticamente
   - Se requiere al menos un destinatario válido

4. LOGGING:
   - Se registran todos los destinatarios en los logs
   - Útil para auditoría y debugging

5. LÍMITES SMTP:
   - Algunos servidores SMTP tienen límite de destinatarios por email
   - Si envías a muchos (>100), considera enviar lotes

6. BCC vs CC:
   - CC: visible para todos los destinatarios
   - BCC: oculto, no ven otros en BCC
   - Úsalo para auditoría sin que lo sepan los clientes

7. CASOS DE USO:
   - Notificaciones a múltiples gerentes
   - Auditoría (BCC a email de archivo)
   - Copias a departamentos (CC)
   - Newsletters a múltiples clientes
   - Alertas a equipo de soporte
*/

?>
