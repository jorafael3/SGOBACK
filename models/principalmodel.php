<?php


// =====================================================
// ARCHIVO: models/principalmodel.php
// =====================================================
/**
 * Modelo Principal
 */
class PrincipalModel extends Model 
{
    protected $table = 'inv_series';
    protected $primaryKey = 'id';
    protected $fillable = ['PEDIDO', 'serie', 'estado', 'fecha_creacion'];
    protected $hidden = [];
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function cargarSeries($params)
    {
        try {
            $validationResult = $this->validateCargarSeriesParams($params);
            if ($validationResult !== true) {
                return [
                    'success' => false,
                    'message' => 'Parámetros inválidos',
                    'errors' => $validationResult
                ];
            }
            
            $pedido = $this->sanitize($params['PEDIDOS_A_BUSCAR']);
            $result = $this->where(['PEDIDO' => $pedido]);
            
            if ($result === false) {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta'
                ];
            }
            
            return [
                'success' => true,
                'data' => $result,
                'message' => 'Series cargadas correctamente',
                'pedido' => $pedido,
                'total' => count($result)
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en cargarSeries: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno al cargar las series'
            ];
        }
    }
    
    public function buscarSeries($criterios)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE 1=1";
            $params = [];
            
            if (!empty($criterios['pedido'])) {
                $sql .= " AND PEDIDO = :pedido";
                $params[':pedido'] = $this->sanitize($criterios['pedido']);
            }
            
            if (!empty($criterios['estado'])) {
                $sql .= " AND estado = :estado";
                $params[':estado'] = $this->sanitize($criterios['estado']);
            }
            
            if (!empty($criterios['fecha_desde'])) {
                $sql .= " AND fecha_creacion >= :fecha_desde";
                $params[':fecha_desde'] = $criterios['fecha_desde'];
            }
            
            if (!empty($criterios['fecha_hasta'])) {
                $sql .= " AND fecha_creacion <= :fecha_hasta";
                $params[':fecha_hasta'] = $criterios['fecha_hasta'];
            }
            
            $sql .= " ORDER BY fecha_creacion DESC";
            
            if (!empty($criterios['limit'])) {
                $limit = (int)$criterios['limit'];
                $sql .= " LIMIT $limit";
            }
            
            $stmt = $this->query($sql, $params);
            
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta de búsqueda'
                ];
            }
            
            $result = $stmt->fetchAll();
            
            return [
                'success' => true,
                'data' => $result,
                'message' => 'Búsqueda completada',
                'total' => count($result)
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en buscarSeries: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno en la búsqueda'
            ];
        }
    }
    
    public function obtenerSeriesPaginadas($page = 1, $perPage = 20, $filtros = [])
    {
        try {
            $offset = ($page - 1) * $perPage;
            
            $sql = "SELECT * FROM {$this->table} WHERE 1=1";
            $params = [];
            
            if (!empty($filtros['pedido'])) {
                $sql .= " AND PEDIDO LIKE :pedido";
                $params[':pedido'] = '%' . $filtros['pedido'] . '%';
            }
            
            if (!empty($filtros['estado'])) {
                $sql .= " AND estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }
            
            $countSql = str_replace('*', 'COUNT(*) as total', $sql);
            $countStmt = $this->query($countSql, $params);
            $totalRecords = $countStmt ? $countStmt->fetch()['total'] : 0;
            
            $sql .= " ORDER BY fecha_creacion DESC LIMIT $perPage OFFSET $offset";
            
            $stmt = $this->query($sql, $params);
            $data = $stmt ? $stmt->fetchAll() : [];
            
            return [
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalRecords,
                    'total_pages' => ceil($totalRecords / $perPage)
                ]
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en obtenerSeriesPaginadas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener series paginadas'
            ];
        }
    }
    
    private function validateCargarSeriesParams($params)
    {
        $errors = [];
        
        if (empty($params) || !is_array($params)) {
            $errors[] = 'Los parámetros deben ser un array válido';
            return $errors;
        }
        
        if (empty($params['PEDIDOS_A_BUSCAR'])) {
            $errors[] = 'El parámetro PEDIDOS_A_BUSCAR es requerido';
        } else {
            $pedido = $params['PEDIDOS_A_BUSCAR'];
            if (strlen($pedido) < 3) {
                $errors[] = 'El pedido debe tener al menos 3 caracteres';
            }
        }
        
        return empty($errors) ? true : $errors;
    }
}

