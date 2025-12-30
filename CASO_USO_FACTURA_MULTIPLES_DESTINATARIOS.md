# 📧 Caso de Uso: Notificación de Factura en nuevafactura.php

## 🎯 Implementación Actual

El controlador `nuevafactura.php` ha sido actualizado para enviar emails de factura a **múltiples destinatarios** con **CC y BCC**.

## 📊 Flujo de Email

```
FACTURA REGISTRADA
    ↓
    ├─ TO: Proveedor (destinatario principal)
    ├─ CC: Responsable (copia visible)
    └─ BCC: Contabilidad + Auditoría (copias ocultas)
```

## 💻 Código Implementado

### Antes (Simple)
```php
$emailService->enviar(
    $data['email_proveedor'] ?? 'noreply@empresa.com',
    'Factura Registrada: ' . $secuencia_formateada,
    'factura',
    [
        'numero_factura' => $secuencia_formateada,
        // ... otros datos ...
    ]
);
```

### Ahora (Múltiples Destinatarios)
```php
// Preparar destinatarios principales
$destinatarios = [];
if (!empty($data['email_proveedor'])) {
    $destinatarios[$data['email_proveedor']] = $data['proveedor'] ?? 'Proveedor';
}
if (empty($destinatarios)) {
    $destinatarios['noreply@empresa.com'] = 'Empresa';
}

// Preparar CC: responsable
$cc = [];
if (!empty($data['email_responsable'])) {
    $cc[$data['email_responsable']] = $data['responsable'] ?? 'Responsable';
}

// Preparar BCC: auditoría
$bcc = [
    'contabilidad@empresa.com' => 'Contabilidad',
    'auditoria@empresa.com' => 'Auditoría'
];

// Enviar con múltiples destinatarios
$emailService->enviar(
    $destinatarios,              // TO: Proveedor
    'Factura Registrada: ...',
    'factura',
    $datosEmail,
    null,
    $cc,                         // CC: Responsable
    $bcc                         // BCC: Contabilidad + Auditoría
);
```

## 📋 Destinatarios por Tipo

### TO (Destinatarios Principales)
```php
$destinatarios = [
    'proveedor@ejemplo.com' => 'Nombre del Proveedor'
]
```
**Quién recibe**: El proveedor que registró la factura
**Visibilidad**: Ve su email y los CC

### CC (Copia Visible)
```php
$cc = [
    'responsable@empresa.com' => 'Responsable del Área'
]
```
**Quién recibe**: El responsable del área
**Visibilidad**: Ve al proveedor (TO) y también se ve a sí mismo (CC)

### BCC (Copia Oculta)
```php
$bcc = [
    'contabilidad@empresa.com' => 'Contabilidad',
    'auditoria@empresa.com' => 'Auditoría'
]
```
**Quién recibe**: Contabilidad y Auditoría
**Visibilidad**: Ven TO y CC, pero nadie ve el BCC (es oculto)

## 🔄 Flujo de Datos

### Entrada (JSON POST)
```json
{
    "secuencia": "1-1-5656",
    "proveedor": "Juan García",
    "email_proveedor": "juan@proveedores.com",
    "email_responsable": "responsable@empresa.com",
    "responsable": "Carlos López",
    "tipo_gasto": "Servicios",
    "monto": 1500.00
}
```

### Procesamiento
1. **Validar secuencia** → Formatea a `001-001-005656`
2. **Guardar factura** en base de datos
3. **Construir destinatarios**:
   - TO: `juan@proveedores.com` (Juan García)
   - CC: `responsable@empresa.com` (Carlos López)
   - BCC: `contabilidad@empresa.com`, `auditoria@empresa.com`
4. **Enviar email** a todos con plantilla 'factura'

### Email Enviado

**Visible para Juan García (Proveedor)**:
```
Para: juan@proveedores.com
Asunto: Factura Registrada: 001-001-005656
CC: responsable@empresa.com (Carlos López)

Contenido:
  Factura: 001-001-005656
  Proveedor: Juan García
  Gasto: Servicios
  Monto: $1,500.00
  ...
```

**Visible para Carlos López (Responsable - CC)**:
```
Para: juan@proveedores.com
CC: responsable@empresa.com ← Se ve aquí
Asunto: Factura Registrada: 001-001-005656
```

**Visible para Contabilidad (BCC)**:
```
Para: juan@proveedores.com
CC: responsable@empresa.com
BCC: contabilidad@empresa.com ← Solo lo ve quien recibe BCC

(Contabilidad ve todo pero nadie sabe que recibió copia)
```

## 🛡️ Validaciones

### Destinatarios Principales
```php
if (!empty($data['email_proveedor'])) {
    $destinatarios[$data['email_proveedor']] = $data['proveedor'];
}
// Si no hay email de proveedor, usa fallback
if (empty($destinatarios)) {
    $destinatarios['noreply@empresa.com'] = 'Empresa';
}
```
✓ Si hay email del proveedor, se usa
✓ Si no, usa `noreply@empresa.com` como fallback

### CC: Responsable
```php
$cc = [];
if (!empty($data['email_responsable'])) {
    $cc[$data['email_responsable']] = $data['responsable'] ?? 'Responsable';
}
```
✓ Solo se agrega si existe email del responsable
✓ Si no existe, CC quedará vacío (sin problema)

### BCC: Auditoría
```php
$bcc = [
    'contabilidad@empresa.com' => 'Contabilidad',
    'auditoria@empresa.com' => 'Auditoría'
];
```
✓ Siempre se envía a contabilidad y auditoría
⚠️ Reemplaza con tus dominios reales

## 📧 Plantilla Utilizada

**Archivo**: `email-templates/factura.html`

**Variables disponibles** (desde `$datosEmail`):
- `{{numero_factura}}` → 001-001-005656
- `{{fecha}}` → 23/12/2025
- `{{cliente}}` → Juan García
- `{{descripcion_producto}}` → Servicios
- `{{cantidad}}` → 1
- `{{precio_unitario}}` → $1,500.00
- `{{subtotal}}` → $1,500.00
- `{{iva}}` → $0.00
- `{{total}}` → $1,500.00
- `{{enlace_factura}}` → Link a la factura

## 🔧 Customización

### Cambiar Destinatarios CC
Si quieres agregar más personas a CC:

```php
$cc = [];
if (!empty($data['email_responsable'])) {
    $cc[$data['email_responsable']] = $data['responsable'];
}
// Agregar más
$cc['supervisor@empresa.com'] = 'Supervisor';
$cc['gerente@empresa.com'] = 'Gerente';
```

### Cambiar Destinatarios BCC
Para modificar quién recibe auditoría:

```php
$bcc = [
    'contabilidad@empresa.com' => 'Contabilidad',
    'auditoria@empresa.com' => 'Auditoría',
    'archivo@empresa.com' => 'Archivo',  // Agregar más
];
```

### Cambiar Plantilla
Si deseas usar otra plantilla:

```php
$emailService->enviar(
    $destinatarios,
    'Factura Registrada: ' . $secuencia_formateada,
    'orden-compra',  // ← Cambiar plantilla aquí
    $datosEmail,
    null,
    $cc,
    $bcc
);
```

## 🚀 Escenarios de Uso

### Escenario 1: Email solo al Proveedor
Si solo quieres enviar al proveedor sin CC ni BCC:

```php
$destinatarios = ['proveedor@ejemplo.com' => 'Proveedor'];
$cc = [];
$bcc = [];

$emailService->enviar($destinatarios, 'Asunto', 'factura', $datos, null, $cc, $bcc);
```

### Escenario 2: Múltiples Proveedores
Si quieres notificar a varios proveedores:

```php
$destinatarios = [
    'proveedor1@ejemplo.com' => 'Proveedor 1',
    'proveedor2@ejemplo.com' => 'Proveedor 2',
    'contacto@ejemplo.com' => 'Contacto'
];

$emailService->enviar($destinatarios, 'Asunto', 'factura', $datos, null, $cc, $bcc);
```

### Escenario 3: Auditoría Estricta
Si necesitas más supervisión:

```php
$bcc = [
    'contabilidad@empresa.com' => 'Contabilidad',
    'auditoria@empresa.com' => 'Auditoría',
    'director@empresa.com' => 'Director Financiero',
    'rrhh@empresa.com' => 'RRHH'  // Para cumplimiento
];
```

## ✅ Checklist de Verificación

Antes de poner en producción:

- [ ] Reemplazar `contabilidad@empresa.com` con email real
- [ ] Reemplazar `auditoria@empresa.com` con email real
- [ ] Verificar que plantilla 'factura' existe
- [ ] Probar con datos reales (email de prueba)
- [ ] Validar que emails llegan a los correctos
- [ ] Revisar que CC y BCC funcionan
- [ ] Revisar logs: `grep "EmailService" php_errors.log`

## 📊 Logging

Se registra automáticamente:
```
[EmailService] Email enviado exitosamente a: proveedor@ejemplo.com, responsable@empresa.com (CC), contabilidad@empresa.com (BCC), auditoria@empresa.com (BCC) - Empresa: COMPUTRONSA - Asunto: Factura Registrada: 001-001-005656
```

Ver logs:
```bash
grep "EmailService" php_errors.log
```

## 🔐 Seguridad

✓ **BCC para auditoría**: Nadie sabe que auditoría ve el email
✓ **Validación de emails**: Solo emails válidos se envían
✓ **Error handling**: Si hay error, solo se registra, no detiene el proceso
✓ **Datos sensibles**: No se exponene en respuesta JSON

## 🆘 Troubleshooting

| Problema | Solución |
|----------|----------|
| No llega a CC | Validar que `email_responsable` existe en `$data` |
| BCC no recibe | Verificar que emails de contabilidad/auditoría son válidos |
| No llega a nadie | Revisar logs y credenciales SMTP en `.env` |
| Email sin branding | Verificar que plantilla 'factura.html' existe |

## 📝 Próximas Mejoras

Opcionales:
- [ ] Agregar archivo adjunto (PDF de factura)
- [ ] Enviar a múltiples responsables en CC
- [ ] Permitir customizar BCC desde admin
- [ ] Crear plantilla específica para factura (vs orden compra)
- [ ] Envío asincrónico si hay muchos destinatarios

---

**Status**: ✅ Implementado y Listo
**Archivo**: `controllers/proveeduria/facturas/nuevafactura.php`
**Líneas**: 156-207 (SetGuardarFactura → envío de email)
