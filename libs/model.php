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
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function getAll($columns = ['*'], $orderBy = null, $limit = null)
    {
        try {
            $sql = "SELECT " . implode(', ', $columns) . " FROM {$this->table}";
            
            if ($orderBy) $sql .= " ORDER BY {$orderBy}";
            if ($limit) $sql .= " LIMIT {$limit}";
            
            $stmt = $this->db->query($sql);
            return $stmt ? $stmt->fetchAll() : false;
            
        } catch (Exception $e) {
            $this->logError("Error en getAll: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id, $columns = ['*'])
    {
        try {
            $sql = "SELECT " . implode(', ', $columns) . " FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->query($sql, [':id' => $id]);
            return $stmt ? $stmt->fetch() : false;
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
            
            $stmt = $this->db->query($sql, $params);
            return $stmt ? $stmt->fetchAll() : false;
            
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
            $placeholders = array_map(function($col) { return ":$col"; }, $columns);
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $params = [];
            foreach ($data as $key => $value) {
                $params[":$key"] = $value;
            }
            
            $stmt = $this->db->query($sql, $params);
            return $stmt ? $this->db->lastInsertId() : false;
            
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
            
            $stmt = $this->db->query($sql, $params);
            return $stmt !== false;
            
        } catch (Exception $e) {
            $this->logError("Error en update: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->query($sql, [':id' => $id]);
            return $stmt !== false;
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
            
            $stmt = $this->db->query($sql, $params);
            $result = $stmt ? $stmt->fetch() : false;
            return $result ? (int)$result['total'] : false;
            
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
        return Database::getInstance()->query($sql, $params);
    }
}

?>