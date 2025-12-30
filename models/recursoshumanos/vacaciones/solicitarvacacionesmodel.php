<?php

// require_once __DIR__ . '/../logsmodel.php';


class SolicitarVacacionesModel extends Model
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

    function Cargar_Vaciones_Pedidas($param)
    {
        try {
            $EmpleadoID = $param['empleadoid'] ?? null;
            $tipo = 1;
            $sql = "EXECUTE SGO_REC_VACACIONES_SOLICITADAS @empleado = :empleado, @tipo = :tipo";
            $params = [
                ":empleado" => $EmpleadoID,
                ':tipo' => $tipo
            ];
            $query = $this->db->query($sql, $params);
            return $query;
        } catch (Exception $e) {
            return ['error' => $e];
        }
    }

    function getDatosPersonales($data = [])
    {
        try {
            $sql = "SELECT e.Código as Cedula , e.Nombre , VD.Direccion as Dirección , VD.Telefono as Teléfono3
                , e.FechaNac , j.Nombre as Jefe, VD.email , e.email_personal , d.Nombre , e.EstadoCivil , e.Nombre as Nombre_ficha,
                CASE WHEN e.PDecimos = 1 THEN 'SI' ELSE 'NO' END AS PDecimos,
                CASE WHEN e.ProvisionaFR = 1 THEN 'SI' ELSE 'NO' END AS PFondos , isnull(vd.Estado, 0) as Estado 
				, FU.Nombre AS Cargo , e.Genero ,  isnull(e.foto_perfil, '') as foto_perfil, e.Teléfono3 as Telefono2  ,
				e.Dirección as Direccion2 , e.email_personal  as Email2 , 
				e.EstadoCivil  as EstadoCivil2
                from EMP_EMPLEADOS e 
                inner join EMP_EMPLEADOS j ON e.PadreID = j.ID
                inner join SIS_DEPARTAMENTOS D on E.DepartamentoID = D.ID
				left outer join SGO_EMPLEADOS_VALIDACIONES VD on vd.EmpleadoID = e.ID
				inner join EMP_FUNCIONES FU ON FU.ID = E.FunciónID
                WHERE e.ID = :empleadoId
                ";
            $params = [
                ':empleadoId' => $data['empleadoId'] ?? null,
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error en getDatosPersonales: " . $e->getMessage());
            error_log("Exception in getDatosPersonales: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    function GetVacaciones($data = [])
    {
        try {
            $sql = "EXEC  CARTIMEX..SGO_Reporte_Vacaciones_Tomadas :empleadoId";
            $params = [
                ':empleadoId' => $data['empleadoId'] ?? null,
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error en GetVacaciones: " . $e->getMessage());
            error_log("Exception in GetVacaciones: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    
}
