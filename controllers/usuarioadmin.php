<?php
require_once __DIR__ . '/../libs/JwtHelper.php';
require_once __DIR__ . '/../models/usuarioadminmodel.php';

class UsuarioAdmin extends Controller
{
    /**
     * Crea un usuario administrador
     * Requiere JWT y método POST
     * Recibe datos por JSON
     */
    public function create()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $jwt = str_replace('Bearer ', '', $authHeader);
        if (!JwtHelper::validateJwt($jwt)) {
            $this->jsonResponse(["success" => false, 'error' => 'Token JWT inválido o expirado'], 401);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(["success" => false, 'error' => 'Método no permitido'], 405);
            return;
        }
        $data = $this->getJsonInput();
        $id_empresa = $data['id_empresa'] ?? null;
        $id_rol = $data['id_rol'] ?? null;
        $usuario = strtoupper($data['usuario']) ?? null;
        $nombre = $data['nombre'] ?? "";
        $apellido = $data['apellido'] ?? "";
        $telefono = $data['telefono'] ?? "";
        $email = $data['email'] ?? "";
        $password = $data['password'] ?? "";
        $estado = $data['estado'] ?? 'A';
        $creado_por = $data['creado_por'] ?? null;
        if (empty($usuario) || empty($nombre) || empty($apellido) || empty($telefono) || empty($email) || empty($password)) {
            $this->jsonResponse(["success" => false, 'error' => 'Faltan datos obligatorios'], 400);
            return;
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $result = $this->model->createUsuario([
            'id_empresa' => $id_empresa,
            'id_rol' => $id_rol,
            'usuario' => $usuario,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'email' => $email,
            'password_hash' => $password_hash,
            'estado' => $estado,
            'creado_por' => $creado_por
        ]);
        if ($result) {
            $result["message"] = "Usuario creado exitosamente";
            $this->jsonResponse($result, 201);
        } else {
            $this->jsonResponse($result, 500);
        }
    }

    /**
     * Crea un rol de usuario administrador
     * Requiere JWT y método POST
     * Recibe datos por JSON
     */
    public function createRol()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $jwt = str_replace('Bearer ', '', $authHeader);
        if (!JwtHelper::validateJwt($jwt)) {
            $this->jsonResponse(["success" => false, 'error' => 'Token JWT inválido o expirado'], 401);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(["success" => false,    'error' => 'Método no permitido'], 405);
            return;
        }
        $data = $this->getJsonInput();
        $nombre = $data['nombre'] ?? null;
        $descripcion = $data['descripcion'] ?? null;
        $estado = $data['estado'] ?? 'A';
        $creado_por = $data['creado_por'] ?? null;
        if (empty($nombre)) {
            $this->jsonResponse(["success" => false, 'error' => 'Faltan datos obligatorios'], 400);
            return;
        }
        $result = $this->model->createRol([
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'estado' => $estado,
            'creado_por' => $creado_por
        ]);
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Rol creado exitosamente'], 201);
        } else {
            $this->jsonResponse(['error' => 'Error al crear rol'], 500);
        }
    }
}
