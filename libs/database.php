<?php
//error_reporting(0);
// =====================================================
// ARCHIVO: libs/database.php  
// =====================================================
/**
 * Clase Database
 * Maneja la conexión a la base de datos usando el patrón Singleton
 */
class Database
{
    // private static $instance = null;
    // private $connection = null;

    // private $host;
    // private $database;
    // private $username;
    // private $password;
    // private $charset;
    // private $driver;

    private static $instances = [];
    private $connection;
    private $config;

    public function __construct($config)
    {
        // $this->host = constant('DB_HOST');
        // $this->database = constant('DB_NAME');
        // $this->username = constant('DB_USER');
        // $this->password = constant('DB_PASSWORD');
        // $this->charset = constant('DB_CHARSET');
        // $this->driver = constant('DB_DRIVER');

        $this->config = $config;
    }

    public static function getInstance($empresaCode, $empresasConfig)
    {
        // if (self::$instance === null) {
        //     self::$instance = new self();
        // }
        // return self::$instance;

        if (!isset($empresasConfig[$empresaCode])) {
            throw new Exception("No existe configuración para la empresa: $empresaCode");
        }

        if (!isset(self::$instances[$empresaCode])) {
            self::$instances[$empresaCode] = new self($empresasConfig[$empresaCode]);
        }

        return self::$instances[$empresaCode];
    }

    public function connect()
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        try {
            $dsn = $this->buildDsn();
            $options = $this->getPdoOptions();

            // $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
            $this->logInfo("Conexión a base de datos establecida exitosamente");
            return $this->connection;
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
            return null;
        }
    }

    private function buildDsn()
    {
        switch ($this->config['driver']) {
            case 'mysql':
                return "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
            case 'pgsql':
                return "pgsql:host={$this->config['host']};dbname={$this->config['database']}";
            case 'sqlsrv':
                return "sqlsrv:Server={$this->config['host']};Database={$this->config['database']}";
            case 'sqlite':
                return "sqlite:{$this->config['database']}";
            default:
                throw new Exception("Driver no soportado: {$this->config['driver']}");
        }
    }

    private function getPdoOptions()
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
    }

    public function query($query, $params = [])
    {
        try {
            $connection = $this->connect();
            if (!$connection) return false;

            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            if ($stmt) {
                $res = array(
                    "success" => true,
                    "querymessage" => "Consulta ejecutada correctamente",
                    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC),
                );
                $this->logInfo("Consulta ejecutada: $query");
                return $res;
            } else {
                $err = $stmt->errorInfo();
                $res = array(
                    "success" => false,
                    "querymessage" => "Error en la consulta",
                    "message" => "Ocurrio un error al ejecutar la acción, vuelva a intentarlo en un momento, si el problema persiste contacte al administrador",
                    "error" => $err,
                    "query" => $query
                );
                return $res;
            }
        } catch (PDOException $e) {
            // $this->logError("Error en consulta: " . $e->getMessage());
            // if (DEBUG) throw $e;
            $res = array(
                "success" => false,
                "querymessage" => "Error en la consulta",
                "message" => "Ocurrio un error al ejecutar la acción, vuelva a intentarlo en un momento, si el problema persiste contacte al administrador",
                "error" => $e->getMessage()
            );
            return $res;
        }
    }

    public function execute($query, $params = [])
    {
        try {
            $connection = $this->connect();
            if (!$connection) return false;

            $stmt = $connection->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                $res = array(
                    "success" => true,
                    "querymessage" => "Operación ejecutada correctamente",
                    "affected_rows" => $stmt->rowCount(),
                    "last_insert_id" => $connection->lastInsertId()
                );
                $this->logInfo("Operación ejecutada: $query");
                return $res;
            } else {
                $err = $stmt->errorInfo();
                $res = array(
                    "success" => false,
                    "querymessage" => "Error en la operación",
                    "message" => "Ocurrió un error al ejecutar la acción, vuelva a intentarlo en un momento, si el problema persiste contacte al administrador",
                    "error" => $err,
                    "query" => $query
                );
                return $res;
            }
        } catch (PDOException $e) {
            $res = array(
                "success" => false,
                "querymessage" => "Error en la operación",
                "message" => "Ocurrió un error al ejecutar la acción, vuelva a intentarlo en un momento, si el problema persiste contacte al administrador",
                "error" => $e->getMessage()
            );
            return $res;
        }
    }

    public function lastInsertId()
    {
        return $this->connection ? $this->connection->lastInsertId() : null;
    }

    public function beginTransaction()
    {
        return $this->connection ? $this->connection->beginTransaction() : false;
    }

    public function commit()
    {
        return $this->connection ? $this->connection->commit() : false;
    }

    public function rollback()
    {
        return $this->connection ? $this->connection->rollback() : false;
    }

    /**
     * Fuerza una reconexión cerrando la conexión actual
     */
    public function forceReconnect()
    {
        $this->connection = null;
        return $this->connect();
    }

    /**
     * Limpia el cache de instancias de una empresa específica
     */
    public static function clearInstanceCache($empresaCode = null)
    {
        if ($empresaCode) {
            if (isset(self::$instances[$empresaCode])) {
                self::$instances[$empresaCode]->connection = null;
                unset(self::$instances[$empresaCode]);
            }
        } else {
            // Limpiar todas las instancias
            foreach (self::$instances as $instance) {
                $instance->connection = null;
            }
            self::$instances = [];
        }
    }

    /**
     * Obtiene información de debug sobre las conexiones activas
     */
    public static function getActiveConnections()
    {
        $connections = [];
        foreach (self::$instances as $empresaCode => $instance) {
            $connections[$empresaCode] = [
                'connected' => $instance->connection !== null,
                'config' => [
                    'host' => $instance->config['host'] ?? 'N/A',
                    'database' => $instance->config['database'] ?? 'N/A'
                ]
            ];
        }
        return $connections;
    }

    private function handleConnectionError(PDOException $e)
    {
        $this->logError("Error de conexión: " . $e->getMessage());

        if (DEBUG) {
            die("Database Connection Error: " . $e->getMessage());
        }

        http_response_code(500);
        include_once 'views/errores/500.php';
        exit;
    }

    private function logError($message)
    {
        if (DEBUG) {
            error_log("[" . date('Y-m-d H:i:s') . "] Database Error: $message");
        }
    }

    private function logInfo($message)
    {
        if (DEBUG) {
            error_log("[" . date('Y-m-d H:i:s') . "] Database Info: $message");
        }
    }

    private function __clone() {}
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Clase para conexión específica a MySQL
 * Uso: $mysqlDb = new MySQLConnection();
 */
class MySQLConnection
{
    private static $instance = null;
    private $connection;
    
    // Configuración hardcodeada para MySQL
    private $host = "tcp:10.5.1.245";
    private $database = "sisco";
    private $username = "root";
    private $password = "Bruno2001";
    private $port = 3306;
    private $charset = "utf8mb4";

    private function __construct()
    {
        $this->connect();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect()
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        try {
            // Limpiar el host si viene con "tcp:"
            $hostClean = str_replace("tcp:", "", $this->host);
            
            $dsn = "mysql:host={$hostClean};port={$this->port};dbname={$this->database};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            error_log("[" . date('Y-m-d H:i:s') . "] Conexión MySQL establecida exitosamente a {$this->database}");
            return $this->connection;
        } catch (PDOException $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Error de conexión MySQL: " . $e->getMessage());
            throw new Exception("Error al conectar a MySQL: " . $e->getMessage());
        }
    }

    public function query($query, $params = [])
    {
        try {
            $connection = $this->connect();
            if (!$connection) return false;

            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            
            if ($stmt) {
                $res = array(
                    "success" => true,
                    "querymessage" => "Consulta ejecutada correctamente",
                    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC),
                );
                error_log("[" . date('Y-m-d H:i:s') . "] MySQL Query ejecutada: $query");
                return $res;
            } else {
                $err = $stmt->errorInfo();
                $res = array(
                    "success" => false,
                    "querymessage" => "Error en la consulta",
                    "message" => "Error al ejecutar la consulta",
                    "error" => $err,
                    "query" => $query
                );
                return $res;
            }
        } catch (PDOException $e) {
            $res = array(
                "success" => false,
                "querymessage" => "Error en la consulta",
                "message" => "Error al ejecutar la consulta",
                "error" => $e->getMessage()
            );
            error_log("[" . date('Y-m-d H:i:s') . "] MySQL Error: " . $e->getMessage());
            return $res;
        }
    }

    public function execute($query, $params = [])
    {
        try {
            $connection = $this->connect();
            if (!$connection) return false;

            $stmt = $connection->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                $res = array(
                    "success" => true,
                    "querymessage" => "Operación ejecutada correctamente",
                    "affected_rows" => $stmt->rowCount(),
                    "last_insert_id" => $connection->lastInsertId()
                );
                error_log("[" . date('Y-m-d H:i:s') . "] MySQL Execute: $query");
                return $res;
            } else {
                $err = $stmt->errorInfo();
                $res = array(
                    "success" => false,
                    "querymessage" => "Error en la operación",
                    "message" => "Error al ejecutar la operación",
                    "error" => $err,
                    "query" => $query
                );
                return $res;
            }
        } catch (PDOException $e) {
            $res = array(
                "success" => false,
                "querymessage" => "Error en la operación",
                "message" => "Error al ejecutar la operación",
                "error" => $e->getMessage()
            );
            error_log("[" . date('Y-m-d H:i:s') . "] MySQL Execute Error: " . $e->getMessage());
            return $res;
        }
    }

    public function getConnection()
    {
        return $this->connect();
    }

    public function lastInsertId()
    {
        return $this->connection ? $this->connection->lastInsertId() : null;
    }

    public function beginTransaction()
    {
        return $this->connection ? $this->connection->beginTransaction() : false;
    }

    public function commit()
    {
        return $this->connection ? $this->connection->commit() : false;
    }

    public function rollback()
    {
        return $this->connection ? $this->connection->rollback() : false;
    }

    private function __clone() {}
    
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize MySQLConnection");
    }
}
