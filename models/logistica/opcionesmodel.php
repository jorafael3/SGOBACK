<?php

// require_once __DIR__ . '/../logsmodel.php';


class OpcionesModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("DespacharFacturasModel conectado a: " . $this->empresaCode);
        }
    }


    function GetSucursalesFacturacion()
    {
        try {
            $sql = "SELECT 
                s.*,
                isnull(s.tipo,'') as tipo,
                ss.Nombre as Sucursal_Nombre
                FROM SGO_LOG_SUCURSALES_FACTURACION s
                LEFT JOIN SIS_SUCURSALES ss
                ON s.Sucursal = ss.Código
                ORDER BY Sucursal";

            $params = [];
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

    function getListaSucursales()
    {
        try {
            $sql = "SELECT ID,Código as Codigo ,Nombre FROM SIS_SUCURSALES
            where Anulado = 0 
            ORDER BY Código desc";

            $params = [];
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

    function SetEditarSucursalFacturacion($data)
    {
        try {
            $sql = "UPDATE SGO_LOG_SUCURSALES_FACTURACION SET
                Sucursal = :Sucursal,
                tipo = :tipo,
                estado = :estado
                WHERE id = :id";

            $params = [
                ':Sucursal' => $data['sucursal'],
                ':tipo' => $data['tipo'],
                ':estado' => $data['estado'],
                ':id' => $data['id'],
            ];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getListaBodegas()
    {
        try {
            $sql = "SELECT  ID,Código as Codigo ,Nombre from INV_BODEGAS where Anulado = 0
				--and Disponible = 1-- and venta = 1
				order by Código";
            $params = [];
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

    function getListaBodegasDropshipping()
    {
        try {
            $sql = "SELECT
				s.*,
                isnull(s.tipo,'') as tipo,
                b.Nombre as Bodega_Nombre,
				b.Código as Bodega_Codigo
				from SGO_LOG_BODEGAS_FACTURACION s
				LEFT JOIN INV_BODEGAS b on s.bodega_id = b.ID";
            $params = [];
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

    function SetSaveBodegaFacturacion($data)
    {
        try {
            $sql = "INSERT INTO SGO_LOG_BODEGAS_FACTURACION
                (bodega_id, tipo, estado)
                VALUES
                (:bodega_id, :tipo, :estado)";

            $params = [
                ':bodega_id' => $data['bodega_id'],
                ':tipo' => $data['tipo'],
                ':estado' => $data['estado'],
            ];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function SetEditarBodegaFacturacion($data)
    {
        try {
            $sql = "UPDATE SGO_LOG_BODEGAS_FACTURACION
            SET
                tipo = :tipo,
                estado = :estado
            WHERE
                bodega_id = :bodega_id
                ";

            $params = [
                ':bodega_id' => $data['bodega_id'],
                ':tipo' => $data['tipo'],
                ':estado' => $data['estado'],
            ];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }
}
