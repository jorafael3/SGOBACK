# 🏢 Sistema Multiempresa - Plantilla MVC con Routing Recursivo

## 📋 Descripción General

Este sistema MVC implementa un patrón **multiempresa** con **routing recursivo** que permite manejar múltiples bases de datos desde una sola aplicación, con autenticación JWT y configuración automática de conexiones por empresa.

## 🚀 Características Principales

- ✅ **Autenticación automática con JWT**
- ✅ **Configuración automática de empresa desde token**
- ✅ **Consultas cruzadas entre empresas**
- ✅ **Routing recursivo para controladores y modelos**
- ✅ **Búsqueda automática en cualquier nivel de carpetas**
- ✅ **Métodos helper para simplificar código**
- ✅ **Patrón singleton para conexiones de base de datos**
- ✅ **Validación y sanitización automática**
- ✅ **Logging de errores por empresa**

## 🏗️ Arquitectura del Sistema

```
back2/
├── config/
│   ├── config.php          # Configuración multiempresa
│   └── .env                # Variables de entorno por empresa
├── libs/
│   ├── controller.php      # Controlador base con búsqueda recursiva
│   ├── model.php           # Modelo base con consultas cruzadas
│   ├── app.php             # Routing recursivo automático
│   ├── database.php        # Manejo de conexiones multiempresa
│   └── JwtHelper.php       # Utilidades JWT
├── controllers/            # 🔄 ESTRUCTURA RECURSIVA
│   ├── principal.php       # /principal
│   ├── login/
│   │   └── login.php       # /login/authenticate
│   ├── ejemplos/
│   │   ├── ejemplomultiempresa.php  # /ejemplos/ejemplomultiempresa/metodo
│   │   └── pr/
│   │       └── pr.php      # /ejemplos/pr/pr/metodo
│   └── a/b/c/d/
│       └── controlador.php # /a/b/c/d/controlador/metodo
├── models/                 # � ESTRUCTURA RECURSIVA
│   ├── loginmodel.php
│   ├── ejemplos/
│   │   ├── ejemplomodel.php
│   │   └── pr/
│   │       └── prmodel.php
│   └── cualquier/estructura/
│       └── mimodel.php
```

## ⚙️ Configuración

### 1. Variables de Entorno (.env)

```env
# EMPRESA 1 - Cartimex
EMPRESA1_CODE=pruebas_cartimex
EMPRESA1_NAME=Cartimex
EMPRESA1_DB_HOST=10.5.1.86
EMPRESA1_DB_NAME=CARTIMEX
EMPRESA1_DB_USER=jalvarado
EMPRESA1_DB_PASSWORD=12345
EMPRESA1_DB_DRIVER=sqlsrv

# EMPRESA 2 - Computron
EMPRESA2_CODE=pruebas_computron
EMPRESA2_NAME=Computron
EMPRESA2_DB_HOST=10.5.1.86
EMPRESA2_DB_NAME=COMPUTRONSA
EMPRESA2_DB_USER=jalvarado
EMPRESA2_DB_PASSWORD=12345
EMPRESA2_DB_DRIVER=sqlsrv

# EMPRESA 3 - Sisco
EMPRESA3_CODE=sisco
EMPRESA3_NAME=Sisco
EMPRESA3_DB_HOST=localhost
EMPRESA3_DB_NAME=sisco_db
EMPRESA3_DB_USER=root
EMPRESA3_DB_PASSWORD=
EMPRESA3_DB_DRIVER=mysql
```

### 2. Configuración Automática (config.php)

El sistema carga automáticamente las empresas desde las variables de entorno y las hace disponibles globalmente.

## 📖 Guía de Uso

### 🚀 Routing Recursivo Automático

El sistema ahora soporta **cualquier nivel de carpetas** tanto para controladores como modelos:

#### **URLs Soportadas:**
```
/principal                           → controllers/principal.php
/login/authenticate                  → controllers/login/login.php
/ejemplos/ejemplomultiempresa/metodo → controllers/ejemplos/ejemplomultiempresa.php
/a/b/c/controlador/metodo           → controllers/a/b/c/controlador.php
/nivel1/nivel2/.../controlador/accion → controllers/nivel1/nivel2/.../controlador.php
```

#### **Algoritmo de Búsqueda:**
1. **Análisis progresivo:** `/a/b/c/controlador/metodo`
2. **Prueba rutas:** 
   - `controllers/a.php` (❌)
   - `controllers/a/b.php` (❌)
   - `controllers/a/b/c.php` (❌)
   - `controllers/a/b/c/controlador.php` (✅)
3. **Resultado:** Controlador=`controlador`, Método=`metodo`

### 🎯 Patrón Básico para Controladores

```php
class MiControlador extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'mi/carpeta/anidada'; // Opcional para modelos
        $this->loadModel('mi_modelo'); // Busca recursivamente
    }

    public function miMetodo()
    {
        // ✅ UNA SOLA LÍNEA: Autenticar + Configurar empresa
        $jwtData = $this->authenticateAndConfigureModel(2); // POST
        if (!$jwtData) return;

        // ✅ El modelo ya está configurado con la empresa del JWT
        $result = $this->model->miConsulta();
        $this->jsonResponse($result, 200);
    }
}
```

### 🎯 Patrón Básico para Modelos

```php
class MiControlador extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'mi_carpeta';
        $this->loadModel('mi_modelo');
    }

    public function miMetodo()
    {
        // ✅ UNA SOLA LÍNEA: Autenticar + Configurar empresa
        $jwtData = $this->authenticateAndConfigureModel(2); // POST
        if (!$jwtData) return;

        // ✅ El modelo ya está configurado con la empresa del JWT
        $result = $this->model->miConsulta();
        $this->jsonResponse($result, 200);
    }
}
```

### 🎯 Patrón Básico para Modelos

```php
class MiModel extends Model
{
    protected $table = 'mi_tabla';
    
    public function __construct($empresaCode = null)
    {
        parent::__construct($empresaCode);
    }

    public function miConsulta()
    {
        // ✅ Consulta en empresa actual
        return $this->query("SELECT * FROM mi_tabla");
    }

    public function consultaEnOtraEmpresa($empresa)
    {
        // ✅ Consulta cruzada sin afectar conexión actual
        return $this->queryInEmpresa($empresa, "SELECT * FROM mi_tabla");
    }
}
```

## 🔧 Creación de Estructura Recursiva

### 📁 **Crear Controlador en Subcarpetas Profundas**

#### Ejemplo: `/empresas/admin/usuarios/gestionar`

1. **Crear archivo:** `controllers/empresas/admin/usuarios.php`
2. **Código del controlador:**
```php
<?php
class Usuarios extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'empresas/admin'; // Para modelos
        $this->loadModel('usuario'); // Busca recursivamente
    }

    public function gestionar()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;
        
        $result = $this->model->obtenerUsuarios();
        $this->jsonResponse($result, 200);
    }
}
```

3. **URL de acceso:** `POST /empresas/admin/usuarios/gestionar`

### 📊 **Crear Modelo en Subcarpetas Profundas**

1. **Crear archivo:** `models/empresas/admin/usuariomodel.php`
2. **El sistema lo encuentra automáticamente** sin configuración adicional

### 🎯 **Beneficios del Sistema Recursivo**

- 🚀 **Flexibilidad total:** Cualquier estructura de carpetas
- 🔧 **Zero configuración:** Funciona automáticamente  
- 🔄 **Compatibilidad:** No rompe código existente
- 📈 **Escalabilidad:** Organiza proyectos grandes fácilmente
- 🎯 **Intuitivo:** URL refleja estructura de carpetas

## 🔧 Métodos Helper Disponibles

### Controlador Base (Controller)

| Método | Descripción | Parámetros |
|--------|-------------|------------|
| `authenticateAndConfigureModel($method)` | Autenticar JWT + configurar empresa | 0=cualquiera, 1=GET, 2=POST, 3=PUT, 4=DELETE |
| `configureModelWithJWT()` | Solo configurar modelo sin validar método | - |
| `getCurrentEmpresa()` | Obtener empresa actual del JWT | - |
| `getModelForEmpresa($model, $empresa)` | Crear modelo temporal para otra empresa | nombre_modelo, codigo_empresa |

### Modelo Base (Model)

| Método | Descripción | Parámetros |
|--------|-------------|------------|
| `query($sql, $params)` | Consulta en empresa actual | sql, parámetros |
| `queryInEmpresa($empresa, $sql, $params)` | Consulta en otra empresa | empresa, sql, parámetros |
| `queryMultipleEmpresas($queries)` | Consultas en múltiples empresas | array de consultas |
| `setEmpresa($empresaCode)` | Cambiar empresa del modelo | codigo_empresa |

## 🌐 Ejemplos de Endpoints

### 1. Consulta Básica
```http
POST /ejemplomultiempresa/consultaBasica
Authorization: Bearer <jwt_token>
Content-Type: application/json

{}
```

### 2. Consulta Cruzada
```http
POST /ejemplomultiempresa/consultaCruzada
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
    "empresa_destino": "pruebas_cartimex"
}
```

### 3. Consulta Múltiple
```http
POST /ejemplomultiempresa/consultaMultiple
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
    "empresas": ["pruebas_cartimex", "pruebas_computron", "sisco"]
}
```

## 🔐 Autenticación JWT

### Estructura del Token

```json
{
    "sub": "123",
    "username": "jralvarado",
    "empresa": "pruebas_computron",
    "iat": 1234567890,
    "exp": 1234567890
}
```

### Login

```http
POST /login/authenticate
Content-Type: application/json

{
    "username": "jralvarado",
    "password": "jorge123",
    "empresa_code": "pruebas_computron"
}
```

## 📝 Archivos de Plantilla

### 📚 Controlador de Ejemplo
- **Archivo:** `controllers/ejemplos/ejemplomultiempresa.php`
- **Contiene:** 6 métodos de ejemplo con documentación completa
- **Funciones:** Consultas básicas, cruzadas, múltiples, comparaciones

### 📚 Modelo de Ejemplo
- **Archivo:** `models/ejemplos/ejemplomodel.php`
- **Contiene:** Métodos CRUD con multiempresa
- **Funciones:** Consultas, validaciones, operaciones cruzadas

## 🚦 Flujo de Trabajo

1. **Usuario hace login** con empresa específica
2. **Sistema genera JWT** con código de empresa
3. **Cliente envía requests** con JWT en header
4. **Controlador autentica** y configura modelo automáticamente
5. **Modelo ejecuta consultas** en la base de datos correcta
6. **Sistema retorna datos** de la empresa correspondiente

## 🔧 Personalización

### Agregar Nueva Empresa

1. **Agregar variables en .env:**
```env
EMPRESA4_CODE=nueva_empresa
EMPRESA4_NAME=Nueva Empresa
EMPRESA4_DB_HOST=localhost
EMPRESA4_DB_NAME=nueva_db
EMPRESA4_DB_USER=user
EMPRESA4_DB_PASSWORD=pass
EMPRESA4_DB_DRIVER=mysql
```

2. **Reiniciar aplicación** para cargar nueva configuración

### Crear Nuevo Controlador

1. **Copiar plantilla:** `ejemplomultiempresa.php`
2. **Renombrar clase** y métodos
3. **Ajustar lógica** de negocio específica
4. **Mantener patrón** de autenticación

### Crear Nuevo Modelo

1. **Copiar plantilla:** `ejemplomodel.php`
2. **Configurar propiedades:** `$table`, `$primaryKey`, etc.
3. **Implementar métodos** específicos
4. **Usar métodos heredados** para consultas cruzadas

## 🐛 Debugging

### Verificar Configuración
```php
// Ver empresas cargadas
print_r($GLOBALS['EMPRESAS']);

// Ver empresa actual del JWT
$empresa = $this->getCurrentEmpresa();

// Probar conexión
$db = Database::getInstance('pruebas_cartimex', $GLOBALS['EMPRESAS']);
```

### Logs de Error
Los errores se registran automáticamente con información de empresa:
```
[2025-10-03 10:30:00] Model Error (EjemploModel): Error en consulta para empresa: pruebas_computron
```

## 🎯 Mejores Prácticas

1. **Siempre usar `authenticateAndConfigureModel()`** en controladores
2. **No cambiar empresa manualmente** salvo casos específicos
3. **Usar métodos heredados** del modelo base
4. **Validar parámetros** antes de consultas cruzadas
5. **Mantener consistencia** en respuestas JSON
6. **Documentar métodos** personalizados
7. **Usar try-catch** para manejo de errores

## 📞 Soporte

Para dudas o problemas con el sistema multiempresa, revisar:
- 📚 Plantillas de ejemplo
- 📖 Documentación en código
- 🔧 Archivos de configuración
- 🐛 Logs de error

---

**¡Sistema Multiempresa listo para producción! 🚀**
