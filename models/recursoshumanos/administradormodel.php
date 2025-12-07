<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';




class AdministradorModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
    }




    function GetEmpleadosPorEmpresa($data = [])
    {
        try {


            $sql = "EXEC SGO_EMP_CARGAR_EMPLEADOS  @empresa = :empresa";

            $params = [

                ':empresa' => $data['empresa'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetEmpleadosPorEmpresa: " . $e->getMessage());
            error_log("Exception in GetEmpleadosPorEmpresa: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



}
