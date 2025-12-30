# 📧 Soporte de Múltiples Destinatarios en EmailService

## ✨ Nueva Funcionalidad

EmailService ahora **soporta enviar emails a múltiples destinatarios**, con opciones para CC y BCC.

## 🚀 Uso Básico

### Un Solo Destinatario (Original)

```php
$emailService = new EmailService('COMPUTRONSA');

$emailService->enviar(
    'cliente@ejemplo.com',
    'Tu Factura',
    'factura',
    ['numero' => '001-001-000000001']
);
```

### Múltiples Destinatarios - Array Simple

```php
$emailService->enviar(
    [
        'cliente@ejemplo.com',
        'gerente@ejemplo.com',
        'contacto@ejemplo.com'
    ],
    'Notificación',
    'generico',
    ['mensaje' => 'Contenido']
);
```

### Múltiples Destinatarios - Con Nombres

```php
$emailService->enviar(
    [
        'cliente@ejemplo.com' => 'John Doe',
        'gerente@ejemplo.com' => 'Manager',
        'contacto@ejemplo.com' => 'Contact Person'
    ],
    'Factura Enviada',
    'factura',
    ['numero' => '001-001-000000001']
);
```

## 📨 Parámetros Completos

```php
$emailService->enviar(
    $email,              // string|array - Destinatario(s)
    $asunto,             // string - Asunto
    $plantilla,          // string - Plantilla (default: 'generico')
    $datos,              // array - Variables (default: [])
    $htmlPersonalizado,  // string|null - HTML custom (default: null)
    $cc,                 // array - CC (NEW) (default: [])
    $bcc                 // array - BCC (NEW) (default: [])
);
```

## 🎯 Casos de Uso

### Caso 1: Factura a Cliente + Contabilidad + Supervisor

```php
$emailService->enviar(
    // To: Cliente
    ['cliente@empresa.com' => 'Cliente XYZ'],
    'Tu Factura #001-001-000000001',
    'factura',
    ['numero' => '001-001-000000001', 'total' => '$1000.00'],
    null,
    // CC: Supervisor (visible)
    ['supervisor@empresa.com' => 'Supervisor Financiero'],
    // BCC: Auditoría (oculto)
    ['auditoria@empresa.com' => 'Auditoría']
);
```

### Caso 2: Notificación a Equipo Completo

```php
$gerentes = [
    'gerente1@empresa.com' => 'Gerente 1',
    'gerente2@empresa.com' => 'Gerente 2'
];

$supervisores = [
    'supervisor1@empresa.com',
    'supervisor2@empresa.com'
];

$emailService->enviar(
    // Todos los gerentes reciben
    $gerentes,
    'Alerta de Sistema',
    'generico',
    ['alerta' => 'Actividad sospechosa detectada'],
    null,
    // Los supervisores reciben copia
    $supervisores
);
```

### Caso 3: Newsletter a Clientes desde DB

```php
// Obtener clientes de la base de datos
$sql = "SELECT EMAIL, NOMBRE FROM CLIENTES WHERE ESTADO = 'ACTIVO'";
$clientes = $this->db->query($sql);

// Convertir a array asociativo
$destinatarios = [];
foreach ($clientes as $cliente) {
    $destinatarios[$cliente['EMAIL']] = $cliente['NOMBRE'];
}

// Enviar a todos
$emailService->enviar(
    $destinatarios,
    'Promoción Especial - 30% OFF',
    'generico',
    ['oferta' => 'Válido hasta el 31 de diciembre']
);
```

## 📝 Formatos de Email Soportados

### Formato 1: String Simple
```php
'cliente@ejemplo.com'
```

### Formato 2: Array de Strings
```php
[
    'cliente1@ejemplo.com',
    'cliente2@ejemplo.com',
    'cliente3@ejemplo.com'
]
```

### Formato 3: Array Asociativo (Email => Nombre)
```php
[
    'cliente@ejemplo.com' => 'Nombre del Cliente',
    'gerente@ejemplo.com' => 'Nombre del Gerente',
    'supervisor@ejemplo.com' => 'Nombre del Supervisor'
]
```

### Formato 4: Mezcla de Formatos
```php
// Puedes combinar:
$simple = ['email1@ejemplo.com', 'email2@ejemplo.com'];
$conNombre = ['email3@ejemplo.com' => 'Nombre 3'];

$todos = array_merge($simple, $conNombre);

$emailService->enviar($todos, 'Asunto', 'plantilla');
```

## 🔄 CC vs BCC vs To

| Tipo | Visible para Todos | Caso de Uso |
|------|-------------------|-----------|
| **To** (Destinatario) | ✅ Sí | Receptor principal |
| **CC** | ✅ Sí | Copias que deben saber que otros ven |
| **BCC** | ❌ No | Auditoría, archivo, sin que se enteren |

**Ejemplo Práctico:**

```php
$emailService->enviar(
    // To: El cliente es el principal
    ['cliente@ejemplo.com' => 'Cliente'],
    'Tu Factura',
    'factura',
    ['numero' => '001-001-000000001'],
    null,
    // CC: El supervisor ve que el cliente recibió
    ['supervisor@empresa.com' => 'Supervisor'],
    // BCC: Auditoría ve pero cliente no sabe
    ['auditoria@empresa.com' => 'Auditoría']
);

// Resultado visible para cliente:
// - To: cliente@ejemplo.com
// - CC: supervisor@empresa.com
// 
// Resultado visible para supervisor:
// - To: cliente@ejemplo.com
// - CC: supervisor@empresa.com
//
// Resultado visible para auditoría (invisible para otros):
// - To: cliente@ejemplo.com
// - CC: supervisor@empresa.com
// - BCC: auditoria@empresa.com (solo ve esto el recipiente)
```

## ✅ Validación Automática

Cada email se valida automáticamente:

```php
// Algunos inválidos, algunos válidos - NO FALLA
$emailService->enviar(
    [
        'valido@ejemplo.com',      // ✓ OK
        '',                         // ✗ Ignorado (vacío)
        'invalido_sin_arroba',      // ✗ Ignorado (sin @)
        'otro@ejemplo.com'          // ✓ OK
    ],
    'Asunto',
    'generico',
    []
);
// Se envía a: valido@ejemplo.com, otro@ejemplo.com
```

## 🛡️ Buenas Prácticas

### 1. Validar antes de enviar

```php
$emails = obtenerEmailsDeLaDB();

// Filtrar emails válidos
$emailsValidos = array_filter($emails, function($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
});

if (empty($emailsValidos)) {
    return false; // No hay emails válidos
}

$emailService->enviar($emailsValidos, ...);
```

### 2. Usar nombres descriptivos en CC/BCC

```php
// ✓ Bueno: describe el propósito
$emailService->enviar(
    $cliente,
    'Factura',
    'factura',
    $datos,
    null,
    null,
    ['auditoria@empresa.com' => 'Auditoría de Facturas']
);

// ✗ Evitar: sin descripción
$emailService->enviar(
    $cliente,
    'Factura',
    'factura',
    $datos,
    null,
    null,
    ['auditoria@empresa.com']
);
```

### 3. Limitar cantidad de destinatarios

```php
// Si tienes muchos clientes, envía en lotes
$clientes = obtenerClientesDeBaseDatos();
$lote = 50; // Emails por envío

for ($i = 0; $i < count($clientes); $i += $lote) {
    $grupo = array_slice($clientes, $i, $lote);
    
    $emailService->enviar(
        $grupo,
        'Newsletter',
        'generico',
        ['contenido' => '...']
    );
    
    sleep(1); // Esperar 1 segundo entre envíos
}
```

### 4. Manejo de errores

```php
try {
    $resultado = $emailService->enviar(
        $emails,
        'Asunto',
        'plantilla',
        $datos
    );
    
    if (!$resultado) {
        error_log("Fallo al enviar email");
        // Registrar en DB para reintentar
    }
} catch (Exception $e) {
    error_log("Excepción: " . $e->getMessage());
}
```

## 📊 Logging

Todos los emails se registran automáticamente:

```
[EmailService] Email enviado exitosamente a: cliente@ejemplo.com, gerente@ejemplo.com - Empresa: COMPUTRONSA - Asunto: Tu Factura
```

Para buscar logs de emails:

```bash
grep "EmailService" php_errors.log
```

## 🔍 Ejemplos del Mundo Real

### Ejemplo 1: Notificación de Nuevo Pedido

```php
class PedidosController {
    public function crearPedido() {
        // ... guardar pedido ...
        
        $emailService = new EmailService('COMPUTRONSA');
        
        // Notificar al cliente y al equipo de ventas
        $emailService->enviar(
            [
                $pedido['CLIENTE_EMAIL'] => $pedido['CLIENTE_NOMBRE'],
                'ventas@empresa.com' => 'Equipo de Ventas'
            ],
            'Nuevo Pedido #' . $pedido['ID'],
            'generico',
            [
                'numero_pedido' => $pedido['ID'],
                'total' => $pedido['TOTAL']
            ],
            null,
            // CC a gerencia
            ['gerencia@empresa.com' => 'Gerencia'],
            // BCC para auditoría
            ['auditoria@empresa.com' => 'Auditoría']
        );
    }
}
```

### Ejemplo 2: Alerta de Bajo Stock

```php
class InventarioController {
    public function alertarBajoStock($producto) {
        $emailService = new EmailService();
        
        // Obtener responsables de inventario
        $responsables = $this->obtenerResponsablesInventario();
        
        $emailService->enviar(
            $responsables,
            'Alerta: Stock bajo de ' . $producto['NOMBRE'],
            'generico',
            [
                'producto' => $producto['NOMBRE'],
                'stock_actual' => $producto['CANTIDAD'],
                'stock_minimo' => $producto['MINIMO']
            ]
        );
    }
}
```

### Ejemplo 3: Reporte Diario a Múltiples Jefes

```php
class ReportesController {
    public function generarReporteDiario() {
        $emailService = new EmailService('CARTIMEX');
        
        // Todos los jefes reciben
        $jefes = [
            'jefe_ventas@empresa.com' => 'Jefe de Ventas',
            'jefe_operaciones@empresa.com' => 'Jefe de Operaciones',
            'jefe_finanzas@empresa.com' => 'Jefe de Finanzas'
        ];
        
        $emailService->enviar(
            $jefes,
            'Reporte Diario - ' . date('Y-m-d'),
            'factura', // Reutilizando plantilla
            [
                'ingresos' => $this->calcularIngresos(),
                'gastos' => $this->calcularGastos()
            ]
        );
    }
}
```

## ⚡ Performance

- **Validación**: Se valida cada email automáticamente
- **Límites SMTP**: Algunos servidores limitan destinatarios por email
- **Recomendación**: Máximo 50-100 destinatarios por email

Para muchos clientes:

```php
$todos = obtenerTodosLosClientes(); // 10,000+

// Enviar en lotes de 50
foreach (array_chunk($todos, 50) as $lote) {
    $emailService->enviar($lote, ...);
}
```

## 🆘 Troubleshooting

| Problema | Causa | Solución |
|----------|-------|----------|
| No se envía a ninguno | Todos los emails inválidos | Validar con `filter_var()` |
| Se envía pero a pocos | Algunos emails inválidos | Ver logs, revisar formato |
| SMTP rechaza | Demasiados destinatarios | Reducir cantidad, enviar en lotes |
| BCC no funciona | Email en BCC inválido | Validar email antes |

## 📚 Referencia Rápida

```php
// Simple
enviar('email@ejemplo.com', 'Asunto', 'plantilla');

// Múltiples
enviar(['e1@.com', 'e2@.com'], 'Asunto', 'plantilla');

// Con nombres
enviar(['e1@.com' => 'Nombre 1', 'e2@.com' => 'Nombre 2'], 'Asunto', 'plantilla');

// Con CC
enviar($emails, 'Asunto', 'plantilla', $datos, null, $cc);

// Con BCC
enviar($emails, 'Asunto', 'plantilla', $datos, null, $cc, $bcc);

// Todo
enviar($emails, 'Asunto', 'plantilla', $datos, $html, $cc, $bcc);
```

---

**✅ Completamente implementado y listo para usar**

Ver `EJEMPLOS_MULTIPLES_DESTINATARIOS.php` para más ejemplos de código.
