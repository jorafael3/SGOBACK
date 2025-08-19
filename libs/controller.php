<?php 


//=====================================================
// ARCHIVO: libs/controller.php
// =====================================================
/**
 * Controlador base
 */
class Controller 
{
    protected $view;
    protected $model;
    
    public function __construct()
    {
        $this->view = new View();
        $this->initializeSession();
    }
    
    public function loadModel($modelName)
    {
        try {
            $modelFile = 'models/' . strtolower($modelName) . 'model.php';
            
            if (!file_exists($modelFile)) {
                $this->logError("Archivo de modelo no encontrado: $modelFile");
                return false;
            }
            
            require_once $modelFile;
            $modelClassName = ucfirst($modelName) . 'Model';
            
            if (!class_exists($modelClassName)) {
                $this->logError("Clase del modelo no encontrada: $modelClassName");
                return false;
            }
            
            $this->model = new $modelClassName();
            return true;
            
        } catch (Exception $e) {
            $this->logError("Error al cargar modelo '$modelName': " . $e->getMessage());
            return false;
        }
    }
    
    public function render()
    {
        if ($this->view) {
            $controllerName = strtolower(get_class($this));
            $this->view->render($controllerName . '/index');
        }
    }
    
    protected function jsonResponse($data, $statusCode = 200, $headers = [])
    {
        $defaultHeaders = [
            'Content-Type: application/json',
            'Cache-Control: no-cache, must-revalidate'
        ];
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        http_response_code($statusCode);
        
        foreach ($allHeaders as $header) {
            header($header);
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    protected function getJsonInput()
    {
        $json = file_get_contents("php://input");
        return json_decode($json, true);
    }
    
    protected function getPost($key, $default = null)
    {
        return isset($_POST[$key]) ? $this->sanitizeInput($_POST[$key]) : $default;
    }
    
    protected function getGet($key, $default = null)
    {
        return isset($_GET[$key]) ? $this->sanitizeInput($_GET[$key]) : $default;
    }
    
    protected function sanitizeInput($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    protected function isAuthenticated()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    protected function requireAuth($redirectUrl = null)
    {
        if (!$this->isAuthenticated()) {
            if ($this->isAjax()) {
                $this->jsonResponse(['error' => 'No autorizado'], 401);
            } else {
                $redirect = $redirectUrl ?: URL . 'login';
                header("Location: $redirect");
                exit;
            }
        }
    }
    
    protected function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    protected function initializeSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    protected function logError($message)
    {
        if (DEBUG) {
            error_log("[" . date('Y-m-d H:i:s') . "] Controller Error: $message");
        }
    }
    
    protected function generateCsrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    protected function validateCsrf()
    {
        $token = $this->getPost('csrf_token');
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Valida el JWT enviado en el header Authorization
     * Retorna el payload si es válido, false si no
     */
    protected function validateJwtToken()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($authHeader, 'Bearer ') !== 0) {
            return false;
        }
        $jwt = substr($authHeader, 7);
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return false;
        }
        list($headerB64, $payloadB64, $signatureB64) = $parts;
        $signature = base64_decode(strtr($signatureB64, '-_', '+/'));
        $expected = hash_hmac('sha256', $headerB64 . "." . $payloadB64, JWT_SECRET, true);
        if (!hash_equals($expected, $signature)) {
            return false;
        }
        $payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/')), true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }
        return $payload;
    }
}


?>