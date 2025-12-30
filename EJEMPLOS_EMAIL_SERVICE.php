<?php
/**
 * EJEMPLOS DE USO - EmailService
 * 
 * El servicio detecta automáticamente la empresa y aplica:
 * - Logo correspondiente
 * - Colores de la empresa
 * - Información de contacto
 * - Email de origen
 */

require_once __DIR__ . '/libs/EmailService.php';

// ============================================
// EJEMPLO 1: Enviar factura (auto-detecta empresa)
// ============================================
$emailService = new EmailService(); // Detecta empresa del JWT/sesión

$resultado = $emailService->enviar(
    'cliente@email.com',
    'Tu Factura #001-001-000005656',
    'factura',  // Plantilla factura.html
    [
        'numero_factura' => '001-001-000005656',
        'fecha' => date('d/m/Y'),
        'cliente' => 'Juan Pérez García',
        'descripcion_producto' => 'Laptop HP EliteBook',
        'cantidad' => '1',
        'precio_unitario' => '$1,200.00',
        'subtotal' => '$1,200.00',
        'iva' => '$180.00',
        'total' => '$1,380.00',
        'enlace_factura' => 'https://tudominio.com/facturas/001-001-000005656'
    ]
);

if ($resultado) {
    echo "✅ Factura enviada a cliente@email.com";
} else {
    echo "❌ Error al enviar factura";
}

// ============================================
// EJEMPLO 2: Enviar orden de compra (empresa específica)
// ============================================
$emailService = new EmailService('CARTIMEX'); // Usa configuración de CARTIMEX

$resultado = $emailService->enviar(
    'proveedor@email.com',
    'Orden de Compra #ORD-2025-0001',
    'orden-compra',
    [
        'cliente' => 'Computronsa',
        'numero_orden' => 'ORD-2025-0001',
        'fecha' => date('d/m/Y'),
        'proveedor' => 'Proveedor XYZ',
        'responsable' => 'Juan García',
        'descripcion' => '100 unidades de Producto A',
        'cantidad' => '100',
        'precio_unitario' => '$10.00',
        'total_item' => '$1,000.00',
        'total_orden' => '$1,000.00',
        'enlace_orden' => 'https://tudominio.com/ordenes/ORD-2025-0001'
    ]
);

// ============================================
// EJEMPLO 3: Enviar confirmación de registro
// ============================================
$emailService = new EmailService('COMPUTRONSA');

$resultado = $emailService->enviar(
    'nuevo.usuario@email.com',
    'Bienvenido a Computronsa',
    'confirmacion',
    [
        'nombre' => 'Carlos López',
        'usuario' => 'carlos.lopez',
        'email' => 'nuevo.usuario@email.com',
        'rol' => 'Supervisor',
        'enlace_inicio' => 'https://tudominio.com/login'
    ]
);

// ============================================
// EJEMPLO 4: Email personalizado (HTML custom)
// ============================================
$htmlPersonalizado = '<h3>Notificación Especial</h3>';
$htmlPersonalizado .= '<p>Estimado {{cliente}},</p>';
$htmlPersonalizado .= '<p>Su pedido #{{numero_pedido}} ha sido despachado.</p>';
$htmlPersonalizado .= '<a href="{{enlace_rastreo}}" class="button">Rastrear Pedido</a>';

$emailService = new EmailService('DISTRIBUCION');

$resultado = $emailService->enviar(
    'cliente@email.com',
    'Tu pedido ha sido despachado',
    null, // Sin plantilla
    [
        'cliente' => 'María González',
        'numero_pedido' => 'PED-2025-5001',
        'enlace_rastreo' => 'https://tudominio.com/rastreo/PED-2025-5001'
    ],
    $htmlPersonalizado // HTML personalizado
);

// ============================================
// EJEMPLO 5: Agregar nueva empresa dinámicamente
// ============================================
$emailService = new EmailService();

$emailService->agregarEmpresa('NUEVAEMPRESA', [
    'nombre' => 'Nueva Empresa S.A.',
    'logo' => 'https://tudominio.com/logos/nueva-empresa-logo.png',
    'color_primario' => '#FF6600',
    'color_secundario' => '#FF9933',
    'email_from' => 'noreply@nuevaempresa.com',
    'telefono' => '+593-2-111-2222',
    'website' => 'www.nuevaempresa.com'
]);

// Ahora puedes usar la nueva empresa
$emailService = new EmailService('NUEVAEMPRESA');
$emailService->enviar(
    'alguien@email.com',
    'Email desde Nueva Empresa',
    'generico',
    [
        'titulo' => 'Bienvenida',
        'contenido' => 'Hola, te damos la bienvenida a Nueva Empresa',
        'enlaces' => '<a href="https://tudominio.com" class="button">Visitar Sitio</a>'
    ]
);

// ============================================
// EJEMPLO 6: En un Controller
// ============================================
/*
class FacturasController extends Controller {
    
    public function guardarFactura() {
        $data = $this->getJsonInput();
        
        // Guardar factura en BD...
        $factura = $this->model->guardarFactura($data);
        
        if ($factura['success']) {
            // Enviar email automáticamente
            $emailService = new EmailService(); // Usa empresa actual
            
            $emailService->enviar(
                $data['email_cliente'],
                'Factura Generada: ' . $factura['numero_factura'],
                'factura',
                [
                    'numero_factura' => $factura['numero_factura'],
                    'fecha' => $factura['fecha'],
                    'cliente' => $factura['cliente_nombre'],
                    // ... más datos
                ]
            );
            
            $this->jsonResponse(['success' => true, 'message' => 'Factura guardada y email enviado'], 200);
        }
    }
}
*/

// ============================================
// EJEMPLO 7: En un Model
// ============================================
/*
class FacturasModel extends Model {
    
    public function guardarFactura($datos) {
        // Código de guardado...
        
        // Después de guardar, enviar email
        $emailService = new EmailService($this->empresaCode);
        
        if ($emailService->enviar(...)) {
            // Email enviado exitosamente
            return ['success' => true, 'mensaje' => 'Factura guardada'];
        }
    }
}
*/
