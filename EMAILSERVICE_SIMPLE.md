# 📧 EmailService - Simple

## Uso Básico

```php
use libs\EmailService;

$email = new EmailService();

// Enviar email simple
$email->enviar(
    ['cliente@ejemplo.com'],           // Array de emails
    'Asunto del email',                // Asunto
    [                                   // Datos para la plantilla
        'numero_factura' => '001-001-000000001',
        'proveedor' => 'Juan García',
        'monto' => '$1000.00'
    ],
    'factura'                           // Nombre de plantilla (sin .html)
);
```

## Parámetros

```php
enviar(
    $destinatarios,     // string|array - Email o array de emails
    $asunto,            // string - Asunto del email
    $datos = [],        // array - Variables para reemplazar en plantilla
    $plantilla = 'generico',  // string - Nombre plantilla (sin .html)
    $htmlCustom = null  // string - [Opcional] HTML personalizado
)
```

## Ejemplos

### Un solo email
```php
$email->enviar(
    'cliente@ejemplo.com',
    'Tu Factura',
    ['numero' => '001-001-000000001'],
    'factura'
);
```

### Múltiples emails
```php
$email->enviar(
    ['email1@ejemplo.com', 'email2@ejemplo.com', 'email3@ejemplo.com'],
    'Notificación',
    ['mensaje' => 'Contenido aquí'],
    'generico'
);
```

### HTML personalizado
```php
$html = '<h1>Hola {{nombre}}</h1><p>{{mensaje}}</p>';

$email->enviar(
    'cliente@ejemplo.com',
    'Asunto',
    ['nombre' => 'Juan', 'mensaje' => 'Contenido'],
    'generico',
    $html  // HTML custom en lugar de plantilla
);
```

### Cambiar credenciales SMTP
```php
$email->configurarSMTP(
    'smtp.gmail.com',
    465,
    'tu_email@gmail.com',
    'tu_contraseña',
    PHPMailer::ENCRYPTION_SMTPS
);

$email->enviar(...);
```

## Plantillas

Las plantillas van en `email-templates/` y usan variables con `{{variable}}`:

**archivo: email-templates/factura.html**
```html
<h2>Factura {{numero_factura}}</h2>
<p>Proveedor: {{proveedor}}</p>
<p>Monto: {{monto}}</p>
```

Luego se usan así:

```php
$email->enviar(
    'email@ejemplo.com',
    'Tu Factura',
    [
        'numero_factura' => '001-001-000000001',
        'proveedor' => 'Juan García',
        'monto' => '$1000.00'
    ],
    'factura'  // Carga email-templates/factura.html
);
```

## En el Controlador

```php
use libs\EmailService;

class MiController {
    public function enviarFactura() {
        try {
            $emailService = new EmailService();
            
            $resultado = $emailService->enviar(
                ['cliente@ejemplo.com'],
                'Tu Factura ' . $numero,
                [
                    'numero_factura' => $numero,
                    'fecha' => date('d/m/Y'),
                    'total' => '$' . number_format($monto, 2)
                ],
                'factura'
            );
            
            if ($resultado) {
                echo "Email enviado";
            }
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
        }
    }
}
```

## Errores Comunes

**Email no se envía:**
- Verificar credenciales SMTP en EmailService.php
- Revisar logs: `php_errors.log`

**Plantilla no encontrada:**
- El archivo debe existir en `email-templates/nombre.html`
- Verificar que el nombre sea correcto (sin .html)

**Variables no se reemplazan:**
- Las variables deben estar en formato `{{variable}}`
- Las claves en el array `$datos` deben coincidir exactamente

---

**Versión**: 2.0 (Simplificada)
**Status**: ✅ Listo para usar
