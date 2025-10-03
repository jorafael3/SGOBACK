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
