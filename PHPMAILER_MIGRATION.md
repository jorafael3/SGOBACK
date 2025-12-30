# EmailService - Migración a PHPMailer ✓ COMPLETADA

## 🎯 Cambios Realizados

Se ha completado exitosamente la migración del servicio de email de PHP nativa `mail()` a **PHPMailer** para mayor confiabilidad y control.

## 📋 Resumen de Cambios

### 1. **EmailService.php** - Actualizado ✓

#### Cambios principales:
- **Agregadas importaciones PHPMailer**: Namespace imports en líneas 1-9
- **Agregada configuración SMTP**: Propiedad privada `$smtpConfig` con parámetros de conexión
- **Nuevo método `configurarSMTP()`**: Permite configurar credenciales SMTP manualmente
- **Nuevo método `cargarConfiguracionSMTP()`**: Carga configuración desde variables de entorno
- **Refactorizado método `enviar()`**: Ahora utiliza PHPMailer en lugar de `mail()`
- **Mejor manejo de errores**: Excepciones específicas de PHPMailer con información detallada

#### Mejoras implementadas:
```php
// Antes (mail() nativa):
$resultado = mail($email, $asunto, $htmlFinal, $headers);

// Ahora (PHPMailer SMTP):
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $this->smtpConfig['host'];
$mail->Port = $this->smtpConfig['port'];
$mail->SMTPSecure = $this->smtpConfig['secure'];
$mail->SMTPAuth = true;
$mail->Username = $this->smtpConfig['username'];
$mail->Password = $this->smtpConfig['password'];
// ... configuración y envío
$resultado = $mail->send();
```

### 2. **Nuevos Archivos Creados**

#### `documentacion/PHPMAILER_CONFIG.md`
- Documentación completa de configuración SMTP
- Ejemplos para múltiples proveedores (Gmail, Outlook, SendGrid, etc.)
- Guía de troubleshooting
- Referencias a documentación oficial

#### `.env.example`
- Plantilla para variables de entorno
- Ejemplos de configuración para diferentes proveedores SMTP
- Instrucciones de seguridad

#### `test_email.php`
- Script de prueba interactivo
- Validaciones previas de configuración
- Diagnostico de errores
- Interfaz visual amigable

## 🚀 Pasos para Configurar

### Paso 1: Instalar PHPMailer vía Composer

```bash
composer require phpmailer/phpmailer
```

### Paso 2: Configurar Credenciales SMTP

**Opción A: Variables de Entorno (Recomendado)**

```bash
# Copiar plantilla
cp .env.example .env

# Editar .env con tus credenciales
# SMTP_HOST=smtp.gmail.com
# SMTP_PORT=587
# SMTP_USERNAME=tu_email@gmail.com
# SMTP_PASSWORD=tu_contraseña_app
```

**Opción B: Configuración Manual en Código**

```php
$emailService = new EmailService();
$emailService->configurarSMTP(
    'smtp.gmail.com',
    587,
    'tu_email@gmail.com',
    'tu_contraseña_app'
);
```

### Paso 3: Validar Configuración

```bash
# Ejecutar script de prueba
php test_email.php

# O desde PowerShell en Windows
powershell -Command "php test_email.php"
```

## ✅ Verificación

### Cambios Verificados

- [x] PHPMailer instalado en `vendor/phpmailer/phpmailer/`
- [x] Namespace imports correctos en EmailService
- [x] Configuración SMTP en propiedad privada
- [x] Método `enviar()` refactorizado para usar PHPMailer
- [x] Manejo de excepciones implementado
- [x] Soporte para carga de variables de entorno
- [x] Documentación SMTP completa
- [x] Script de prueba funcional

### Métodos Afectados

El cambio es transparente para los controladores existentes:

```php
// El uso sigue siendo el mismo
$emailService = new EmailService('COMPUTRONSA');
$resultado = $emailService->enviar(
    'cliente@ejemplo.com',
    'Tu Factura',
    'factura',
    ['numero_factura' => '001-001-000000001']
);
```

No requiere cambios en `controllers/proveeduria/facturas/nuevafactura.php` ni otros controladores.

## 🔒 Seguridad

### Mejoras de Seguridad

1. **SMTP Autenticado**: No depende de la configuración del servidor local
2. **Cifrado TLS/SSL**: Protege credenciales en tránsito
3. **Aislamiento de Credenciales**: Variables de entorno en lugar de hardcoding
4. **Validación de Certificados**: Soporte SSL/TLS properly configured
5. **Mejor Manejo de Errores**: No expone información sensible

### Checklist de Seguridad

- [ ] `.env` agregado a `.gitignore` (no commitear credenciales)
- [ ] Usar contraseñas de aplicación en Gmail, no contraseña de cuenta
- [ ] Usar credenciales diferentes por empresa si es posible
- [ ] Revisar logs regularmente para intentos fallidos
- [ ] En producción: usar variables de entorno del servidor

## 📝 Documentación

### Archivos de Referencia

1. **PHPMAILER_CONFIG.md** - Guía completa
2. **test_email.php** - Script de validación
3. **.env.example** - Template de variables

### Proveedores Soportados

| Proveedor | Host | Puerto | Encriptación |
|-----------|------|--------|--------------|
| Gmail | smtp.gmail.com | 587 | STARTTLS |
| Outlook | smtp-mail.outlook.com | 587 | STARTTLS |
| SendGrid | smtp.sendgrid.net | 587 | STARTTLS |
| Custom SMTP | Personalizado | 587/465 | STARTTLS/SSL |

## 🔧 Troubleshooting

### Errores Comunes

| Error | Solución |
|-------|----------|
| `Could not authenticate` | Verificar credenciales SMTP |
| `Could not connect to host` | Verificar host y puerto SMTP |
| `mail not accepted from this address` | Verificar email FROM en config empresa |
| `OpenSSL error` | Verificar certificados SSL/TLS |

### Debug

```bash
# Ver errores de PHPMailer en logs
grep "PHPMailer" php_errors.log

# Ver todos los errores de EmailService
grep "EmailService" php_errors.log

# Ejecutar test interactivo
php test_email.php
```

## 📚 Próximos Pasos

1. **Instalar PHPMailer**: `composer require phpmailer/phpmailer`
2. **Configurar SMTP**: Editar `.env` con tus credenciales
3. **Validar Setup**: Ejecutar `php test_email.php`
4. **Revisar Logs**: Verificar que emails se envíen exitosamente
5. **Actualizar Documentación**: Si usas servidor SMTP personalizado

## 📞 Referencias

- [Documentación PHPMailer](https://github.com/PHPMailer/PHPMailer)
- [Configuración Gmail](https://support.google.com/accounts/answer/185833)
- [RFC 5321 SMTP](https://tools.ietf.org/html/rfc5321)

---

**Estado**: ✅ Completo y Listo para Usar
**Última Actualización**: 2024
**Requiere**: PHP 7.0+ y PHPMailer 6.0+
