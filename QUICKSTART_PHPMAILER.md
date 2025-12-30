# 🚀 Guía Rápida: PHPMailer en EmailService

## ⚡ TL;DR (Lo Más Importante)

1. **Instalar PHPMailer**:
   ```bash
   composer require phpmailer/phpmailer
   ```

2. **Configurar SMTP** (editar credenciales en `.env`):
   ```env
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USERNAME=tu_email@gmail.com
   SMTP_PASSWORD=tu_contraseña_app
   ```

3. **Probar**:
   ```bash
   php test_email.php
   ```

## 📝 Uso en Controladores

El código existente **NO CAMBIA**:

```php
use libs\EmailService;

// Funciona exactamente igual que antes
$emailService = new EmailService('COMPUTRONSA');
$emailService->enviar(
    'cliente@ejemplo.com',
    'Tu Factura',
    'factura',
    ['numero' => '001-001-000000001']
);
```

## 🔧 Configuración Avanzada

### En el Controlador:
```php
$emailService = new EmailService('COMPUTRONSA');

// Cambiar credenciales SMTP si es necesario
$emailService->configurarSMTP(
    'tu.servidor.com',
    587,
    'usuario@ejemplo.com',
    'contraseña123'
);

$emailService->enviar(...);
```

### Con Variables de Entorno:
```bash
export SMTP_HOST=smtp.gmail.com
export SMTP_PORT=587
export SMTP_USERNAME=email@gmail.com
export SMTP_PASSWORD=app_password
```

## ✅ Checklist

- [ ] `composer require phpmailer/phpmailer` ejecutado
- [ ] `vendor/autoload.php` existe
- [ ] `.env` configurado con credenciales SMTP
- [ ] `php test_email.php` ejecutado exitosamente
- [ ] Email de prueba recibido

## 🆘 Si Algo No Funciona

```bash
# Ver errores detallados
grep "PHPMailer\|EmailService" php_errors.log

# Ejecutar diagnóstico completo
php test_email.php

# Verificar permisos
ls -la vendor/phpmailer/phpmailer/
```

## 📚 Documentación Detallada

Consulta `PHPMAILER_CONFIG.md` para:
- Configuración por proveedor
- Manejo de errores
- Características avanzadas
- Solución de problemas

## 🎯 Notas Importantes

✅ **Mejoras Implementadas**:
- SMTP autenticado (no depende del servidor local)
- Mejor manejo de errores
- Soporte multi-empresa
- Logging detallado
- SSL/TLS cifrado

⚠️ **Requisitos**:
- PHP 7.0+
- PHPMailer 6.0+
- Credenciales SMTP válidas

---

**Última Actualización**: 2024
**Status**: ✅ Listo para Producción
