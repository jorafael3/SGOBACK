# Configuración de PHPMailer para EmailService

## Descripción General

`EmailService.php` ahora utiliza **PHPMailer** para envío de emails mediante SMTP en lugar de la función nativa `mail()` de PHP. Esto proporciona mejor confiabilidad, autenticación y manejo de errores.

## Instalación de PHPMailer

### Via Composer (Recomendado)

```bash
composer require phpmailer/phpmailer
```

### Verificar instalación

La librería debe estar en `vendor/phpmailer/phpmailer/src/`

## Configuración SMTP

### Método 1: Variables de Entorno

Crea un archivo `.env` en la raíz del proyecto (o configura en tu servidor):

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=tu_email@gmail.com
SMTP_PASSWORD=tu_contraseña_aplicacion
```

Carga las variables en tu `config/config.php`:

```php
// En config/config.php
require_once __DIR__ . '/../.env.php'; // Si usas archivo PHP

// O usar getenv() directamente si están configuradas en el servidor
$smtpHost = getenv('SMTP_HOST');
```

### Método 2: Configuración Manual en el Código

```php
use libs\EmailService;

$emailService = new EmailService();

// Configurar SMTP manualmente
$emailService->configurarSMTP(
    'smtp.gmail.com',      // host
    587,                   // port
    'tu_email@gmail.com',  // username
    'tu_contraseña',       // password
    PHPMailer::ENCRYPTION_STARTTLS
);

// Enviar email
$emailService->enviar(
    'destinatario@ejemplo.com',
    'Asunto del email',
    'generico',
    ['contenido' => 'Mensaje de prueba']
);
```

### Método 3: Heredar la Configuración en EmailService (Recomendado)

EmailService ahora llama automáticamente a `cargarConfiguracionSMTP()` en el constructor, que busca las variables de entorno. Los valores por defecto actuales son:

```php
private $smtpConfig = [
    'host' => 'smtp.gmail.com',           // Cambiar según tu servidor
    'port' => 587,                        // 587 para TLS, 465 para SSL
    'secure' => PHPMailer::ENCRYPTION_STARTTLS,  // STARTTLS o SSL
    'username' => '',                     // Tu email SMTP
    'password' => ''                      // Tu contraseña SMTP
];
```

## Configuraciones SMTP por Proveedor

### Gmail
```php
'host' => 'smtp.gmail.com',
'port' => 587,
'secure' => PHPMailer::ENCRYPTION_STARTTLS,
'username' => 'tu_email@gmail.com',
'password' => 'contraseña_app_específica'  // NO usar contraseña de cuenta
```

**Nota**: Gmail requiere [generar una contraseña de aplicación](https://support.google.com/accounts/answer/185833)

### Outlook/Office365
```php
'host' => 'smtp-mail.outlook.com',
'port' => 587,
'secure' => PHPMailer::ENCRYPTION_STARTTLS,
'username' => 'tu_email@outlook.com',
'password' => 'tu_contraseña'
```

### SendGrid
```php
'host' => 'smtp.sendgrid.net',
'port' => 587,
'secure' => PHPMailer::ENCRYPTION_STARTTLS,
'username' => 'apikey',
'password' => 'SG.xxxxxxxxxxxxxxxxxxxxx'  // Tu API Key
```

### Servidor SMTP Personalizado
```php
'host' => 'mail.tudominio.com',
'port' => 587,  // O 465 para SSL
'secure' => PHPMailer::ENCRYPTION_STARTTLS,  // O ENCRYPTION_SMTPS
'username' => 'usuario@tudominio.com',
'password' => 'contraseña'
```

## Uso en Controladores

### Envío Simple

```php
<?php
require_once __DIR__ . '/../../libs/EmailService.php';

use libs\EmailService;

class NuevaFacturaController {
    public function setGuardarFactura() {
        try {
            // ... código de guardado de factura ...
            
            $emailService = new EmailService('COMPUTRONSA');
            $resultado = $emailService->enviar(
                'cliente@ejemplo.com',
                'Tu Factura ha sido Creada',
                'factura',
                [
                    'numero_factura' => '001-001-000000001',
                    'cliente' => 'John Doe',
                    'total' => '$100.00'
                ]
            );
            
            if (!$resultado) {
                error_log("Fallo al enviar email de factura");
            }
            
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
        }
    }
}
?>
```

### Con HTML Personalizado

```php
$emailService = new EmailService();

$htmlPersonalizado = '<h2>Confirmación de Pago</h2>';
$htmlPersonalizado .= '<p>Tu pago ha sido procesado exitosamente.</p>';

$emailService->enviar(
    'cliente@ejemplo.com',
    'Confirmación de Pago',
    'generico',
    [],
    $htmlPersonalizado
);
```

## Plantillas Email

Las plantillas deben estar en `email-templates/` y usar el formato `{{VARIABLE}}` para placeholders:

### Plantillas Disponibles

1. **generico.html** - Plantilla general para cualquier contenido
2. **factura.html** - Para notificaciones de facturas
3. **orden-compra.html** - Para órdenes de compra
4. **confirmacion.html** - Para confirmaciones de registro

**Estructura básica de plantilla:**

```html
<h2>{{TITULO}}</h2>
<p>{{CONTENIDO}}</p>
<p>Detalles:</p>
<ul>
    <li>Número: {{NUMERO}}</li>
    <li>Monto: {{MONTO}}</li>
</ul>
```

## Manejo de Errores

PHPMailer proporciona información detallada sobre errores:

```php
try {
    $emailService = new EmailService();
    $resultado = $emailService->enviar(...);
    
    if (!$resultado) {
        // El error ya fue registrado en error_log
        // Revisar logs en: php_errors.log o includes/php_errors.log
    }
} catch (Exception $e) {
    error_log("Excepción PHPMailer: " . $e->getMessage());
}
```

**Errores Comunes:**

| Error | Causa | Solución |
|-------|-------|----------|
| `SMTP Error: Could not authenticate` | Credenciales inválidas | Verificar usuario/contraseña |
| `SMTP Error: Could not connect to host` | Host SMTP incorrecto o inaccesible | Verificar host y puerto |
| `SMTP Error: mail not accepted from this address` | Email FROM no configurado | Actualizar `email_from` en config empresa |
| `OpenSSL error` | Certificado SSL/TLS no válido | Verificar configuración de puerto y encriptación |

## Testing

### Script de Prueba

Crea `test_email.php` en la raíz:

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/libs/EmailService.php';
use libs\EmailService;

echo "=== Test PHPMailer ===\n\n";

try {
    $emailService = new EmailService('COMPUTRONSA');
    
    // Configuración manual para testing
    $emailService->configurarSMTP(
        'smtp.gmail.com',
        587,
        'tu_email@gmail.com',
        'tu_contraseña_app'
    );
    
    // Enviar email de prueba
    $resultado = $emailService->enviar(
        'tu_email_prueba@gmail.com',
        'Test de EmailService con PHPMailer',
        'generico',
        ['mensaje' => 'Este es un test de configuración correcta']
    );
    
    if ($resultado) {
        echo "✓ Email enviado exitosamente\n";
    } else {
        echo "✗ Fallo al enviar email\n";
        echo "Revisa php_errors.log para más detalles\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
```

**Ejecutar prueba:**

```bash
# Desde la raíz del proyecto
php test_email.php
```

## Logs

PHPMailer registra automáticamente:

- ✓ Emails enviados exitosamente
- ✗ Errores de conexión SMTP
- ✗ Errores de validación
- ✗ Excepciones de PHPMailer

**Ubicación de logs:**
- `php_errors.log` (raíz del proyecto)
- `includes/php_errors.log` (carpeta includes)

**Buscar errores de email:**

```bash
grep "EmailService" php_errors.log
grep "PHPMailer" php_errors.log
```

## Funcionalidades Adicionales

### Attachments (Adjuntos)

```php
// Nota: El método actual enviar() no tiene parámetro de adjuntos
// Para agregar adjuntos, necesitarías extender EmailService

// Ejemplo de código para agregar adjuntos:
$mail = new PHPMailer(true);
// ... configuración SMTP ...
$mail->addAttachment('/ruta/al/archivo.pdf', 'nombre_archivo.pdf');
```

### Reply-To (Responder A)

El remitente está configurado desde `empresaConfig['email_from']`, que se usa como reply-to automáticamente.

### CC y BCC

Para agregar CC/BCC, se requeriría extender el método `enviar()`:

```php
// Extensión futura para CC/BCC
$mail->addCC('cc@ejemplo.com');
$mail->addBCC('bcc@ejemplo.com');
```

## Seguridad

1. **Nunca pushear credenciales** - Usar variables de entorno o archivos `.env` no trackeados
2. **Usar contraseñas de aplicación** - En Gmail, Outlook, etc., generar contraseñas específicas
3. **Verificar certificados SSL** - Algunos servidores requieren desactivar SSL verification (no recomendado en producción)
4. **Rate Limiting** - Algunos SMTP servers tienen límites de envío por hora

## Solución de Problemas

### Email no se envía pero tampoco hay error

- Verificar que PHPMailer esté instalado vía Composer
- Verificar que `vendor/autoload.php` exista
- Revisar si las credenciales están vacías
- Ejecutar `test_email.php` para diagnosticar

### "Could not connect to host"

- El puerto SMTP es incorrecto (587 para TLS, 465 para SSL)
- El host SMTP es inaccesible desde el servidor
- El firewall bloquea conexiones SMTP

### "SMTP authentication failed"

- Credenciales incorrectas
- Cuenta SMTP está deshabilitada
- Para Gmail: generar [contraseña de app](https://support.google.com/accounts/answer/185833)

### Email llega a spam

- Configurar SPF, DKIM, DMARC en tu dominio
- Usar direcciones FROM que coincidan con el dominio
- Incluir unsubscribe links para newsletters

## Referencias

- [Documentación PHPMailer](https://github.com/PHPMailer/PHPMailer)
- [SMTP RFC 5321](https://tools.ietf.org/html/rfc5321)
- [Generador de contraseñas Gmail](https://support.google.com/accounts/answer/185833)
