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
            // $valor = isset($param['valor']) ? abs((float) $param['valor']) : null;
            // $valor = $param['valorabs'] ?? null;
            $valor = isset($param['valorabs']) ? round((float)$param['valorabs'], 2) : null;
            $fecha = $param['fecha'] ?? null;
            $debito = $param['debito'] ?? null;
            $chequeCheck = $param['chequeCheck'] ?? 0;
            if ($chequeCheck == 1) {
                $sql = "SELECT TOP 1 ID FROM [10.5.1.3].CARTIMEX.dbo.BAN_BANCOS_CARDEX with(NOLOCK)
                -- WHERE Cheque = :cheque
                -- WHERE SUBSTRING( Cheque, PATINDEX('%[^0]%', Cheque + '1'), LEN(Cheque) ) = :cheque
                -- WHERE TRY_CAST(Cheque AS INT) = TRY_CAST(:cheque AS INT)
                WHERE LTRIM(RTRIM(Cheque)) NOT LIKE '%[^0-9]%'
                AND TRY_CAST(LTRIM(RTRIM(Cheque)) AS BIGINT) = TRY_CAST(:cheque AS BIGINT)
                AND Valor = :valor
                ORDER BY Fecha DESC
                ";
                $params = [
                    ':cheque' => $cheque,
                    ':valor' => $valor,
                ];
                $query = $this->db->query($sql, $params);
                return $query;
            } else {
                $sql = "SELECT TOP 1 ID FROM [10.5.1.3].CARTIMEX.dbo.BAN_BANCOS_CARDEX with(NOLOCK)
                WHERE [Débito] = :debito
                AND Valor = :valor
                ORDER BY Fecha DESC
                ";
                $params = [
                    ":debito" => $debito,
                    ":valor" => $valor
                ];
            $query = $this->db->query($sql, $params);
            return $query;
            };
        } catch (Exception $e) {
            return ['error' => $e];
        }
    }

}
