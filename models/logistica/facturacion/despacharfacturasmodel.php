<?php

// require_once __DIR__ . '/../logsmodel.php';


class DespacharFacturasModel extends Model
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


    function getFacturasPorDespachar($data = [])
    {
        try {
            $sql = "EXECUTE SGO_LOG_GUIAS_PIKUP_FACTURAS_2 @usuario = :usuario";

            $params = [
                ":usuario" => $data['usrid']
            ];
            $result = $this->query($sql, $params);
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error en la consulta: ' . $e->getMessage()
            ];
        }
    }
}
