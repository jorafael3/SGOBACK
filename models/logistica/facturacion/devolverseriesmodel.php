<?php

// require_once __DIR__ . '/../logsmodel.php';


class DevolverSeriesModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);

        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("VerificarFacturasModel conectado a: " . $this->empresaCode);
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


    function getFacturaDet($FATID)
    {
        try {
            $sql = "LOG_BUSQUEDA_FACTURA_DT @FacturaID = :Id";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":Id" => $FATID
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function Validar_RMA_PRODUCTOS($datos)
    {
        try {
            $sql = "select serie from rma_productos where  Serie=:serie and FacturaID=:facturaid";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":serie" => $datos['serie'],
                ":facturaid" => $datos['factura_id']
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function Actualizar_RMAProductos($data)
    {
        try {
            $estado = 'INVENTARIO';
            $sql = "UPDATE COMPUTRONSA..RMA_PRODUCTOS 
                SET 
                    ESTADO=:estado
                    WHERE PRODUCTOID=:ProductoID 
                    and SERIE=:Serie
                    and FacturaID=:FacturaID";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":Serie" => $data['serie'],
                ":FacturaID" => $data['factura_id'],
                ":ProductoID" => $data['ProductoId'],
                ":estado" => $estado
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function Actualizar_RMADT($data)
    {
        try {
            $sql = "LOG_ELIMINAR_SERIES_RMA_FACTURA @serie=:serie , @facturaid=:facturaid";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":serie" => $data['serie'],
                ":facturaid" => $data['factura_id']
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }
}
