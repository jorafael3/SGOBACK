<?php
class UsuarioAdminModel extends Model
{
    /**
     * Inserta un usuario administrador en la tabla adm_Usuarios_Admin
     */
    public function createUsuario($data)
    {
        try {
            $sql = "INSERT INTO adm_Usuarios_Admin (
                id_empresa, id_rol, usuario, nombre, apellido, telefono, email, password_hash, estado, creado_por
            ) VALUES (
                :id_empresa, :id_rol, :usuario, :nombre, :apellido, :telefono, :email, :password_hash, :estado, :creado_por
            )";
            $params = [
                ':id_empresa' => 1,
                ':id_rol' => 1,
                ':usuario' => strtoupper($data['usuario']),
                ':nombre' => $data['nombre'],
                ':apellido' => $data['apellido'],
                ':telefono' => $data['telefono'],
                ':email' => $data['email'],
                ':password_hash' => $data['password_hash'],
                ':estado' => "A",
                ':creado_por' => $data["sessionData"]['id']
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error creando usuario admin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserta un rol en la tabla adm_Usuarios_Admin_Roles
     */
    public function createRol($data)
    {
        try {
            $sql = "INSERT INTO adm_Usuarios_Admin_Roles (
                nombre, descripcion, estado, creado_por
            ) VALUES (
                :nombre, :descripcion, :estado, :creado_por
            )";
            $params = [
                ':nombre' => $data['nombre'],
                ':descripcion' => $data['descripcion'],
                ':estado' => $data['estado'],
                ':creado_por' => $data['creado_por']
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error creando rol admin: " . $e->getMessage());
            return false;
        }
    }

    function getAllUsuarios()
    {
        try {
            $sql = "SELECT id_usuario, usuario, nombre, apellido, email, estado, id_empresa as empresa 
            FROM adm_Usuarios_Admin
            WHERE estado != 'E'";
            $stmt = $this->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo usuarios admin: " . $e->getMessage());
            return false;
        }
    }

    function getUserDetails($id_usuario)
    {
        try {
            $sql = "SELECT 
                id_usuario, 
                usuario, 
                nombre, 
                apellido, 
                email, 
                estado, 
                id_empresa as empresa,
                id_rol as rol
                FROM adm_Usuarios_Admin
                WHERE id_usuario = :id_usuario LIMIT 1";
            $params = [':id_usuario' => $id_usuario];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo usuarios admin: " . $e->getMessage());
            return false;
        }
    }

    function updateUserData($data)
    {
        try {
            $fecha_actualizacion = date('Y-m-d H:i:s');
            $sql = "UPDATE adm_Usuarios_Admin
                SET 
                    nombre = :nombre, 
                    apellido = :apellido, 
                    email = :email, 
                    estado = :estado,
                    fecha_actualizacion = :fecha_actualizacion,
                    actualizado_por = :actualizado_por
                WHERE id_usuario = :id_usuario";
            $params = [
                ':id_usuario' => $data["id_usuario"],
                ':fecha_actualizacion' => $fecha_actualizacion,
                ':nombre' => $data['nombre'],
                ':apellido' => $data['apellido'],
                ':estado' => $data['estado'],
                ':email' => $data['email'],
                ':actualizado_por' => $data["sessionData"]['id']
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo usuarios admin: " . $e->getMessage());
            return false;
        }
    }

    function deleteUser($data)
    {
        try {
            $fecha_actualizacion = date('Y-m-d H:i:s');
            $sql = "UPDATE adm_Usuarios_Admin
                SET 
                    estado = :estado,
                    fecha_eliminacion = :fecha_eliminacion,
                    eliminado_por = :eliminado_por
                WHERE id_usuario = :id_usuario";
            $params = [
                ':id_usuario' => $data["id_usuario"],
                ':fecha_eliminacion' => $fecha_actualizacion,
                ':estado' => 'E',
                ':eliminado_por' => $data["sessionData"]['id']
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo usuarios admin: " . $e->getMessage());
            return false;
        }
    }
}
