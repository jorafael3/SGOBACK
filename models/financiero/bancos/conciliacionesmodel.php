<?php

// require_once __DIR__ . '/../logsmodel.php';


class ConciliacionesModel extends Model
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

    function ComprobarConciliaciones($param)
    {
        try {
            $cheque = $param['col5'] ?? null;
            $valor = isset($param['valor']) ? abs((float) $param['valor']) : null;
            // $fecha = $param['fecha'] ?? null;
            // $debito = $param['debito'] ?? null;
            $chequeCheck = $param['chequeCheck'] ?? 0;
            if ($chequeCheck == 1) {
                $cheque = ltrim($cheque, '0');
                // $sql = "SELECT TOP 1 ID FROM BAN_BANCOS_CARDEX with(NOLOCK) WHERE Cheque = :cheque AND Valor = :valor ORDER BY Fecha DESC";
                $sql = "SELECT TOP 1 Valor FROM BAN_BANCOS_CARDEX with(NOLOCK) WHERE Cheque = :cheque AND Valor = :valor ORDER BY Fecha DESC";
                $params = [
                    ":cheque" => $cheque,
                    ":valor" => $valor
                ];
                // $query = $this->db->query($sql, $params);
                $query = $this->db->query($sql, $params);
                return $query;
            } else {
                // $sql = "SELECT TOP 1 ID FROM BAN_BANCOS_CARDEX with(NOLOCK) WHERE CONVERT(date, Fecha) = :fecha AND [Débito] = :debito AND Valor = :valor ORDER BY Fecha DESC";
                $sql = "SELECT TOP 1 ID FROM BAN_BANCOS_CARDEX with(NOLOCK) ORDER BY Fecha DESC";
            //     $params = [
            //         ":fecha" => $fecha,
            //         ":debito" => $debito,
            //         ":valor" => $valor
            //     ];
            // $query = $this->db->query($sql, $params);
            $query = $this->db->query($sql);
            return $query;
            };
        } catch (Exception $e) {
            return ['error' => $e];
        }
    }

}
