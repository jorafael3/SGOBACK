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
    private static $instance = null;
    private $connection = null;

    private $host;
    private $database;
    private $username;
    private $password;
    private $charset;
    private $driver;

    public function __construct()
    {
        $this->host = constant('DB_HOST');
        $this->database = constant('DB_NAME');
        $this->username = constant('DB_USER');
        $this->password = constant('DB_PASSWORD');
        $this->charset = constant('DB_CHARSET');
        $this->driver = constant('DB_DRIVER');
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function connect()
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        try {
            $dsn = $this->buildDsn();
            $options = $this->getPdoOptions();

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);

            $this->logInfo("Conexión a base de datos establecida exitosamente");
            return $this->connection;
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
            return null;
        }
    }

    private function buildDsn()
    {
        switch ($this->driver) {
            case 'mysql':
                return "mysql:host={$this->host}:3306;dbname={$this->database};charset={$this->charset}";
            case 'pgsql':
                return "pgsql:host={$this->host};dbname={$this->database}";
            case 'sqlsrv':
                return "sqlsrv:Server={$this->host};Database={$this->database}";
            case 'sqlite':
                return "sqlite:{$this->database}";
            default:
                throw new Exception("Driver de base de datos no soportado: {$this->driver}");
        }
    }

    private function getPdoOptions()
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ];

        switch ($this->driver) {
            case 'mysql':
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$this->charset}";
                break;
            case 'sqlsrv':
                $options[PDO::ATTR_AUTOCOMMIT] = true;
                break;
        }

        return $options;
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
