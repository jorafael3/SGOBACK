<?php

// require_once __DIR__ . '/../logsmodel.php';


class GuiasPickupModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);

        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("GuiasPickupModel conectado a: " . $this->empresaCode);
        }
    }


    function getFacturasGuiasPickup($data = [])
    {
        try {
            $sql = "EXECUTE SGO_LOG_GUIAS_PIKUP_FACTURAS @usuario = :usuario";

            $params = [
                ":usuario" => $data['usrid']
            ];

            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo facturas guías pickup: " . $e->getMessage());
            return false;
        }
    }

    function getTransporteGuiasPickup()
    {
        try {
            $sql = "select id, Código as Codigo  from SIS_PARAMETROS where PadreID='0000000090' order by 2";

            $stmt = $this->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo transporte guías pickup: " . $e->getMessage());
            return false;
        }
    }

    function ActualizarFacturasListas($datos)
    {
        try {
            $sql = "UPDATE FACTURASLISTAS 
                SET 
                    Estado='DESPACHADA',
                    id_unico = :id_unico,
                    GUIA = :GUIA,
                    FECHAGUIA = GETDATE(),
                    GUIAPOR = :usrid
                WHERE Factura = :factura_id
                AND bodegaID = :bodega_id
                ";

            $params = [
                ":id_unico" => $datos['id_unico'],
                ":factura_id" => $datos['factura'],
                ":bodega_id" => $datos['bodega'],
                ":GUIA" => $datos['numeroGuia'],
                ":usrid" => $datos['usrid']
            ];

            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->logError("Error actualizando facturas listas: " . $e->getMessage());
            return false;
        }
    }

    function ActualizarFacturasListasParaConsolidacion($datos)
    {
        try {
            $sql = "UPDATE FACTURASLISTAS 
                SET 
                    Estado='CONSOLIDADA',
                    id_unico = :id_unico
                WHERE Factura = :factura_id
                AND bodegaID = :bodega_id
                ";

            $params = [
                ":id_unico" => $datos['id_unico'],
                ":factura_id" => $datos['factura'],
                ":bodega_id" => $datos['bodega']
            ];

            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->logError("Error actualizando facturas listas: " . $e->getMessage());
            return false;
        }
    }


    function GuardarListaGuias($datos)
    {
        try {
            $sql = "INSERT INTO SGO_LOG_GUIASPICKUP_GUIAS
            (
                factura_id,
                bodega,
                guia,
                creado_por,
                id_unico
            ) VALUES (
                :factura_id,
                :bodega,
                :guia,
                :creado_por,
                :id_unico
            )";

            $params = [
                ":factura_id" => $datos['factura'],
                ":bodega" => $datos['bodegaInfo'][0],
                ":id_unico" => $datos['id_unico'],
                ":guia" => $datos['numeroGuia'],
                ":creado_por" => $datos['usrid']
            ];

            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->logError("Error guardando lista de guías: " . $e->getMessage());
            return false;
        }
    }

    function GuardarCambioTipoPedido($datos)
    {
        try {
            $sql = "INSERT INTO SGO_FACTURASLISTAS_LOG_TIPOPEDIDOSFACTURA
            (
                factura_id,
                tipo,
                creado_por
            ) VALUES (
                :factura_id,
                :tipo,
                :creado_por
            )";

            $params = [
                ":factura_id" => $datos['factura'],
                ":tipo" => $datos['tipo'],
                ":creado_por" => $datos['usrid']
            ];

            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->logError("Error guardando lista de guías: " . $e->getMessage());
            return false;
        }
    }
}
