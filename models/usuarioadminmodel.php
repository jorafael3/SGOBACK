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
                ':id_empresa' => $data['id_empresa'],
                ':id_rol' => $data['id_rol'],
                ':usuario' => $data['usuario'],
                ':nombre' => $data['nombre'],
                ':apellido' => $data['apellido'],
                ':telefono' => $data['telefono'],
                ':email' => $data['email'],
                ':password_hash' => $data['password_hash'],
                ':estado' => $data['estado'],
                ':creado_por' => $data['creado_por']
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
}
