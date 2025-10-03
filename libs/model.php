<?php

// =====================================================
// ARCHIVO: libs/model.php
// =====================================================
/**
 * Clase Model base
 * Proporciona funcionalidad común para todos los modelos
 */
class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $empresaCode;
    protected $empresasConfig;

    public function __construct($empresaCode = null)
    {
        // Obtener configuración de empresas desde config.php
        $this->empresasConfig = $GLOBALS['EMPRESAS'] ?? [];
        
        // Si no se especifica empresa, usar la por defecto
        $this->empresaCode = $empresaCode ?? DEFAULT_EMPRESA;
        
        // Validar que la empresa existe en la configuración
        if (empty($this->empresasConfig) || !isset($this->empresasConfig[$this->empresaCode])) {
            throw new Exception("No se encontró configuración para la empresa: " . $this->empresaCode);
        }
        
        // Obtener instancia de base de datos para la empresa específica
        $this->db = Database::getInstance($this->empresaCode, $this->empresasConfig);
    }
    
    /**
     * Establece la empresa para este modelo
     */
    public function setEmpresa($empresaCode)
    {
        // Validar que la empresa existe en la configuración
        if (!isset($this->empresasConfig[$empresaCode])) {
            throw new Exception("No se encontró configuración para la empresa: " . $empresaCode);
        }
        
        $this->empresaCode = $empresaCode;
        $this->db = Database::getInstance($this->empresaCode, $this->empresasConfig);
        return $this;
    }

    public function getAll($columns = ['*'], $orderBy = null, $limit = null)
    {
        try {
            $sql = "SELECT " . implode(', ', $columns) . " FROM {$this->table}";

            if ($orderBy) $sql .= " ORDER BY {$orderBy}";
            if ($limit) $sql .= " LIMIT {$limit}";

            $result = $this->db->query($sql);
            return $result && $result['success'] ? $result['data'] : false;
        } catch (Exception $e) {
            $this->logError("Error en getAll: " . $e->getMessage());
            return false;
        }
    }

    public function getById($id, $columns = ['*'])
    {
        try {
            $sql = "SELECT " . implode(', ', $columns) . " FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $result = $this->db->query($sql, [':id' => $id]);
            return $result && $result['success'] && !empty($result['data']) ? $result['data'][0] : false;
        } catch (Exception $e) {
            $this->logError("Error en getById: " . $e->getMessage());
            return false;
        }
    }

    public function where($conditions, $columns = ['*'], $operator = 'AND')
    {
        try {
            $sql = "SELECT " . implode(', ', $columns) . " FROM {$this->table}";
            $params = [];

            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $column => $value) {
                    $whereClause[] = "{$column} = :param_{$column}";
                    $params[":param_{$column}"] = $value;
                }
                $sql .= " WHERE " . implode(" {$operator} ", $whereClause);
            }

            $result = $this->db->query($sql, $params);
            return $result && $result['success'] ? $result['data'] : false;
        } catch (Exception $e) {
            $this->logError("Error en where: " . $e->getMessage());
            return false;
        }
    }

    public function create($data)
    {
        try {
            $data = $this->filterFillable($data);
            if (empty($data)) return false;

            $columns = array_keys($data);
            $placeholders = array_map(function ($col) {
                return ":$col";
            }, $columns);

            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

            $params = [];
            foreach ($data as $key => $value) {
                $params[":$key"] = $value;
            }

            $result = $this->db->query($sql, $params);
            return $result && $result['success'] ? $this->db->lastInsertId() : false;
        } catch (Exception $e) {
            $this->logError("Error en create: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data)
    {
        try {
            $data = $this->filterFillable($data);
            if (empty($data)) return false;

            $setClause = [];
            $params = [":id" => $id];

            foreach ($data as $column => $value) {
                $setClause[] = "{$column} = :param_{$column}";
                $params[":param_{$column}"] = $value;
            }

            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$this->primaryKey} = :id";

            $result = $this->db->query($sql, $params);
            return $result && $result['success'];
        } catch (Exception $e) {
            $this->logError("Error en update: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $result = $this->db->query($sql, [':id' => $id]);
            return $result && $result['success'];
        } catch (Exception $e) {
            $this->logError("Error en delete: " . $e->getMessage());
            return false;
        }
    }

    public function count($conditions = [])
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            $params = [];

            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $column => $value) {
                    $whereClause[] = "{$column} = :param_{$column}";
                    $params[":param_{$column}"] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }

            $result = $this->db->query($sql, $params);
            if ($result && $result['success'] && !empty($result['data'])) {
                return (int)$result['data'][0]['total'];
            }
            return false;
        } catch (Exception $e) {
            $this->logError("Error en count: " . $e->getMessage());
            return false;
        }
    }

    protected function filterFillable($data)
    {
        return empty($this->fillable) ? $data : array_intersect_key($data, array_flip($this->fillable));
    }

    protected function validate($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = isset($data[$field]) ? $data[$field] : null;

            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field][] = "El campo {$field} es requerido";
            }

            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field][] = "El campo {$field} debe ser un email válido";
            }

            if (preg_match('/min:(\d+)/', $rule, $matches) && strlen($value) < $matches[1]) {
                $errors[$field][] = "El campo {$field} debe tener al menos {$matches[1]} caracteres";
            }
        }

        return $errors;
    }

    protected function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    protected function logError($message)
    {
        if (DEBUG) {
            error_log("[" . date('Y-m-d H:i:s') . "] Model Error (" . get_class($this) . "): $message");
        }
    }

    /**
     * Ejecuta una consulta usando la clase Database
     */
    protected function query($sql, $params = [])
    {
        return $this->db->query($sql, $params);
    }
    
    /**
     * Ejecuta una consulta en una empresa específica sin cambiar la conexión actual
     * @param string $empresaCode Código de la empresa
     * @param string $sql Consulta SQL
     * @param array $params Parámetros de la consulta
     * @return array|false Resultado de la consulta
     */
    protected function queryInEmpresa($empresaCode, $sql, $params = [])
    {
        try {
            // Validar que la empresa existe
            if (!isset($this->empresasConfig[$empresaCode])) {
                throw new Exception("No se encontró configuración para la empresa: " . $empresaCode);
            }
            
            // Obtener instancia temporal de base de datos para la otra empresa
            $tempDb = Database::getInstance($empresaCode, $this->empresasConfig);
            
            // Ejecutar consulta en la otra empresa
            return $tempDb->query($sql, $params);
            
        } catch (Exception $e) {
            $this->logError("Error en consulta cross-empresa: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ejecuta múltiples consultas en diferentes empresas
     * @param array $queries Array con formato: ['empresa_code' => ['sql' => '...', 'params' => [...]]]
     * @return array Resultados organizados por empresa
     */
    protected function queryMultipleEmpresas($queries)
    {
        $results = [];
        
        foreach ($queries as $empresaCode => $queryData) {
            $sql = $queryData['sql'];
            $params = $queryData['params'] ?? [];
            
            $results[$empresaCode] = $this->queryInEmpresa($empresaCode, $sql, $params);
        }
        
        return $results;
    }
}
