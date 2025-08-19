<?php
// =====================================================
// ARCHIVO: controllers/login.php
// =====================================================
/**
 * Controlador de Login
 */
class Login extends Controller 
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function render()
    {
        // Si ya está logueado, redirigir
        if ($this->isAuthenticated()) {
            header('Location: ' . URL . 'principal');
            exit;
        }
        
        $this->view->assign('csrf_token', $this->generateCsrfToken());
        $this->view->render('login/index');
    }
    
    public function authenticate()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
                return;
            }
            
            // Validar CSRF
            // if (!$this->validateCsrf()) {
            //     $this->jsonResponse(['error' => 'Token CSRF inválido', 'csrf_token' => $this->generateCsrfToken()], 403);
            //     return;
            // }
            
            $data = $this->getJsonInput();
            $username = $data['username'] ?? null;
            $password = $data['password'] ?? null;
            
            if (empty($username) || empty($password)) {
                $this->jsonResponse(['error' => 'Usuario y contraseña son requeridos', 'username' => $username], 400);
                return;
            }
            
            $result = $this->model->authenticate($username, $password);
            // $result =  ['success' => true, 'message' => 'Método no implementado'];
            
            if ($result['success']) {
                // Generar JWT
                $token = $this->generateJwtToken($result['user']);
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Login exitoso',
                    'token' => $token,
                    'user' => $result
                ]);
            } else {
                $this->jsonResponse($result, 401);
            }
            
        } catch (Exception $e) {
            error_log("Error en authenticate: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Genera un JWT para el usuario autenticado
     */
    protected function generateJwtToken($user)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'sub' => $user['id'],
            'username' => $user['username'],
            'iat' => time(),
            'exp' => time() + 3600 // 1 hora
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
}
/**
 * Para acceder a los endpoints de este controlador:
 * 
 * - Vista de login (GET):      http://localhost/plantillaMVC/login
 * - Autenticación (POST):      http://localhost/plantillaMVC/login/authenticate
 * - Logout (GET o AJAX):       http://localhost/plantillaMVC/login/logout
 * 
 * Asegúrate de que las rutas estén configuradas en tu sistema de rutas.
 */