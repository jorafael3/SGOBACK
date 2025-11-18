<?php

class MPPsModel extends Model
{
    public function __construct($empresaCode = null)
    {
        parent::__construct($empresaCode);
        if (DEBUG) {
            error_log("MPPsModel conectado a: " . $this->empresaCode);
        }
    }

    function getDepartamentos(){
        try {
            $sql = "SELECT * from CARTIMEX.dbo.SIS_DEPARTAMENTOS where Anulado='0' and SucursalID='00' and Nombre not like '%COMPUTRO%' and PadreID='0000000001'";
            $stmt = $this->query($sql);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError("Error obteniendo departamentos: " . $e->getMessage());
            return false;
        }
    }
}