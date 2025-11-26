<?php

// require_once __DIR__ . '/../logsmodel.php';


class ObligacionesModel extends Model
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

    function Cargar_Tipos_Obligaciones()
    {
        try {
            $sql = "SELECT * FROM SGO_AMORTIZACION_TIPOS WHERE estado=1";
            $query = $this->db->query($sql, []);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }
    
    function Cargar_ACC_CuentasGastos()
    {
        try {
            $sql = "SELECT * FROM ACC_CUENTAS WITH (NOLOCK) where Anulado=0 and Tipo='DETALLE' and Clase='62'";
            $query = $this->db->query($sql, []);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }
    function Cargar_ACC_CuentasPasivos_Provision()
    {
        try {
            $sql = "SELECT * FROM ACC_CUENTAS WITH (NOLOCK) where Anulado=0 and Tipo='DETALLE' and Clase='12'";
            $query = $this->db->query($sql, []);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Actualizar_cuentas_obligaciones($data)
    {
        try {
            if (!is_array($data)) {
                return ['success' => false, 'error' => 'Formato inválido'];
            }
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $nombre = $data['nombre'] ?? '';
            $cuenta = $data['cuenta'] ?? '';
            $estado = $data['estado'] ?? 1;
            $cuentaPasivo = $data['cuentaPasivo'] ?? '';
            $cuentaProvision = $data['cuentaProvision'] ?? '';
            $cuentaGasto = $data['cuentaGasto'] ?? '';

            if ($id > 0) {
                $sqlUpdate = "
                    UPDATE SGO_AMORTIZACION_TIPOS
                    SET nombre      = :nombre,
                        cuenta      = :cuenta,
                        estado      = :estado,
                        cuentaGasto = :cuentaGasto,
                        cuentaPasivo  = :cuentaPasivo,
                        cuentaProvision = :cuentaProvision
                    WHERE id = :id
                ";
                $param = [
                    ':id' => $id,
                    ':nombre' => $nombre,
                    ':cuenta' => $cuenta,
                    ':estado' => $estado,
                    ':cuentaGasto' => $cuentaGasto,
                    ':cuentaPasivo' => $cuentaPasivo,
                    ':cuentaProvision' => $cuentaProvision,
                ];
                $result = $this->db->execute($sqlUpdate, $param);
            } else {
                $sqlInsert = "
                    INSERT INTO SGO_AMORTIZACION_TIPOS
                        (nombre, cuenta, estado, cuentaGasto, cuentaPasivo, cuentaProvision)
                    VALUES
                        (:nombre, :cuenta, :estado, :cuentaGasto, :cuentaPasivo, :cuentaProvision)
                ";
                $param = [
                    ':nombre' => $nombre,
                    ':cuenta' => $cuenta,
                    ':estado' => $estado,
                    ':cuentaGasto' => $cuentaGasto,
                    ':cuentaPasivo' => $cuentaPasivo,
                    ':cuentaProvision' => $cuentaProvision,
                ];
                $result = $this->db->execute($sqlInsert, $param);
            }
            return $result;
            // return ['success' => true, 'message' => 'Cuentas actualizadas correctamente'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    function Borrar_Tipo_Obligacion($data)
    {
        try {
            if (!is_array($data)) {
                return ['success' => false, 'error' => 'Formato inválido'];
            }
            $id = isset($data['id']) ? intval($data['id']) : 0;

            if ($id > 0) {
                $sqlDelete = "UPDATE SGO_AMORTIZACION_TIPOS SET estado = 0 WHERE id = :id";
                $param = [':id' => $id];
                $result = $this->db->execute($sqlDelete, $param);
                return $result;
            } else {
                return ['success' => false, 'error' => 'ID inválido'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

}