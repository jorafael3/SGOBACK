# 🚀 Quick Reference - Múltiples Destinatarios

## TL;DR (Lo Más Importante)

```php
use libs\EmailService;

$email = new EmailService('COMPUTRONSA');

// ✓ Un email
$email->enviar('cliente@ejemplo.com', 'Asunto', 'plantilla');

// ✓ Múltiples emails
$email->enviar(
    ['e1@ejemplo.com', 'e2@ejemplo.com', 'e3@ejemplo.com'],
    'Asunto',
    'plantilla'
);

// ✓ Con nombres
$email->enviar(
    ['e1@ejemplo.com' => 'Nombre 1', 'e2@ejemplo.com' => 'Nombre 2'],
    'Asunto',
    'plantilla'
);

// ✓ Con CC y BCC
$email->enviar(
    $destinatarios,
    'Asunto',
    'plantilla',
    $datos,
    null,
    $cc,   // Copias visibles
    $bcc   // Copias ocultas
);
```

---

## Firmas de Métodos

### Método `enviar()` Completo

```php
enviar(
    $email,              // string|array
    $asunto,             // string
    $plantilla = 'generico',     // string
    $datos = [],         // array
    $htmlPersonalizado = null,   // string|null
    $cc = [],            // array (NEW)
    $bcc = []            // array (NEW)
): bool
```

---

## Formatos de Email

| Formato | Ejemplo | Caso |
|---------|---------|------|
| String | `'cliente@ejemplo.com'` | Un solo destinatario |
| Array | `['e1@.com', 'e2@.com']` | Múltiples sin nombres |
| Asociativo | `['e1@.com' => 'John', 'e2@.com' => 'Jane']` | Múltiples con nombres |

Todos los formatos funcionan en `$email`, `$cc` y `$bcc`.

---

## Ejemplos Mínimos

### 1. Un Email
```php
$email->enviar('cliente@ejemplo.com', 'Asunto', 'generico');
```

### 2. Varios Emails
```php
$email->enviar(
    ['e1@ejemplo.com', 'e2@ejemplo.com'],
    'Asunto',
    'generico'
);
```

### 3. Con CC
```php
$email->enviar(
    'cliente@ejemplo.com',
    'Asunto',
    'generico',
    [],
    null,
    ['supervisor@empresa.com']
);
```

### 4. Con BCC (Auditoría)
```php
$email->enviar(
    'cliente@ejemplo.com',
    'Asunto',
    'generico',
    [],
    null,
    null,
    ['auditoria@empresa.com']
);
```

### 5. Todo Junto
```php
$email->enviar(
    ['c1@.com', 'c2@.com'],
    'Asunto',
    'factura',
    ['numero' => '001-001-000000001'],
    null,
    ['supervisor@empresa.com'],
    ['auditoria@empresa.com']
);
```

---

## Patrones Comunes

### De Base de Datos
```php
$clientes = $db->query("SELECT EMAIL, NOMBRE FROM CLIENTES");
$dest = [];
foreach ($clientes as $c) {
    $dest[$c['EMAIL']] = $c['NOMBRE'];
}
$email->enviar($dest, 'Newsletter', 'generico');
```

### Array Dinámico
```php
$dest = ['cliente@ejemplo.com' => 'Cliente'];
$dest[] = 'gerente@ejemplo.com';  // Agrega sin nombre
$email->enviar($dest, 'Asunto', 'plantilla');
```

### Merge de Arrays
```php
$principales = ['e1@.com', 'e2@.com'];
$conNombre = ['e3@.com' => 'Nombre'];
$todos = array_merge($principales, $conNombre);
$email->enviar($todos, 'Asunto', 'plantilla');
```

### Con Validación
```php
$emails = ['v@.com', 'invalido', 'otro@.com'];
$validos = array_filter($emails, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
if (!empty($validos)) {
    $email->enviar($validos, 'Asunto', 'plantilla');
}
```

---

## Logging

Todos los emails se registran automáticamente:

```
[EmailService] Email enviado exitosamente a: e1@..., e2@..., e3@... - Empresa: COMPUTRONSA - Asunto: Asunto
```

Ver logs:
```bash
grep "EmailService" php_errors.log
```

---

## Notas Importantes

- ✓ Emails inválidos se ignoran automáticamente
- ✓ Se valida cada email con `filter_var()`
- ✓ Los nombres se escapan correctamente
- ✓ 100% compatible con código antiguo
- ⚠️ Límite SMTP: máximo 50-100 por email (depende del servidor)
- 💡 BCC es ideal para auditoría sin que el cliente se entere

---

## Troubleshooting Rápido

| Error | Solución |
|-------|----------|
| No se envía | Verificar que hay al menos 1 email válido |
| Falla SMTP | Revisar credenciales en `.env` |
| Email no llega | Revisar logs: `grep "EmailService" php_errors.log` |
| Demasiados recipients | Enviar en lotes de 50 |

---

## Referencias

- **Documentación Completa**: `MULTIPLES_DESTINATARIOS.md`
- **Ejemplos de Código**: `EJEMPLOS_MULTIPLES_DESTINATARIOS.php`
- **Resumen Visual**: `MULTIPLES_DESTINATARIOS_RESUMEN.txt`

---

**Versión**: 1.0  
**Status**: ✅ Listo para Producción
