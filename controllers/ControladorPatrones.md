# Controladores - Sistema SGO

## Descripción General

Este documento describe la estructura y patrones utilizados en los controladores del sistema SGO Back-end, específicamente enfocado en el manejo de autenticación JWT y conexiones automáticas de base de datos.

## Arquitectura de Controladores

### 1. Estructura Base

Todos los controladores extienden de la clase `Controller` y siguen el patrón MVC:

```php
class MiControlador extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'ruta/del/modelo/'; 
        $this->loadModel('nombredelmodelo');
    }
}
```

### 2. Autenticación JWT Integrada

Los controladores utilizan autenticación JWT para:
- Validar tokens de usuario
- Determinar la empresa activa automáticamente  
- Configurar conexiones de base de datos
- Controlar acceso a funciones

## Patrones de Autenticación

### 1. Función de Autenticación Principal

#### `$this->authenticateAndConfigureModel($nivel)`

**Parámetros**:
- **$nivel**: Nivel de autenticación requerido

**Niveles Disponibles**:
- `0`: Sin autenticación requerida
- `1`: GET/POST opcional (se intenta autenticar pero no es obligatorio)
- `2`: POST requerido con autenticación JWT obligatoria

**Retorna**:
- `$jwtData`: Array con información del JWT si es exitoso
- `false`: Si la autenticación falla (respuesta de error ya enviada)

### 2. Implementación Estándar

```php
function miFuncion()
{
    // Autenticación JWT obligatoria para POST
    $jwtData = $this->authenticateAndConfigureModel(2);
    if (!$jwtData) {
        return; // La respuesta de error ya fue enviada automáticamente
    }
    
    // Resto de la lógica...
}
```

## Funciones de Utilidad

### 1. Manejo de Datos JSON

#### `$this->getJsonInput()`
```php
// Obtener datos JSON del request
$data = $this->getJsonInput();
$usuario = $data['usrid'] ?? null;
```

### 2. Respuestas JSON Estandarizadas

#### `$this->jsonResponse($data, $httpCode)`
```php
// Respuesta exitosa
$this->jsonResponse([
    'success' => true,
    'data' => $resultado,
    'message' => 'Operación exitosa'
], 200);

// Respuesta de error
$this->jsonResponse([
    'success' => false,
    'error' => 'Descripción del error',
    'empresa_actual' => $jwtData['empresa'] ?? 'N/A'
], 400);
```

## Patrones por Tipo de Operación

### 1. Consultas de Lectura (GET)

```php
function obtenerDatos()
{
    // Autenticación opcional para GET
    $jwtData = $this->authenticateAndConfigureModel(1);
    if (!$jwtData) {
        return;
    }
    
    // Obtener datos del modelo
    $result = $this->model->obtenerDatos();
    
    if ($result && $result['success']) {
        $this->jsonResponse($result, 200);
    } else {
        $this->jsonResponse([
            'success' => false,
            'error' => 'Error al obtener datos',
            'empresa_actual' => $jwtData['empresa'] ?? 'N/A'
        ], 500);
    }
}
```

### 2. Operaciones de Escritura (POST)

```php
function guardarDatos()
{
    // Autenticación obligatoria para POST
    $jwtData = $this->authenticateAndConfigureModel(2);
    if (!$jwtData) {
        return;
    }
    
    // Validar datos de entrada
    $data = $this->getJsonInput();
    if (!$this->validarDatos($data)) {
        $this->jsonResponse([
            'success' => false,
            'error' => 'Datos inválidos'
        ], 400);
        return;
    }
    
    // Ejecutar operación
    $result = $this->model->guardarDatos($data);
    
    if ($result) {
        $this->jsonResponse([
            'success' => true,
            'message' => 'Datos guardados correctamente'
        ], 200);
    } else {
        $this->jsonResponse([
            'success' => false,
            'error' => 'Error al guardar datos'
        ], 500);
    }
}
```

### 3. Operaciones con Transacciones

```php
function operacionCompleja()
{
    $jwtData = $this->authenticateAndConfigureModel(2);
    if (!$jwtData) {
        return;
    }
    
    $data = $this->getJsonInput();
    
    // Iniciar transacción en el modelo
    $this->model->db->beginTransaction();
    
    try {
        // Múltiples operaciones
        $resultado1 = $this->model->operacion1($data);
        if (!$resultado1) {
            throw new Exception("Error en operación 1");
        }
        
        $resultado2 = $this->model->operacion2($data);
        if (!$resultado2) {
            throw new Exception("Error en operación 2");
        }
        
        // Confirmar transacción
        $this->model->db->commit();
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Operación completada exitosamente'
        ], 200);
        
    } catch (Exception $e) {
        // Rollback en caso de error
        $this->model->db->rollback();
        
        $this->jsonResponse([
            'success' => false,
            'error' => 'Error en la transacción: ' . $e->getMessage()
        ], 500);
    }
}
```

## Ejemplos de Controladores Refactorizados

### 1. VerificarFacturas Controller

**Archivo**: `controllers/logistica/facturacion/verificarfacturas.php`

```php
class VerificarFacturas extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/';
        $this->loadModel('verificarfacturas');
    }

    function ObtenerRMA()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        
        $data = $this->getJsonInput();
        $result = $this->model->obtenerRMA($data);
        
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener RMA',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A'
            ], 500);
        }
    }
}
```

### 2. PrepararFacturas Controller

**Archivo**: `controllers/logistica/facturacion/prepararfacturas.php`

```php
class PrepararFacturas extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/';
        $this->loadModel('prepararfacturas');
    }

    function VerificarFacturas()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        
        $data = $this->getJsonInput();
        
        // Validación específica
        if (!isset($data['facturas']) || !is_array($data['facturas'])) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Lista de facturas requerida'
            ], 400);
            return;
        }
        
        $result = $this->model->verificarFacturas($data);
        
        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al verificar facturas'
            ], 500);
        }
    }
}
```

### 3. GuiasPickup Controller

**Archivo**: `controllers/logistica/facturacion/guiaspickup.php`

```php
class GuiasPickup extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/';
        $this->loadModel('guiaspickup');
    }

    function GuardarGuiasPickup()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        
        $data = $this->getJsonInput();
        
        // Procesamiento de datos específico
        $BODEGAS = explode(',', $data['bodegas'][0] ?? null) ?? null;
        $id_unico = date('YmdHis') . rand(1000, 9999);
        $data['id_unico'] = $id_unico;
        
        // Transacción compleja
        $this->model->db->beginTransaction();
        
        $errores = [];
        
        // Procesar bodegas
        foreach ($BODEGAS as $bodega) {
            $data['bodega'] = $bodega;
            $result = $this->model->ActualizarFacturasListas($data);
            if (!$result) {
                $errores[] = "Error en bodega: $bodega";
            }
        }
        
        // Procesar guías
        foreach ($data['guiasPorBodega'] as $guia) {
            $guia['factura'] = $data["factura"];
            $guia['usrid'] = $data["usrid"];
            $result = $this->model->GuardarListaGuias($guia);
            if (!$result) {
                $errores[] = "Error en guía: " . json_encode($guia);
            }
        }
        
        if (count($errores) > 0) {
            $this->model->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'message' => 'Errores en el procesamiento',
                'errors' => $errores
            ], 500);
        } else {
            $this->model->db->commit();
            $this->jsonResponse([
                'success' => true,
                'message' => 'Datos guardados correctamente'
            ], 200);
        }
    }
}
```

## Validaciones Comunes

### 1. Validación de Usuario

```php
function validarUsuario($data)
{
    $usuario = $data['usrid'] ?? null;
    if (!$usuario) {
        $this->jsonResponse([
            'success' => false,
            'error' => 'Usuario no proporcionado'
        ], 400);
        return false;
    }
    return true;
}
```

### 2. Validación de Datos de Entrada

```php
function validarDatosFactura($data)
{
    $camposRequeridos = ['numero_factura', 'cliente_id', 'total'];
    
    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo]) || empty($data[$campo])) {
            $this->jsonResponse([
                'success' => false,
                'error' => "Campo requerido: $campo"
            ], 400);
            return false;
        }
    }
    return true;
}
```

### 3. Validación de Arrays

```php
function validarListaItems($data)
{
    if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
        $this->jsonResponse([
            'success' => false,
            'error' => 'Lista de items requerida y no puede estar vacía'
        ], 400);
        return false;
    }
    return true;
}
```

## Manejo de Errores Estándar

### 1. Error de Autenticación

```php
// Automático con authenticateAndConfigureModel()
$jwtData = $this->authenticateAndConfigureModel(2);
if (!$jwtData) {
    return; // Error 401 enviado automáticamente
}
```

### 2. Error de Validación (400)

```php
$this->jsonResponse([
    'success' => false,
    'error' => 'Datos inválidos',
    'details' => $detallesDelError
], 400);
```

### 3. Error de Servidor (500)

```php
$this->jsonResponse([
    'success' => false,
    'error' => 'Error interno del servidor',
    'empresa_actual' => $jwtData['empresa'] ?? 'N/A'
], 500);
```

### 4. Respuesta Exitosa (200)

```php
$this->jsonResponse([
    'success' => true,
    'data' => $datos,
    'message' => 'Operación completada exitosamente'
], 200);
```

## Mejores Prácticas para Controladores

### 1. Constructor Estándar

```php
public function __construct()
{
    parent::__construct();
    $this->folder = 'ruta/correcta/del/modelo/';
    $this->loadModel('nombre_correcto_del_modelo');
}
```

### 2. Autenticación Consistente

```php
// Siempre al inicio de cada función
$jwtData = $this->authenticateAndConfigureModel($nivel_apropiado);
if (!$jwtData) {
    return;
}
```

### 3. Validación Temprana

```php
// Validar datos antes de procesarlos
$data = $this->getJsonInput();
if (!$this->validarDatos($data)) {
    return; // Error ya enviado en validación
}
```

### 4. Manejo de Transacciones

```php
// Para operaciones múltiples
$this->model->db->beginTransaction();
try {
    // ... operaciones ...
    $this->model->db->commit();
} catch (Exception $e) {
    $this->model->db->rollback();
    // ... manejo de error ...
}
```

### 5. Respuestas Consistentes

```php
// Siempre incluir información relevante
$this->jsonResponse([
    'success' => true/false,
    'data' => $datos, // si aplica
    'message' => 'Mensaje descriptivo',
    'error' => 'Error descriptivo', // si aplica
    'empresa_actual' => $jwtData['empresa'] ?? 'N/A' // en errores
], $codigoHTTP);
```

## Debugging en Controladores

### 1. Logging de Operaciones

```php
if (DEBUG) {
    error_log("Controlador: " . __CLASS__ . " - Función: " . __FUNCTION__);
    error_log("Empresa actual: " . ($jwtData['empresa'] ?? 'N/A'));
    error_log("Datos recibidos: " . json_encode($data));
}
```

### 2. Información de Usuario

```php
// Incluir información del usuario en logs de error
$this->jsonResponse([
    'success' => false,
    'error' => 'Error específico',
    'user_info' => [
        'usuario' => $jwtData['usuario'] ?? 'N/A',
        'empresa' => $jwtData['empresa'] ?? 'N/A'
    ]
], 500);
```

## Migración de Controladores Existentes

### Checklist de Refactorización

- [ ] ✅ Corregir el `loadModel()` en constructor
- [ ] ✅ Agregar `authenticateAndConfigureModel()` a cada función
- [ ] ✅ Estandarizar respuestas JSON
- [ ] ✅ Implementar validaciones apropiadas
- [ ] ✅ Manejar transacciones cuando sea necesario
- [ ] ✅ Agregar logging de debug
- [ ] ✅ Testear cada función después de cambios

### Antes y Después

**Antes**:
```php
function miFuncion() {
    $data = $this->getJsonInput();
    $result = $this->model->operacion($data);
    echo json_encode($result);
}
```

**Después**:
```php
function miFuncion() {
    $jwtData = $this->authenticateAndConfigureModel(2);
    if (!$jwtData) {
        return;
    }
    
    $data = $this->getJsonInput();
    if (!$this->validarDatos($data)) {
        return;
    }
    
    $result = $this->model->operacion($data);
    
    if ($result && $result['success']) {
        $this->jsonResponse($result, 200);
    } else {
        $this->jsonResponse([
            'success' => false,
            'error' => 'Error en la operación',
            'empresa_actual' => $jwtData['empresa'] ?? 'N/A'
        ], 500);
    }
}
```

## Estado de Refactorización de Controladores

### Controladores Completados ✅

- ✅ **verificarfacturas.php** - Autenticación JWT integrada, respuestas estandarizadas
- ✅ **prepararfacturas.php** - Validaciones mejoradas, manejo de transacciones
- ✅ **guiaspickup.php** - Procesamiento complejo con transacciones robustas

### Beneficios Obtenidos

1. **Seguridad**: Autenticación JWT obligatoria
2. **Consistencia**: Respuestas estandarizadas
3. **Flexibilidad**: Conexión automática por empresa
4. **Mantenibilidad**: Código más limpio y estructurado
5. **Debugging**: Información detallada de errores
6. **Escalabilidad**: Patrones reutilizables

## Notas Importantes

- **Siempre** autenticar antes de operaciones críticas
- **Validar** datos de entrada antes de procesarlos
- **Usar transacciones** para operaciones múltiples
- **Estandarizar** respuestas JSON
- **Incluir información** de empresa en errores
- **Testear** cada función después de refactorizar
