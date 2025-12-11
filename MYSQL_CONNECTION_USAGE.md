# Uso de Conexión MySQL

Se agregó una clase `MySQLConnection` que permite conectar a una base de datos MySQL específica de forma sencilla.

## Configuración

La conexión está configurada con estos datos (hardcodeados):
- **Host**: 10.5.1.245
- **Database**: sisco
- **Usuario**: root
- **Password**: Bruno2001
- **Puerto**: 3306

## Uso en Controllers

```php
<?php
require_once __DIR__ . '/../../libs/database.php';

class MiController extends Controller
{
    public function obtener_datos()
    {
        // Obtener instancia de MySQLConnection
        $mysqlDb = MySQLConnection::getInstance();
        
        // Ejecutar query
        $resultado = $mysqlDb->query("SELECT * FROM mi_tabla WHERE id = ?", [1]);
        
        if ($resultado['success']) {
            $this->jsonResponse($resultado['data'], 200);
        } else {
            $this->jsonResponse(['error' => $resultado['error']], 500);
        }
    }

    public function insertar_datos()
    {
        $mysqlDb = MySQLConnection::getInstance();
        
        $resultado = $mysqlDb->execute(
            "INSERT INTO mi_tabla (nombre, email) VALUES (?, ?)",
            ["Juan", "juan@email.com"]
        );
        
        $this->jsonResponse($resultado, 200);
    }

    public function actualizar_datos()
    {
        $mysqlDb = MySQLConnection::getInstance();
        
        $resultado = $mysqlDb->execute(
            "UPDATE mi_tabla SET nombre = ? WHERE id = ?",
            ["Pedro", 1]
        );
        
        $this->jsonResponse($resultado, 200);
    }

    public function eliminar_datos()
    {
        $mysqlDb = MySQLConnection::getInstance();
        
        $resultado = $mysqlDb->execute(
            "DELETE FROM mi_tabla WHERE id = ?",
            [1]
        );
        
        $this->jsonResponse($resultado, 200);
    }
}
```

## Uso en Models

```php
<?php
class MiModel extends Model
{
    public function obtener_usuarios()
    {
        $mysqlDb = MySQLConnection::getInstance();
        
        $resultado = $mysqlDb->query(
            "SELECT id, nombre, email FROM usuarios WHERE estado = ?",
            [1]
        );
        
        return $resultado;
    }

    public function guardar_usuario($datos)
    {
        $mysqlDb = MySQLConnection::getInstance();
        
        $resultado = $mysqlDb->execute(
            "INSERT INTO usuarios (nombre, email, estado) VALUES (?, ?, ?)",
            [$datos['nombre'], $datos['email'], 1]
        );
        
        return $resultado;
    }
}
```

## Transacciones

```php
$mysqlDb = MySQLConnection::getInstance();

try {
    // Inicia transacción
    $mysqlDb->beginTransaction();
    
    // Operación 1
    $resultado1 = $mysqlDb->execute(
        "INSERT INTO usuarios (nombre) VALUES (?)",
        ["Juan"]
    );
    
    if (!$resultado1['success']) {
        throw new Exception("Error en inserción");
    }
    
    // Operación 2
    $resultado2 = $mysqlDb->execute(
        "UPDATE usuarios SET activo = ? WHERE nombre = ?",
        [1, "Juan"]
    );
    
    if (!$resultado2['success']) {
        throw new Exception("Error en actualización");
    }
    
    // Confirmar cambios
    $mysqlDb->commit();
    echo "Transacción completada";
    
} catch (Exception $e) {
    // Revertir cambios si hay error
    $mysqlDb->rollback();
    echo "Error: " . $e->getMessage();
}
```

## Métodos Disponibles

- `MySQLConnection::getInstance()` - Obtiene la instancia (Singleton)
- `query($sql, $params)` - Ejecuta SELECT y retorna datos
- `execute($sql, $params)` - Ejecuta INSERT, UPDATE, DELETE
- `getConnection()` - Obtiene la conexión PDO directa
- `lastInsertId()` - Obtiene el último ID insertado
- `beginTransaction()` - Inicia transacción
- `commit()` - Confirma cambios
- `rollback()` - Revierte cambios

## Notas Importantes

- La conexión es un **Singleton**, solo se crea una vez
- Los parámetros deben ir en un array para evitar SQL Injection
- Los resultados retornan arrays con estructura:
  - `success` (bool): Si la operación fue exitosa
  - `querymessage` (string): Mensaje descriptivo
  - `data` (array): Datos retornados (solo en query)
  - `affected_rows` (int): Filas afectadas (solo en execute)
  - `error` (string): Mensaje de error si falla
