<?php

// require_once __DIR__ . '/../logsmodel.php';


class ParametrosModel extends Model
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

    function Cargar_OPT_Param()
    {
        try {
            $sql = "SELECT * FROM SGO_FINANCIERO_OPT_PARAM";
            $query = $this->db->query($sql, []);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Guardar_OPT_Param($data){
        try {
            $fechaVencimiento = isset($data['fechaVencimientoCalcInt']) ? $data['fechaVencimientoCalcInt'] : null;
            $param = [
                    ':fechaVencimientoCalcInt' => $fechaVencimiento,
                ];
            $sql = "UPDATE SGO_FINANCIERO_OPT_PARAM
                    SET fechaVencimientoCalcInt = :fechaVencimientoCalcInt
                    WHERE id = 1";
            $this->db->query($sql,  $param);
            return ['success' => true];
        } catch (Exception $e) {
            return [];
        }
    }

}