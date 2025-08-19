<?php
class LogsModel extends Model
{
    /**
     * Inserta un registro en la tabla adm_Logs
     * @param array $data Datos del log
     * @return bool
     */
    public function createLog($data)
    {
        try {
            $sql = "INSERT INTO adm_Logs (
                id_empresa, id_usuario, modulo, accion, descripcion, datos_anteriores, datos_nuevos, ip_origen, user_agent, creado_por
            ) VALUES (
                :id_empresa, :id_usuario, :modulo, :accion, :descripcion, :datos_anteriores, :datos_nuevos, :ip_origen, :user_agent, :creado_por
            )";
            $params = [
                ':id_empresa' => $data['id_empresa'] ?? null,
                ':id_usuario' => $data['id_usuario'] ?? null,
                ':modulo' => $data['modulo'],
                ':accion' => $data['accion'],
                ':descripcion' => $data['descripcion'],
                ':datos_anteriores' => isset($data['datos_anteriores']) ? json_encode($data['datos_anteriores']) : null,
                ':datos_nuevos' => isset($data['datos_nuevos']) ? json_encode($data['datos_nuevos']) : null,
                ':ip_origen' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':creado_por' => $data['creado_por'] ?? null
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error creando log: " . $e->getMessage());
            return false;
        }
    }
}
