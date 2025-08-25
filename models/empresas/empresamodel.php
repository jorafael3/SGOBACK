<?php

require_once __DIR__ . '/../logsmodel.php';


class EmpresaModel extends Model

{
    public function createEmpresa($data)
    {
        try {
            $sql = "INSERT INTO adm_Empresas (
                tenant_uid, razon_social, nombre_comercial, ruc, pais, 
                moneda, zona_horaria, logo_url, plan_id, fecha_expira, 
                estado, usuario_admin_id, creado_por, direccion
            ) VALUES (
                :tenant_uid, :razon_social, :nombre_comercial, :ruc, :pais, 
                :moneda, :zona_horaria, :logo_url, :plan_id, :fecha_expira, 
                :estado, :usuario_admin_id, :creado_por, :direccion
            )";
            $tenant_uid = $this->generateUuid();
            $params = [
                ':tenant_uid' => $tenant_uid,
                ':razon_social' => $data['razon_social'],
                ':nombre_comercial' => $data['nombre_comercial'],
                ':ruc' => $data['ruc'],
                ':pais' => $data['pais'],
                ':moneda' => $data['moneda'],
                ':zona_horaria' => $data['zona_horaria'],
                ':logo_url' => $data['logo_url'],
                ':plan_id' => $data['plan_id'],
                ':fecha_expira' => $data['fecha_expira'],
                ':estado' => $data['estado'],
                ':usuario_admin_id' => $data['usuario_admin_id'],
                ':creado_por' => $data["creado_por"],
                ':direccion' => $data['direccion'],
            ];
            $stmt = $this->query($sql, $params);
            // Log de éxito
            // $logsModel = new LogsModel();
            // $logsModel->createLog([
            //     'id_empresa' => null, // Si tienes el id, puedes obtenerlo con lastInsertId()
            //     'id_usuario' => $data['usuario_admin_id'] ?? null,
            //     'modulo' => 'Empresa',
            //     'accion' => 'INSERT',
            //     'descripcion' => 'Creación de empresa exitosa',
            //     'datos_nuevos' => $data,
            //     'creado_por' => $data['creado_por']
            // ]);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error creando empresa: " . $e->getMessage());
            // Log de error
            $logsModel = new LogsModel();
            $logsModel->createLog([
                'id_empresa' => null,
                'id_usuario' => $data['usuario_admin_id'] ?? null,
                'modulo' => 'Empresa',
                'accion' => 'ERROR',
                'descripcion' => 'Error al crear empresa: ' . $e->getMessage(),
                'datos_nuevos' => $data,
                'creado_por' => $data['creado_por']
            ]);
            return false;
        }
    }

    private function generateUuid()
    {
        // Genera un UUID v4
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }


    public function createContacto($data)
    {
        try {
            $sql = "INSERT INTO adm_Empresa_Contactos (
                id_empresa, nombre, email, telefono, rol_contacto, estado, creado_por
            ) VALUES (
                :id_empresa, :nombre, :email, :telefono, :rol_contacto, :estado, :creado_por
            )";
            $params = [
                ':id_empresa' => $data['id_empresa'],
                ':nombre' => $data['nombre'],
                ':email' => $data['email'],
                ':telefono' => $data['telefono'],
                ':rol_contacto' => $data['rol_contacto'],
                ':estado' => $data['estado'],
                ':creado_por' => $data['creado_por']
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error creando contacto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Asocia un plan a una empresa, registra el historial y actualiza el plan actual y fecha de expiración
     * @param int $id_empresa
     * @param int $plan_id
     * @param string|null $fecha_fin
     * @param int|null $creado_por
     * @return bool
     */
    public function asociarPlanEmpresa($id_empresa, $plan_id, $fecha_fin = null, $creado_por = null)
    {
        try {
            // 1. Insertar en el historial
            $sqlHist = "INSERT INTO adm_Empresas_Planes (
                id_empresa, plan_id, fecha_inicio, fecha_fin, estado, creado_por
            ) VALUES (
                :id_empresa, :plan_id, NOW(), :fecha_fin, 'A', :creado_por
            )";
            $paramsHist = [
                ':id_empresa' => $id_empresa,
                ':plan_id' => $plan_id,
                ':fecha_fin' => $fecha_fin,
                ':creado_por' => $creado_por
            ];
            $this->query($sqlHist, $paramsHist);

            // 2. Actualizar plan_id y fecha_expira en la empresa
            $sqlUpdate = "UPDATE adm_Empresas SET plan_id = :plan_id, fecha_expira = :fecha_fin WHERE id_empresa = :id_empresa";
            $paramsUpdate = [
                ':plan_id' => $plan_id,
                ':fecha_fin' => $fecha_fin,
                ':id_empresa' => $id_empresa
            ];
            $this->query($sqlUpdate, $paramsUpdate);

            $logsModel = new LogsModel();
            $logsModel->createLog([
                'id_empresa' => $id_empresa,
                'modulo' => 'EmpresaPlan',
                'accion' => 'UPDATE',
                'descripcion' => 'Asociación de plan a empresa',
                'datos_nuevos' => [
                    'plan_id' => $plan_id,
                    'fecha_expira' => $fecha_fin
                ],
                'creado_por' => $creado_por
            ]);

            return true;
        } catch (Exception $e) {
            $this->logError("Error asociando plan a empresa: " . $e->getMessage());
            return false;
        }
    }

    function getAllEmpresas()
    {
        try {
            $sql = "SELECT 
            e.id_empresa,
            e.razon_social,
            e.nombre_comercial,
            e.ruc,
            e.pais,
            e.plan_id,
            e.fecha_expira,
            e.estado,
            e.fecha_creacion,
            p.nombre AS plan_nombre,
            e.tenant_uid
            FROM adm_Empresas e
            left join adm_Planes p on e.plan_id = p.plan_id
            WHERE e.estado != 'E'";
            $stmt = $this->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getEmpresaDataByUid($uid)
    {
        try {
            $sql = "SELECT 
            e.id_empresa,
            e.razon_social,
            e.nombre_comercial,
            e.ruc,
            e.pais,
            e.plan_id,
            e.fecha_expira,
            e.estado,
            e.fecha_creacion,
            p.nombre AS plan_nombre,
            e.tenant_uid
            FROM adm_Empresas e
            left join adm_Planes p on e.plan_id = p.plan_id
            WHERE e.estado != 'E'
            AND e.tenant_uid = :uid";
            $stmt = $this->query($sql, [':uid' => $uid]);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getContactosEmpresa($uid)
    {
        try {
            $sql = "SELECT 
            *
            FROM adm_Empresa_Contactos e
            left join adm_Empresas p on e.id_empresa = p.id_empresa
            WHERE e.estado = 'A'
            AND p.tenant_uid = :uid";
            $stmt = $this->query($sql, [':uid' => $uid]);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getPlanesEmpresa()
    {
        try {
            $sql = "SELECT 
            *
            FROM adm_Planes ep
            WHERE ep.estado = 'A'
            ORDER BY ep.plan_id DESC";
            $stmt = $this->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo planes de empresa: " . $e->getMessage());
            return false;
        }
    }

    function getPlanesEmpresaPeriodos()
    {
        try {
            $sql = "SELECT 
            *
            FROM adm_Plannes_Periodos ep
            ORDER BY ep.id_periodo asc";
            $stmt = $this->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo planes de empresa: " . $e->getMessage());
            return false;
        }
    }

     function getPlanesEmpresaPeriodosPrecio()
    {
        try {
            $sql = "SELECT 
            *
            FROM adm_Planes_Precios ep
            WHERE ep.estado = 'A'
            ORDER BY ep.id_plan DESC";
            $stmt = $this->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo planes de empresa: " . $e->getMessage());
            return false;
        }
    }
}
