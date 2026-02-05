<?php

// require_once __DIR__ . '/../logsmodel.php';


class NotificacionesModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("DespacharFacturasModel conectado a: " . $this->empresaCode);
        }
    }

    function GetNotificaciones($data)
    {
        try {
            $sql = "SELECT * FROM SGO_notificaciones
              WHERE usuario_id = :usuario
              AND empresa = :empresa      -- ← Solo notificaciones de su empresa
              AND activo = 1
              ORDER BY fecha DESC";
            $params = [
                'usuario' => $data['userdata']["usrid"],
                'empresa' => $data['userdata']["empleado_empresa"]
            ];
            $query = $this->db->query($sql, $params);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function MarcarComoLeida($data)
    {
        try {
            $sql = "UPDATE SGO_notificaciones 
                    SET leida = 1
                WHERE usuario_id = :usuario_id
                AND empresa = :empresa
                AND leida = 0
                AND id = :id
                ";
            $params = [
                'id' => $data['id'],
                'usuario_id' => $data['userdata']["usrid"],
                'empresa' => $data['userdata']["empleado_empresa"]
            ];
            $query = $this->db->execute($sql, $params);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al marcar notificación como leída'];
        }
    }

    function InsertarNotificacion($data)
    {
        try {
            $sql = "INSERT INTO CARTIMEX..SGO_notificaciones (usuario_id, empresa, tipo, titulo, mensaje, url, fecha, leida, activo)
                    VALUES (:usuario_id, :empresa, :tipo, :titulo, :mensaje, :url, GETDATE(), 0, 1)";
            $params = [
                'usuario_id' => $data['usuario_id'],
                'empresa' => $data['empresa'],
                'tipo' => $data['tipo'],
                'titulo' => $data['titulo'],
                'mensaje' => $data['mensaje'],
                'url' => $data['url'],
            ];
            $query = $this->db->execute($sql, $params);
            return $query;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al insertar notificación'];
        }
    }
}
