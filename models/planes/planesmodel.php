<?php

require_once __DIR__ . '/../logsmodel.php';


class PlanesModel extends Model

{
    
    function getPlanes()
    {
        try {
            $sql = "SELECT 
            ap.plan_id,
            app2.id_periodo,
            app.id_plan_precio,
            ap.nombre as plan_nombre,
            ap.max_usuarios,
            ap.max_documentos,
            ap.max_almacenamiento,
            app2.nombre as periodo_nombre,
            app.precio
            from adm_Planes ap 
            left join adm_Planes_Precios app 
            on app.id_plan = ap.plan_id 
            left join adm_Plannes_Periodos app2 
            on app2.id_periodo = app.id_periodo 
            where ap.estado = 'A'
            and app2.id_periodo = 1";
            $stmt = $this->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    
}
