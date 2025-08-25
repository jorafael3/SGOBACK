<?php

require_once __DIR__ . '/../logsmodel.php';


class PlanesModel extends Model

{

    function getPlanes()
    {
        try {
            // $sql = "SELECT 
            // ap.plan_id,
            // app2.id_periodo,
            // app.id_plan_precio,
            // ap.nombre as plan_nombre,
            // ap.max_usuarios,
            // ap.max_documentos,
            // ap.max_almacenamiento,
            // app2.nombre as periodo_nombre,
            // app.precio
            // from adm_Planes ap 
            // left join adm_Planes_Precios app 
            // on app.id_plan = ap.plan_id 
            // left join adm_Plannes_Periodos app2 
            // on app2.id_periodo = app.id_periodo 
            // where ap.estado = 'A'
            // -- and app2.id_periodo = 1
            // ";
            $sql = "SELECT 
                ap.plan_id, 
                ap.nombre as plan_nombre,
                ap.descripcion,
                ap.max_usuarios,
                ap.max_documentos,
                ap.max_almacenamiento
                from adm_Planes ap 
                where ap.estado = 'A' ";

            $stmt = $this->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

     function getPlanesDetalle($plan_id)
    {
        try {
            $sql = "SELECT 
            app2.id_periodo,
            app.id_plan_precio,
            app2.nombre as periodo_nombre,
            app.precio
            from adm_Planes_Precios app 
            left join adm_Plannes_Periodos app2 
            on app2.id_periodo = app.id_periodo 
            where app.estado = 'A'
            and id_plan  =  :plan_id
            ";
            $stmt = $this->query($sql, [
                ':plan_id' => $plan_id
            ]);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function createPlan($datos)
    {
        try {
            $sql = "INSERT INTO adm_Planes (
                nombre, 
                descripcion, 
                max_usuarios, 
                max_documentos, 
                max_almacenamiento
            ) VALUES
            (
                :nombre, 
                :descripcion, 
                :max_usuarios, 
                :max_documentos, 
                :max_almacenamiento
            )";
            $stmt = $this->query($sql, [
                ':nombre' => $datos['nombre'],
                ':descripcion' => $datos['descripcion'],
                ':max_usuarios' => $datos['max_usuarios'],
                ':max_documentos' => $datos['max_documentos'],
                ':max_almacenamiento' => $datos['max_almacenamiento']
            ]);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error insertando planes: " . $e->getMessage());
            return false;
        }
    }
}
