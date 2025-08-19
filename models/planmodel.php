<?php
class PlanModel extends Model
{
    /**
     * Inserta un plan en la tabla adm_Planes
     */
    public function createPlan($data)
    {
        try {
            $sql = "INSERT INTO adm_Planes (
                nombre, descripcion, max_usuarios, max_documentos, max_almacenamiento, precio_mensual, precio_anual, duracion_meses, estado
            ) VALUES (
                :nombre, :descripcion, :max_usuarios, :max_documentos, :max_almacenamiento, :precio_mensual, :precio_anual, :duracion_meses, :estado
            )";
            $params = [
                ':nombre' => $data['nombre'],
                ':descripcion' => $data['descripcion'],
                ':max_usuarios' => $data['max_usuarios'],
                ':max_documentos' => $data['max_documentos'],
                ':max_almacenamiento' => $data['max_almacenamiento'],
                ':precio_mensual' => $data['precio_mensual'],
                ':precio_anual' => $data['precio_anual'],
                ':duracion_meses' => $data['duracion_meses'],
                ':estado' => $data['estado']
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error creando plan: " . $e->getMessage());
            return false;
        }
    }
}
