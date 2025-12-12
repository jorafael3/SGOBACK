<?php

// require_once __DIR__ . '/../logsmodel.php';


class PrepararFacturasModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
        
        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("PrepararFacturasModel conectado a: " . $this->empresaCode);
        }
    }

    function getFacturasPorPreparar($data = [])
    {
        try {
        

            $sql = "EXECUTE SGO_LOG_PREPARAR_FACTURAS @usuario = :usuario";

            $params = [
                ":usuario" => $data['usrid']
            ];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getFacturasCab($secuencia)
    {
        try {
            $sql = "EXECUTE LOG_BUSQUEDA_FACTURA @secuencia = :secuencia";

            $params = [
                ":secuencia" => $secuencia
            ];
            
            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getFacturasDetalle($factura_id, $bodega)
    {
        try {
            $sql = "EXECUTE SGO_LOG_FACTURAS_DETALLE @factura_id = :factura_id, @bodega = :bodega";

            $params = [
                ":factura_id" => $factura_id,
                ":bodega" => $bodega
            ];
            
            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getFacturasDetalleUbicacion($factura_id, $bodega)
    {
        try {
            $sql = "EXECUTE LOG_BUSCAR_MEJOR_UBICACION @ProductoId = :ProductoId, @bodega = :bodega";

            $params = [
                ":ProductoId" => $factura_id,
                ":bodega" => $bodega
            ];
            
            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

      function ValidarPreparacion($data)
    {
        try {
            $sql = "SELECT * FROM FACTURASLISTAS WHERE Factura = :factura_id AND bodegaid=:bodega_id";

            $params = [
                ":factura_id" => $data['factura'],
                ":bodega_id" => $data['bodega_id']
            ];
            
            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function FinalizarPreparacion($data = [])
    {
        try {
            $sql = "SGO_LOG_PREPARAR_FACTURAS_LISTAS  
                @factura_id=:factura_id , 
                @usrid=:usrid,
                @bodega_id =:bodega_id, 
                @id_unico =:id_unico,
                @solo_servicio = :solo_servicio
                ";

            $params = [
                ":factura_id" => $data['factura'],
                ":usrid" => $data['usrid'],
                ":bodega_id" => $data['bodega_id'],
                ":id_unico" => $data['id_unico'],
                ":solo_servicio" => $data['solo_servicios'],
            ];

            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    
}
