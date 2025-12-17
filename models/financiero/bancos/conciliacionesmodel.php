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
            $cheque = $param['cheque'] ?? null;
            $valor = abs(floatval($param['valor'])) ?? null;
            $fecha = $param['fecha'] ?? null;
            $debito = $param['debito'] ?? null;
            $chequeCheck = $param['chequeCheck'] ?? 0;

            if ($chequeCheck == 1) {
                if (!$cheque || $valor === null)
                    return [];
                $sql = "SELECT TOP (1) * FROM BAN_BANCOS_CARDEX WITH (NOLOCK) 
                WHERE Cheque = :cheque 
                AND Valor = :valor";
                $params = [
                    ':cheque' => $cheque,
                    ':valor' => $valor,
                ];
                $stmt = $this->db->query($sql, $params);
                return $stmt;
            }
            if (!$fecha || $valor === null)
                return [];

            $sql = "SELECT TOP (1) * FROM BAN_BANCOS_CARDEX WITH (NOLOCK)
                    WHERE Fecha = :fecha
                    AND [Débito] = :debito
                    AND Valor = :valor
                    ORDER BY Fecha DESC";
            $params = [
                ':fecha' => $fecha,
                ':debito' => $debito,
                ':valor' => $valor,
            ];
            $stmt = $this->db->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            return [];
        }
    }

    function ComprobarConciliacionFila($param){
        $cheque = $param['col5'] ?? null;
        $valor = round(abs((float)$param['valor']), 2)  ?? null;
        $fecha = $param['fecha'] ?? null;
        $debito = $param['debito'] ?? 0;
        $chequeCheck = $param['chequeCheck'] ?? 0;
        if ($chequeCheck == 1) {
            $cheque = ltrim($cheque, '0');
            $sql = "SELECT 1
            FROM BAN_BANCOS_CARDEX
            WHERE Cheque = :cheque
            AND Valor  = :valor";
            $query = $this->db->query($sql, [
                ':cheque' => $cheque,
                ':valor' => $valor
            ]);
            return $query['data'];
        }
        $sql = "SELECT 1
        FROM BAN_BANCOS_CARDEX
        WHERE CONVERT(date, Fecha) = :fecha
        AND [Débito] = :debito
        AND Valor   = :valor";
        $query = $this->db->query($sql, [
            ':fecha' => $fecha,
            ':debito' => $debito,
            ':valor' => $valor
        ]);
        return $query['data'];
    }

}
