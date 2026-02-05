<?php

// require_once __DIR__ . '/../logsmodel.php';


class SubirDocumentosModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);

        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("SubirDocumentosModel conectado a: " . $this->empresaCode);
        }
    }

    function GetOrdenesPorSubirDocumentos($data)
    {
        try {
            $sql = "SGO_RECEPCION_COMPRAS_ORDENES_DOCUMENTOS @estado = :estado";
            $params = [
                'estado' => $data['estado'],
            ];
            $query = $this->db->query($sql, $params);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }
    function GetOrdenesPorSubirDocumentosDetalles($data)
    {
        try {
            $sql = "SGO_RECEPCION_COMPRAS_ORDENES_DETALLE @ORDEN_ID = :ORDEN_ID";
            $params = [
                'ORDEN_ID' => $data['ORDEN_ID'],
            ];
            $query = $this->db->query($sql, $params);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }


    function SaveDocumentoOrdenCompra($data)
    {
        try {
            $sql = "UPDATE COM_ORDENES SET
                    archivo = :archivo,
                    archivo_comentario = :archivo_comentario,
                    archivo_fecha = GETDATE(),
                    archivo_por = :archivo_por,
                    --PAGO_PREVIO = :PAGO_PREVIO,
                    archivo_resubido = case 
                        WHEN isnull(archivo,'') = ''
                        THEN 0
                        ELSE 1
                        END
                    WHERE ID = :ID";
            $params = [
                'archivo' => $data['nombre'],
                'archivo_comentario' => $data['comentario'],
                'archivo_por' => $data['userdata']["usrid"],
                'ID' => $data["data"]['ID'],
            ];
            $query = $this->db->execute($sql, $params);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }
}
