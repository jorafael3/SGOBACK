<?php
// ...existing code...
// Este archivo fue movido a controllers/login/login.php
// Copia aquí el contenido original de tu controlador Login
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
// header("Access-Control-Allow-Origin: http://localhost:4200");
//     header("Access-Control-Allow-Origin: *");
//     header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
//     header("Access-Control-Allow-Headers: Content-Type, Authorization");
//     http_response_code(200);
//     exit;
// }
// =====================================================
// ARCHIVO: controllers/login.php
// =====================================================
/**
 * Controlador de Login
 */
require_once __DIR__ . '/../../libs/JwtHelper.php';


class Login extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'login'; // Especifica la carpeta donde está el modelo
        $this->loadModel('login'); // Cargar el modelo de login
    }

    // public function render()
    // {
    //     // Si ya está logueado, redirigir
    //     if ($this->isAuthenticated()) {
    //         header('Location: ' . URL . 'principal');
    //         exit;
    //     }

    //     $this->view->assign('csrf_token', $this->generateCsrfToken());
    //     $this->view->render('login/index');
    // }

    public function authenticate()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(["success" => false, 'error' => 'Método no permitido'], 405);
                return;
            }

            // Validar CSRF
            // if (!$this->validateCsrf()) {
            //     $this->jsonResponse(['error' => 'Token CSRF inválido', 'csrf_token' => $this->generateCsrfToken()], 403);
            //     return;
            // }

            $data = $this->getJsonInput();
            $username = trim($data['username']) ?? null;
            $password = trim($data['password']) ?? null;
            $empresa_code = trim($data['empresa_code']) ?? null;

            // echo json_encode($data);
            // exit;

            if (empty($username) || empty($password)) {
                $this->jsonResponse(["success" => false, 'error' => 'Usuario y contraseña son requeridos'], 400);
                return;
            }

            if (empty($empresa_code)) {
                $this->jsonResponse(["success" => false, 'error' => 'Código de empresa es requerido'], 400);
                return;
            }

            // Configurar el modelo para usar la empresa específica
            $this->model->setEmpresa($empresa_code);

            $result = $this->model->authenticate($username, $password, $empresa_code);
            // $result =  ['success' => true, 'message' => 'Método no implementado'];

            if ($result['success']) {
                // Generar JWT
                $token = $this->generateJwtToken($result['user_data']);
                $result['user_data']['token'] = $token;
                $this->jsonResponse($result, 200);
            } else {
                $this->jsonResponse($result, 200);
            }
        } catch (Exception $e) {
            error_log("Error en authenticate: " . $e->getMessage());
            $this->jsonResponse(["success" => false, 'error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Genera un JWT para el usuario autenticado
     */
    protected function generateJwtToken($user)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $expTime = TOKEN_EXPIRATION; // Tiempo de expiración en segundos (puedes cambiarlo)
        $payload = json_encode([
            'sub' => $user['usrid'],
            'username' => $user['usuario'],
            'empresa' => $user['empresa'],
            'empresa_name' => $user['empresa_name'] ?? 'Empresa Desconocida',
            'iat' => time(),
            'exp' => time() + $expTime // Expira en $expTime segundos
        ]);
        $base64UrlHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64UrlPayload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function logout()
    {
        session_start();
        session_unset();
        session_destroy();

        if ($this->isAjax()) {
            $this->jsonResponse(['success' => true, 'message' => 'Logout exitoso']);
        } else {
            header('Location: ' . URL . 'login');
            exit;
        }
    }

    public function register()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $jwt = str_replace('Bearer ', '', $authHeader);

        if (!JwtHelper::validateJwt($jwt)) {
            $this->jsonResponse(['error' => 'Token JWT inválido o expirado'], 401);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }

        $data = $this->getJsonInput();
        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (empty($username) || empty($password)) {
            $this->jsonResponse(['error' => 'Usuario y contraseña son requeridos', 'headers' => $headers], 400);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $result = $this->model->registerUser($username, $email, $passwordHash);

        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Usuario registrado exitosamente']);
        } else {
            $this->jsonResponse(['error' => 'Error al registrar usuario'], 500);
        }
    }

  
}
