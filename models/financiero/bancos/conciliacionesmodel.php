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
            $Cuenta = $param['Cuenta'] ?? null;
            $ref = $param['refer'] ?? null;
            // $valor = isset($param['valor']) ? abs((float) $param['valor']) : null;
            // $valor = $param['valorabs'] ?? null;
            $valor = isset($param['valorabs']) ? round((float) $param['valorabs'], 2) : null;
            $fecha = $param['fecha'] ?? null;
            $debito = $param['debito'] ?? null;

            $banco = "SELECT ID FROM BAN_BANCOS WHERE Cuenta = :banco";
            $paramsBanco = [
                ':banco' => $Cuenta
            ];
            $queryBanco = $this->db->query($banco, $paramsBanco);
            $idBanco = $queryBanco['data'][0]['ID'] ?? null;

            $sql = "SELECT TOP 1 ID FROM [10.5.1.3].CARTIMEX.dbo.BAN_BANCOS_CARDEX with(NOLOCK)
                -- WHERE Cheque = :cheque
                -- WHERE SUBSTRING( Cheque, PATINDEX('%[^0]%', Cheque + '1'), LEN(Cheque) ) = :cheque
                -- WHERE TRY_CAST(Cheque AS INT) = TRY_CAST(:cheque AS INT)
                WHERE [Débito] = :debito
                AND TRY_CAST(LTRIM(RTRIM(Referencia)) AS BIGINT) = TRY_CAST(:ref AS BIGINT)
                AND Valor = :valor
                AND BancoId = :bancoId
                ORDER BY Fecha DESC
                ";
            $params = [
                ":debito" => $debito,
                ':ref' => $ref,
                ':valor' => $valor,
                ':bancoId' => $idBanco
            ];
            $query = $this->db->query($sql, $params);
            return $query;
        } catch (Exception $e) {
            return ['error' => $e];
        }
    }

}
