<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/usuarioadminmodel.php';

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
            $this->jsonResponse(["success" => false, 'message' => 'Token JWT inválido o expirado'], 401);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(["success" => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        $data = $this->getJsonInput();
        $usuario = strtoupper($data['usuario']) ?? null;
        $nombre = $data['nombre'] ?? "";
        $apellido = $data['apellido'] ?? "";
        $telefono = $data['telefono'] ?? "";
        $email = $data['email'] ?? "";
        $password = $data['password'] ?? "";
        $password_confirmation = $data['password_confirmation'] ?? "";
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $data['password_hash'] = $password_hash;

        if (empty($usuario) || empty($nombre) || empty($apellido) || empty($telefono) || empty($email) || empty($password)) {
            $this->jsonResponse(["success" => false, 'message' => 'Faltan datos obligatorios'], 400);
            return;
        }
        if ($password !== $password_confirmation) {
            $this->jsonResponse(["success" => false, 'message' => 'Las contraseñas no coinciden'], 400);
            return;
        }
        $result = $this->model->createUsuario($data);
        if ($result["success"]) {
            $result["message"] = "Usuario creado exitosamente";
            $this->jsonResponse($result, 201);
        } else {
            $this->jsonResponse($result, 200);
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

    public function getAllUsers()
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
        $result = $this->model->getAllUsuarios();
        if ($result) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse($result, 500);
        }
    }

    public function getUserDetails()
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
        $result = $this->model->getUserDetails($data['id_usuario']);
        if ($result["success"]) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse($result, 500);
        }
    }

    public function updateUserData()
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
        $result = $this->model->updateUserData($data);
        if ($result["success"]) {
            $result["message"] = "Usuario actualizado exitosamente";
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse($result, 200);
        }
    }

    public function deleteUser()
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
        $result = $this->model->deleteUser($data);
        if ($result["success"]) {
            $result["message"] = "Usuario eliminado exitosamente";
            $result["params"] = $data;
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse($result, 200);
        }
    }
}
