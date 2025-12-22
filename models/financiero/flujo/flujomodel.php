<?php

// require_once __DIR__ . '/../logsmodel.php';


class flujomodel extends Model
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





    function cargar_girados_no_cobrados($data = [])
    {
        try {
            $sql = "EXEC CARTIMEX..SGO_BAN_Informe_ChequesGirados_NoCobrados @empresa = :empresa";

            $params = [
                ':empresa' => $data['empresa'] ?? null
            ];

            // Use query() for stored procedures that return data
            $result = $this->db->query($sql, $params);

            return $result;

        } catch (Exception $e) {
            $this->logError("Error en cargar_girados_no_cobrados: " . $e->getMessage());
            error_log("Exception in cargar_girados_no_cobrados: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



     function cheques_pos_fechados($data = [])
    {
        try {
            $sql = "EXEC CARTIMEX..SGO_BAN_Informe_ChequesPosfechado";

            // $params = [
            //     ':empresa' => $data['empresa'] ?? null
            // ];

            // Use query() for stored procedures that return data
            $result = $this->db->query($sql);

            return $result;

        } catch (Exception $e) {
            $this->logError("Error en cheques_pos_fechados: " . $e->getMessage());
            error_log("Exception in cheques_pos_fechados: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
