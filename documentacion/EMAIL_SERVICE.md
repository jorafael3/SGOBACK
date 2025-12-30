# 📧 Documentación - EmailService

## Descripción General

`EmailService` es un servicio centralizado para envío de emails en el sistema SGOBACK. Soporta múltiples empresas con logos y colores personalizados, plantillas reutilizables y un sistema flexible de variables.

**Ventajas principales:**
- ✅ Una única función para todo el sistema
- ✅ Detección automática de empresa
- ✅ Logos y colores personalizados por empresa
- ✅ Header/Footer automático
- ✅ Plantillas HTML reutilizables
- ✅ HTML personalizado opcional
- ✅ Fácil de mantener y extender

---

## Instalación

### 1. Copiar archivos

```
libs/
├── EmailService.php          ← Clase principal

email-templates/
├── factura.html              ← Plantilla factura
├── orden-compra.html         ← Plantilla orden compra
├── confirmacion.html         ← Plantilla confirmación
└── generico.html             ← Plantilla genérica
```

### 2. Configurar empresas

Editar `EmailService.php` y agregar/modificar empresas en el array `$empresas`:

```php
'COMPUTRONSA' => [
    'nombre' => 'Computronsa',
    'logo' => 'https://tudominio.com/logos/computronsa-logo.png',
    'color_primario' => '#003366',
    'color_secundario' => '#0066CC',
    'email_from' => 'noreply@computronsa.com',
    'telefono' => '+593-2-123-4567',
    'website' => 'www.computronsa.com'
]
```

---

## Uso Básico

### Ejemplo 1: Enviar factura (auto-detecta empresa)

```php
require_once __DIR__ . '/libs/EmailService.php';

$emailService = new EmailService(); // Detecta empresa del JWT/sesión

$emailService->enviar(
    'cliente@email.com',
    'Tu Factura #001-001-000005656',
    'factura',  // Nombre de plantilla
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
```

### Ejemplo 2: Empresa específica

```php
// Usar configuración de CARTIMEX
$emailService = new EmailService('CARTIMEX');

$emailService->enviar(
    'cliente@email.com',
    'Tu pedido',
    'orden-compra',
    [...]
);
```

### Ejemplo 3: HTML personalizado

```php
$htmlPersonalizado = '
    <h3>Notificación Especial</h3>
    <p>Estimado {{cliente}},</p>
    <p>Su pedido #{{numero_pedido}} ha sido despachado.</p>
    <a href="{{enlace_rastreo}}" class="button">Rastrear Pedido</a>
';

$emailService = new EmailService();

$emailService->enviar(
    'cliente@email.com',
    'Tu pedido ha sido despachado',
    null, // Sin plantilla
    [
        'cliente' => 'María González',
        'numero_pedido' => 'PED-2025-5001',
        'enlace_rastreo' => 'https://tudominio.com/rastreo/PED-2025-5001'
    ],
    $htmlPersonalizado
);
```

---

## Plantillas Disponibles

### 📋 factura.html
Para envío de facturas a clientes.

**Variables requeridas:**
```php
[
    'numero_factura' => 'string',
    'fecha' => 'string (DD/MM/YYYY)',
    'cliente' => 'string',
    'descripcion_producto' => 'string',
    'cantidad' => 'string|integer',
    'precio_unitario' => 'string ($X.XX)',
    'subtotal' => 'string ($X.XX)',
    'iva' => 'string ($X.XX)',
    'total' => 'string ($X.XX)',
    'enlace_factura' => 'string (URL)'
]
```

### 📦 orden-compra.html
Para órdenes de compra a proveedores.

**Variables requeridas:**
```php
[
    'cliente' => 'string',
    'numero_orden' => 'string',
    'fecha' => 'string',
    'proveedor' => 'string',
    'responsable' => 'string',
    'descripcion' => 'string',
    'cantidad' => 'string|integer',
    'precio_unitario' => 'string',
    'total_item' => 'string',
    'total_orden' => 'string',
    'enlace_orden' => 'string (URL)'
]
```

### ✅ confirmacion.html
Para confirmaciones de registro o acciones.

**Variables requeridas:**
```php
[
    'nombre' => 'string',
    'usuario' => 'string',
    'email' => 'string',
    'rol' => 'string',
    'enlace_inicio' => 'string (URL)'
]
```

### 📄 generico.html
Para mensajes genéricos sin estructura específica.

**Variables requeridas:**
```php
[
    'titulo' => 'string',
    'contenido' => 'string (puede contener HTML)',
    'enlaces' => 'string (HTML de botones/enlaces)'
]
```

---

## Variables Globales

Estas variables están disponibles en TODAS las plantillas automáticamente:

| Variable | Descripción |
|----------|-------------|
| `{{LOGO}}` | URL del logo de la empresa |
| `{{EMPRESA}}` | Nombre de la empresa |
| `{{COLOR_PRIMARIO}}` | Color primario de la empresa |
| `{{COLOR_SECUNDARIO}}` | Color secundario de la empresa |
| `{{AÑO}}` | Año actual |

**Ejemplo:**
```html
<p>© {{AÑO}} {{EMPRESA}}. Todos los derechos reservados.</p>
<p style="color: {{COLOR_PRIMARIO}};">Contenido importante</p>
```

---

## Uso en Controllers

```php
<?php
require_once __DIR__ . '/../../libs/EmailService.php';

class FacturasController extends Controller
{
    public function guardarFactura()
    {
        $data = $this->getJsonInput();
        
        // Guardar factura en BD
        $factura = $this->model->guardarFactura($data);
        
        if ($factura['success']) {
            // Enviar email automáticamente
            $emailService = new EmailService(); // Usa empresa actual
            
            $resultado = $emailService->enviar(
                $data['email_cliente'],
                'Factura Generada: ' . $factura['numero_factura'],
                'factura',
                [
                    'numero_factura' => $factura['numero_factura'],
                    'fecha' => date('d/m/Y', strtotime($factura['fecha'])),
                    'cliente' => $factura['cliente_nombre'],
                    'descripcion_producto' => 'Compra de productos',
                    'cantidad' => $factura['cantidad_items'],
                    'precio_unitario' => '$' . number_format($factura['precio_unitario'], 2),
                    'subtotal' => '$' . number_format($factura['subtotal'], 2),
                    'iva' => '$' . number_format($factura['iva'], 2),
                    'total' => '$' . number_format($factura['total'], 2),
                    'enlace_factura' => 'https://tudominio.com/facturas/' . $factura['numero_factura']
                ]
            );
            
            if ($resultado) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Factura guardada y email enviado'
                ], 200);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Factura guardada pero error al enviar email'
                ], 200);
            }
        }
    }
}
```

---

## Uso en Models

```php
<?php
class FacturasModel extends Model
{
    public function guardarFactura($datos)
    {
        // Código de guardado en BD...
        $resultado = $this->db->execute($sql, $params);
        
        if ($resultado['success']) {
            // Después de guardar, enviar email
            require_once __DIR__ . '/../../libs/EmailService.php';
            $emailService = new EmailService($this->empresaCode);
            
            $emailService->enviar(
                $datos['email'],
                'Confirmación de Factura',
                'factura',
                [...]
            );
            
            return $resultado;
        }
        
        return $resultado;
    }
}
```

---

## Agregar Nueva Empresa

### Opción 1: Editar EmailService.php

```php
'NUEVAEMPRESA' => [
    'nombre' => 'Nueva Empresa S.A.',
    'logo' => 'https://tudominio.com/logos/nueva-empresa-logo.png',
    'color_primario' => '#FF6600',
    'color_secundario' => '#FF9933',
    'email_from' => 'noreply@nuevaempresa.com',
    'telefono' => '+593-2-111-2222',
    'website' => 'www.nuevaempresa.com'
]
```

### Opción 2: Agregar dinámicamente

```php
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

// Ahora puedes usar
$emailService = new EmailService('NUEVAEMPRESA');
```

---

## Crear Nueva Plantilla

### Paso 1: Crear archivo HTML

Crear `email-templates/nueva-plantilla.html`:

```html
<h2>Mi Plantilla Personalizada</h2>

<p>Hola {{nombre}},</p>

<p>Esto es un mensaje de {{EMPRESA}}.</p>

<div class="detalle">
    <p><strong>Dato 1:</strong> {{dato1}}</p>
    <p><strong>Dato 2:</strong> {{dato2}}</p>
</div>

<p>
    <a href="{{enlace}}" class="button">Botón de Acción</a>
</p>
```

### Paso 2: Usar en código

```php
$emailService->enviar(
    'usuario@email.com',
    'Asunto del Email',
    'nueva-plantilla',  // Nombre sin .html
    [
        'nombre' => 'Juan',
        'dato1' => 'Valor 1',
        'dato2' => 'Valor 2',
        'enlace' => 'https://tudominio.com/accion'
    ]
);
```

---

## Métodos Disponibles

### enviar()
Envía un email con plantilla.

```php
$emailService->enviar(
    $email,           // string - Email del destinatario
    $asunto,          // string - Asunto del email
    $plantilla,       // string - Nombre de plantilla (sin .html)
    $datos,           // array - Variables para la plantilla
    $htmlPersonalizado // string (opcional) - HTML personalizado
);
```

**Retorna:** `bool` - true si se envió, false si hubo error

### agregarEmpresa()
Agrega una nueva configuración de empresa.

```php
$emailService->agregarEmpresa(
    $codigo,    // string - Código de empresa
    $config     // array - Configuración
);
```

### obtenerConfigEmpresa()
Obtiene la configuración de la empresa actual.

```php
$config = $emailService->obtenerConfigEmpresa();
// Retorna: array con nombre, logo, colores, etc.
```

---

## Troubleshooting

### El email no se envía
- ✅ Verificar que `mail()` está habilitado en PHP
- ✅ Verificar logs: `php_errors.log`
- ✅ Validar que el email sea correcto: `filter_var($email, FILTER_VALIDATE_EMAIL)`

### Plantilla no encontrada
- ✅ Verificar que el archivo existe en `email-templates/`
- ✅ Verificar el nombre sin extensión `.html`
- ✅ Revisión de logs para ver la ruta exacta buscada

### Variables no se reemplazan
- ✅ Usar formato correcto: `{{variable_name}}`
- ✅ Las claves en el array deben coincidir exactamente
- ✅ Variables globales siempre en mayúsculas: `{{EMPRESA}}`

### Email con formato incorrecto
- ✅ Revisar que el servidor de mail está configurado
- ✅ Verificar `php.ini` settings para `sendmail_path`
- ✅ En desarrollo, usar servicios como MailHog o MailTrap

---

## Mejores Prácticas

1. **Siempre usar `new EmailService()`** sin parámetros para auto-detectar empresa
2. **Crear plantillas reutilizables** en lugar de HTML personalizado
3. **Validar emails** antes de enviar
4. **Usar variables** en lugar de hardcodear datos
5. **Loguear errores** para debugging
6. **Prueba con MailHog** en desarrollo

---

## Configuración en Producción

Para producción, configurar en `php.ini`:

```ini
; SMTP settings
SMTP = smtp.seuhost.com
smtp_port = 587

; O usar Sendmail
sendmail_path = "/usr/sbin/sendmail -t -i"
```

O configurar credenciales en `EmailService.php` para usar SMTP:

```php
// (Opcional) Agregar soporte SMTP usando PHPMailer
// composer require phpmailer/phpmailer
```

---

## Ejemplos Completos

Ver archivo: `EJEMPLOS_EMAIL_SERVICE.php`

---

**Última actualización:** 23 de diciembre de 2025  
**Versión:** 1.0
