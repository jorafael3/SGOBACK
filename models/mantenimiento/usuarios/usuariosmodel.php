<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class UsuariosModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
    }

    function getUsuarios($data = [])
    {
        try {
            $sql = "SELECT --top 10  
                usrid,
                UPPER(usuario) as usuario,
                UPPER(nombre) as nombre,
                clave as clave,
                anulado,
                isnull(Departamento, '-') as Departamento,
                EmpleadoID,
                CASE WHEN isnull(email_sgo,'-') = '-' or LTRIM(RTRIM(email_sgo)) = '' THEN '-' ELSE email_sgo END as email,
                isgerencia,
                d.departamento_id,
                is_admin,
                empleado_empresa,
                CASE WHEN isnull(d.departamento_nombre,'-') = '-' or LTRIM(RTRIM(d.departamento_nombre)) = '' THEN '-' ELSE d.departamento_nombre END as departamento_log
                from " . $data["empresa"] . "..SERIESUSR u
                left join " . $data["empresa"] . "..SERIESUSR_DEPARTAMENTOS d
                on d.departamento_id = u.departamento_id
                where anulado = 0
                Order by usuario asc";
            $params = [];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getDepartamentosLogistica()
    {
        try {
            $sql = "SELECT
                    departamento_id,
                    departamento_nombre
                from SERIESUSR_DEPARTAMENTOS";
            $params = [];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo departamentos de logística: " . $e->getMessage());
            return false;
        }
    }

    function ValidarUsuarioCreado($data)
    {
        try {
            $sql = "SELECT usrid FROM SERIESUSR WHERE usuario = :username";
            $params = [':username' => $data['usuario']];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo departamentos de logística: " . $e->getMessage());
            return false;
        }
    }

    function CrearUsuario($data)
    {
        try {
            $sql1 = "SELECT MAX(usrid) + 1 as usrid  FROM SERIESUSR";
            $stmt1 = $this->query($sql1);
            $max_id = str_pad($stmt1["data"][0]['usrid'], 10, '0', STR_PAD_LEFT);
            $sql = "INSERT INTO SERIESUSR 
                (usrid,usuario, nombre, clave, anulado, Departamento, EmpleadoID, email_sgo, isgerencia, departamento_id, is_admin) 
                VALUES 
                (:usrid,:usuario, :nombre, :clave, 0, :Departamento, :EmpleadoID, :email_sgo, :isgerencia, :departamento_id, :is_admin)";
            $params = [
                ':usrid' => $max_id,
                ':usuario' => $data['usuario'],
                ':nombre' => $data['nombre'],
                ':clave' => $data['clave'],
                ':Departamento' => $data['Departamento'],
                ':EmpleadoID' => $data['EmpleadoID'],
                ':email_sgo' => $data['email'],
                ':isgerencia' => $data['isgerencia'],
                ':departamento_id' => $data['departamento_id'],
                ':is_admin' => $data['is_admin']
            ];
            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->logError("Error creando usuario: " . $e->getMessage());
            return false;
        }
    }

    function getMenuUsuario($usrid)
    {
        try {
            $sql = "SGO_MENU_PORUSUARIO 'CARTIMEX','0000000386'";
            $params = [];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo menús de usuario: " . $e->getMessage());
            return false;
        }
    }

    function getMenuUsuarioAsignacion($usrid,$empresa)
    {
        try {
            $sql = "SGO_MENU_USUARIOS_ASIGNACION '$empresa','$usrid'";
            $params = [];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo menús de usuario: " . $e->getMessage());
            return false;
        }
    }

    function ActualizarUsuario($data)
    {
        try {
            $sql = "UPDATE SERIESUSR SET 
                nombre = :nombre,
                clave = :clave,
                Departamento = :Departamento,
                EmpleadoID = :EmpleadoID,
                email_sgo = :email_sgo,
                isgerencia = :isgerencia,
                departamento_id = :departamento_id,
                is_admin = :is_admin
                WHERE usrid = :usrid";
            $params = [
                ':nombre' => $data['nombre'],
                ':clave' => $data['clave'],
                ':Departamento' => $data['Departamento'],
                ':EmpleadoID' => $data['EmpleadoID'],
                ':email_sgo' => $data['email'],
                ':isgerencia' => $data['isgerencia'],
                ':departamento_id' => $data['departamento_id'],
                ':is_admin' => $data['is_admin'],
                ':usrid' => $data['usrid']
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo menús de usuario: " . $e->getMessage());
            return false;
        }
    }

    function ActualizarMenusUsuario($usrid, $menus)
    {
        try {
            // Primero eliminar los menús existentes para el usuario
            $sqlDelete = "DELETE FROM SGO_MENU_USUARIOS WHERE UsuarioId = :usrid";
            $paramsDelete = [':usrid' => $usrid];
            $this->db->execute($sqlDelete, $paramsDelete);
            // Luego insertar los nuevos menús
            foreach ($menus as $menuId) {
                $sqlInsert = "INSERT INTO SGO_MENU_USUARIOS (UsuarioId, MenuId) VALUES (:usrid, :menu_id)";
                $paramsInsert = [
                    ':usrid' => $usrid,
                    ':menu_id' => $menuId
                ];
                $this->db->execute($sqlInsert, $paramsInsert);
            }
            return [
                'success' => true,
                'message' => 'Menús actualizados exitosamente'
            ];
        } catch (Exception $e) {
            $this->logError("Error actualizando menús de usuario: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error actualizando menús de usuario: ' . $e->getMessage()
            ];
        }
    }
}

