<?php

class VacacionesModel extends Model
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

// Agregar al Procedimiento SGO_REC_VACACIONES_SOLICITADAS -> cancelado, cancelado_comentario
    function Cargar_Vacaciones_Pedidas($param)
    {
        try {
            // $EmpleadoID = $param['EmpleadoID'] ?? null;
            $EmpleadoID = $param['userdata']['EmpleadoId'] ?? $param['userdata']['EmpleadoID'] ?? null;
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

    function Guardar_Vacaciones_empleados($param)
    {
        try {
            // return $param;
            $DATOS = $param['DATOS'] ?? [];
            $DATOS_EMPLEADO = $param['DATOS_EMPLEADO'] ?? [];
            $SOLICITUD_ID = date("YmdHis");

            $EmpleadoID = $param['userdata']['EmpleadoId'] ?? $param['userdata']['EmpleadoID'] ?? null;

            $OK = 0;
            $ERRORES = [];

            foreach ($DATOS as $row) {
                $ID = $SOLICITUD_ID;

                $fecha_salida = $param['inicio'];
                $fecha_fin = $param['fin'];
                $fecha_regreso_trabajo = $param['regreso'];

                $Dias = $row['solicitados'];
                $Total_pendiente = $row['Disponibles'];
                $Periodo = $row['Periodo'];
                $year = $row['year'];

                $sql = "EXECUTE SGO_EMP_VACIONES_INSERT_PERIODO_EMPLEADO 
                    @ID = :ID, 
                    @EmpleadoID = :EmpleadoID, 
                    @Dias = :Dias, 
                    @fecha_salida = :fecha_salida, 
                    @fecha_regreso = :fecha_regreso, 
                    @fecha_regreso_trabajo = :fecha_regreso_trabajo, 
                    @Total_pendiente = :Total_pendiente, 
                    @periodo = :periodo, 
                    @year = :year
                ";
                $params = [
                    ":ID" => $ID,
                    ":EmpleadoID" => $EmpleadoID,
                    ":Dias" => $Dias,
                    ":fecha_salida" => $fecha_salida,
                    ":fecha_regreso" => $fecha_fin,
                    ":fecha_regreso_trabajo" => $fecha_regreso_trabajo,
                    ":Total_pendiente" => $Total_pendiente,
                    ":periodo" => $Periodo,
                    ":year" => $year
                ];
                $query = $this->db->execute($sql, $params);
                if ($query['success']) {
                    $OK++;
                } else {
                    $ERRORES[] = $query;
                }
            }
            if (count($ERRORES) == 0) {
                return $res = array(
                    "success" => true,
                    "data" => [],
                    "message" => "Datos Guardados",
                    "sql" => $query,
                    "params" => $params
                );
            } else {
                return $res = array(
                    "success" => false,
                    "data" => $ERRORES,
                    "message" => "Error al guardar",
                    "sql" => $query,
                    "param" => $param
                );
            }
        } catch (Exception $e) {
            return ['error' => $e];
        }
    }

// ALTER TABLE SGO_VACACIONES_SOLICITADAS_EMPLEADOS
// ADD cancelado_comentario VARCHAR(800) NULL,
// cancelado BIT NOT NULL DEFAULT 0;
    function Anular_Solicitud_Vacaciones($param)
    {
        try {
            $SOLICITUD_ID = $param['solicitudId'] ?? null;
            $EmpleadoID = $param['EmpleadoID'] ?? null;
            $Cancelado_Comentario = $param['motivo'] ?? null;
            
            $sql = "UPDATE SGO_VACACIONES_SOLICITADAS_EMPLEADOS
            SET cancelado = 1, 
            cancelado_comentario = :Cancelado_comentario
            WHERE ID = :SOLICITUD_ID AND EmpleadoID = :EmpleadoID";
            $params = [
                ":SOLICITUD_ID" => $SOLICITUD_ID,
                ":EmpleadoID" => $EmpleadoID,
                ":Cancelado_comentario" => $Cancelado_Comentario
            ];
            $query = $this->db->execute($sql, $params);
            return $query;
        } catch (Exception $e) {
            return ['error' => $e];
        }
    }

}
