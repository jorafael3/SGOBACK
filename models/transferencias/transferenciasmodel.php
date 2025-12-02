<?php

class Transferenciasmodel extends Model
{
    public function __construct($empresaCode = null)
    {
        parent::__construct($empresaCode);

        if (DEBUG) {
            error_log("✅ Transferenciasmodel conectado a empresa: " . $this->empresaCode);
        }
    }

    public function gettransferenciascargar($data = [])
    {
        try {
            $sql = "SELECT TOP 10 * FROM INV_TRANSFERENCIAS ORDER BY Fecha DESC";
            $stmt = $this->query($sql);

            // Validar respuesta del método query()
            if ($stmt && isset($stmt['success']) && $stmt['success'] === true) {
                return [
                    'success' => true,
                    'message' => 'Transferencias cargadas correctamente',
                    'data' => $stmt['data']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al obtener transferencias',
                    'error' => $stmt['error'] ?? null
                ];
            }
        } catch (Exception $e) {
            $this->logError("❌ Error obteniendo transferencias: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Excepción al ejecutar consulta',
                'error' => $e->getMessage()
            ];
        }
    }


    // function getTransporteGuiasPickup()
    // {
    //     try {
    //         $sql = "select id, Código as Codigo  from SIS_PARAMETROS where PadreID='0000000090' order by 2";

    //         $stmt = $this->query($sql);
    //         return $stmt;
    //     } catch (Exception $e) {
    //         $this->logError("Error obteniendo transporte guías pickup: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // function ActualizarFacturasListas($datos)
    // {
    //     try {
    //         $sql = "UPDATE FACTURASLISTAS 
    //             SET 
    //                 Estado='DESPACHADA',
    //                 id_unico = :id_unico,
    //                 GUIA = :GUIA,
    //                 FECHAGUIA = GETDATE(),
    //                 GUIAPOR = :usrid
    //             WHERE Factura = :factura_id
    //             AND bodegaID = :bodega_id
    //             ";

    //         $params = [
    //             ":id_unico" => $datos['id_unico'],
    //             ":factura_id" => $datos['factura'],
    //             ":bodega_id" => $datos['bodega'],
    //             ":GUIA" => $datos['numeroGuia'],
    //             ":usrid" => $datos['usrid']
    //         ];

    //         $result = $this->db->execute($sql, $params);
    //         return $result;
    //     } catch (Exception $e) {
    //         $this->logError("Error actualizando facturas listas: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // function ActualizarFacturasListasParaConsolidacion($datos)
    // {
    //     try {
    //         $sql = "UPDATE FACTURASLISTAS 
    //             SET 
    //                 Estado='CONSOLIDADA',
    //                 id_unico = :id_unico
    //             WHERE Factura = :factura_id
    //             AND bodegaID = :bodega_id
    //             ";

    //         $params = [
    //             ":id_unico" => $datos['id_unico'],
    //             ":factura_id" => $datos['factura'],
    //             ":bodega_id" => $datos['bodega']
    //         ];

    //         $result = $this->db->execute($sql, $params);
    //         return $result;
    //     } catch (Exception $e) {
    //         $this->logError("Error actualizando facturas listas: " . $e->getMessage());
    //         return false;
    //     }
    // }


    // function GuardarListaGuias($datos)
    // {
    //     try {
    //         $sql = "INSERT INTO SGO_LOG_GUIASPICKUP_GUIAS
    //         (
    //             factura_id,
    //             bodega,
    //             guia,
    //             creado_por,
    //             id_unico
    //         ) VALUES (
    //             :factura_id,
    //             :bodega,
    //             :guia,
    //             :creado_por,
    //             :id_unico
    //         )";

    //         $params = [
    //             ":factura_id" => $datos['factura'],
    //             ":bodega" => $datos['bodegaInfo'][0],
    //             ":id_unico" => $datos['id_unico'],
    //             ":guia" => $datos['numeroGuia'],
    //             ":creado_por" => $datos['usrid']
    //         ];

    //         $result = $this->db->execute($sql, $params);
    //         return $result;
    //     } catch (Exception $e) {
    //         $this->logError("Error guardando lista de guías: " . $e->getMessage());
    //         return false;
    //     }
    // }

    // function GuardarCambioTipoPedido($datos)
    // {
    //     try {
    //         $sql = "INSERT INTO SGO_FACTURASLISTAS_LOG_TIPOPEDIDOSFACTURA
    //         (
    //             factura_id,
    //             tipo,
    //             creado_por
    //         ) VALUES (
    //             :factura_id,
    //             :tipo,
    //             :creado_por
    //         )";

    //         $params = [
    //             ":factura_id" => $datos['factura'],
    //             ":tipo" => $datos['tipo'],
    //             ":creado_por" => $datos['usrid']
    //         ];

    //         $result = $this->db->execute($sql, $params);
    //         return $result;
    //     } catch (Exception $e) {
    //         $this->logError("Error guardando lista de guías: " . $e->getMessage());
    //         return false;
    //     }
    // }
}
