# Funciones de Conexión - Sistema SGO

## Descripción General

Este documento describe las diferentes funciones de conexión utilizadas en el sistema SGO Back-end para el manejo de base de datos multi-empresa con soporte para SQL Server.

## Arquitectura de Conexiones

### 1. Configuración Multi-Empresa

El sistema maneja dos tipos de conexiones principales:
- **Producción**: `produccion_cartimex` (Servidor: 10.5.1.3)
- **Pruebas**: `pruebas_cartimex` (Servidor: 10.5.1.22)

### 2. Autenticación JWT

El sistema utiliza JWT (JSON Web Tokens) para:
- Autenticar usuarios
- Determinar automáticamente qué empresa usar
- Enrutar conexiones de base de datos

## Funciones de Conexión

### 1. Conexión Automática (Recomendado)

#### `$this->query($sql, $params = [])`
**Uso**: Para consultas SELECT (recuperación de datos)

```php
// Ejemplo de uso
$sql = "SELECT * FROM FACTURAS WHERE FacturaID = :factura_id";
$params = [":factura_id" => $facturaId];
$result = $this->query($sql, $params);
```

**Características**:
- Determina automáticamente la empresa desde el JWT
- Retorna objetos PDOStatement para consultas SELECT
- Manejo automático de errores
- Logging automático de consultas

#### `$this->db->execute($sql, $params = [])`
**Uso**: Para operaciones INSERT, UPDATE, DELETE

```php
// Ejemplo de uso
$sql = "INSERT INTO FACTURAS (Numero, Estado) VALUES (:numero, :estado)";
$params = [":numero" => $numero, ":estado" => "ACTIVO"];
$result = $this->db->execute($sql, $params);
```

**Características**:
- Retorna boolean (true/false) para operaciones de escritura
- Automáticamente usa la empresa del JWT
- Manejo de transacciones integrado
- Logging de errores automático

### 2. Conexión Específica (Depreciado)

#### `$this->queryInEmpresa($empresa, $sql, $params = [])`
**Estado**: DEPRECIADO - No usar en código nuevo

```php
// NO USAR - Solo para referencia
$stmt = $this->queryInEmpresa('pruebas_cartimex', $sql, $params);
```

#### `$this->executeInEmpresa($empresa, $sql, $params = [])`
**Estado**: DEPRECIADO - No usar en código nuevo

```php
// NO USAR - Solo para referencia
$result = $this->executeInEmpresa('pruebas_cartimex', $sql, $params);
```

## Manejo de Transacciones

### Inicio de Transacción
```php
$this->db->beginTransaction();
```

### Confirmar Transacción
```php
$this->db->commit();
```

### Cancelar Transacción
```php
$this->db->rollback();
```

### Ejemplo Completo de Transacción
```php
function guardarDatos($datos) {
    try {
        $this->db->beginTransaction();
        
        // Operación 1
        $sql1 = "INSERT INTO TABLA1 (campo1) VALUES (:valor1)";
        $result1 = $this->db->execute($sql1, [":valor1" => $datos['valor1']]);
        
        if (!$result1) {
            throw new Exception("Error en operación 1");
        }
        
        // Operación 2
        $sql2 = "UPDATE TABLA2 SET estado = :estado WHERE id = :id";
        $result2 = $this->db->execute($sql2, [
            ":estado" => "PROCESADO",
            ":id" => $datos['id']
        ]);
        
        if (!$result2) {
            throw new Exception("Error en operación 2");
        }
        
        $this->db->commit();
        return true;
        
    } catch (Exception $e) {
        $this->db->rollback();
        $this->logError("Error en transacción: " . $e->getMessage());
        return false;
    }
}
```

## Autenticación en Controladores

### Función de Autenticación
```php
// En el controlador
$jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
if (!$jwtData) {
    return; // La respuesta de error ya fue enviada automáticamente
}
```

### Niveles de Autenticación
- **Nivel 0**: Sin autenticación requerida
- **Nivel 1**: GET/POST opcional (se intenta autenticar pero no es obligatorio)
- **Nivel 2**: POST requerido con autenticación obligatoria

## Mejores Prácticas

### 1. Constructores de Modelos
```php
public function __construct($empresaCode = null)
{
    // Usar la empresa por defecto del sistema si no se especifica
    parent::__construct($empresaCode);
    
    // Debug: mostrar qué empresa estás usando
    if (DEBUG) {
        error_log("ModelName conectado a: " . $this->empresaCode);
    }
}
```

### 2. Manejo de Errores
```php
try {
    $result = $this->query($sql, $params);
    return $result;
} catch (Exception $e) {
    $this->logError("Error descriptivo: " . $e->getMessage());
    return false;
}
```

### 3. Validación de Parámetros
```php
function miFuncion($data) {
    // Validar datos de entrada
    $usuario = $data['usrid'] ?? null;
    if (!$usuario) {
        $this->logError("Usuario no proporcionado");
        return false;
    }
    // ... resto de la función
}
```

## Migración de Código Existente

### Pasos para Migrar

1. **Reemplazar queryInEmpresa**:
   ```php
   // Antes
   $stmt = $this->queryInEmpresa('pruebas_cartimex', $sql, $params);
   
   // Después
   $stmt = $this->query($sql, $params);
   ```

2. **Reemplazar executeInEmpresa**:
   ```php
   // Antes
   $result = $this->executeInEmpresa('pruebas_cartimex', $sql, $params);
   
   // Después
   $result = $this->db->execute($sql, $params);
   ```

3. **Agregar Autenticación en Controladores**:
   ```php
   // Al inicio de cada función del controlador
   $jwtData = $this->authenticateAndConfigureModel(2);
   if (!$jwtData) {
       return;
   }
   ```

4. **Actualizar Constructor del Modelo**:
   ```php
   public function __construct($empresaCode = null)
   {
       parent::__construct($empresaCode);
       
       if (DEBUG) {
           error_log("ModelName conectado a: " . $this->empresaCode);
       }
   }
   ```

## Debugging

### Variables de Debug
- **DEBUG**: Constante para activar logging detallado
- **$this->empresaCode**: Código de empresa actual
- **error_log()**: Función para logging de debug

### Verificar Conexión Actual
```php
if (DEBUG) {
    error_log("Empresa actual: " . $this->empresaCode);
}
```

## Archivos Refactorizados

### Modelos Actualizados
- ✅ `models/logistica/facturacion/verificarfacturasmodel.php`
- ✅ `models/logistica/facturacion/prepararfacturasmodel.php`
- ✅ `models/logistica/facturacion/guiaspickupmodel.php`

### Controladores Actualizados
- ✅ `controllers/logistica/facturacion/verificarfacturas.php`
- ✅ `controllers/logistica/facturacion/prepararfacturas.php`
- ✅ `controllers/logistica/facturacion/guiaspickup.php`

## Beneficios de la Nueva Arquitectura

1. **Flexibilidad**: Cambio automático entre empresas según usuario
2. **Seguridad**: Autenticación JWT integrada
3. **Mantenibilidad**: Código más limpio y estandarizado
4. **Debugging**: Logging automático de conexiones
5. **Transacciones**: Manejo robusto de operaciones múltiples
6. **Escalabilidad**: Fácil agregado de nuevas empresas

## Notas Importantes

- **Siempre** usar transacciones para operaciones múltiples
- **Nunca** hardcodear nombres de empresa en el código
- **Siempre** validar datos de entrada antes de usarlos
- **Usar** logging para debugging y monitoreo
- **Testear** cada función después de refactorizar
