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
    protected $jwtError; // Para almacenar errores de JWT
    public $folder = null;
    public function __construct()
    {
        $this->view = new View();
        $this->initializeSession();
    }
    
    public function loadModel($modelName)
    {
        try {
            $modelFileName = strtolower($modelName) . 'model.php';
            $modelClassName = ucfirst($modelName) . 'Model';
            $modelFile = null;

            // Método 1: Buscar en carpeta específica si está definida
            if (property_exists($this, 'folder') && $this->folder) {
                $specificPath = 'models/' . $this->folder . '/' . $modelFileName;
                if (file_exists($specificPath)) {
                    $modelFile = $specificPath;
                }
            }

            // Método 2: Búsqueda recursiva automática si no se encontró en la carpeta específica
            if (!$modelFile) {
                $modelFile = $this->findModelRecursively('models', $modelFileName);
            }

            // Método 3: Buscar en la raíz de models como fallback
            if (!$modelFile) {
                $rootPath = 'models/' . $modelFileName;
                if (file_exists($rootPath)) {
                    $modelFile = $rootPath;
                }
            }

            // Si no se encontró el archivo, mostrar error detallado
            if (!$modelFile) {
                $searchedPaths = [];
                if (property_exists($this, 'folder') && $this->folder) {
                    $searchedPaths[] = 'models/' . $this->folder . '/' . $modelFileName;
                }
                $searchedPaths[] = 'models/' . $modelFileName;
                $searchedPaths[] = 'búsqueda recursiva en models/';
                
                $this->logError("Archivo de modelo '$modelFileName' no encontrado en: " . implode(', ', $searchedPaths));
                return false;
            }

            // Cargar el archivo del modelo
            require_once $modelFile;

            // Verificar que la clase existe
            if (!class_exists($modelClassName)) {
                $this->logError("Clase del modelo '$modelClassName' no encontrada en archivo: $modelFile");
                return false;
            }

            // Crear instancia del modelo con soporte multiempresa
            $this->model = new $modelClassName();
            
            // Log de éxito (solo en modo debug)
            if (DEBUG) {
                error_log("Modelo '$modelClassName' cargado exitosamente desde: $modelFile");
            }
            return true;
            
        } catch (Exception $e) {
            $this->logError("Error al cargar modelo '$modelName': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca recursivamente un archivo de modelo en todas las subcarpetas
     * @param string $directory Directorio base para buscar
     * @param string $fileName Nombre del archivo a buscar
     * @return string|null Ruta del archivo encontrado o null
     */
    private function findModelRecursively($directory, $fileName)
    {
        if (!is_dir($directory)) {
            return null;
        }

        // Buscar en el directorio actual
        $filePath = $directory . '/' . $fileName;
        if (file_exists($filePath)) {
            return $filePath;
        }

        // Buscar recursivamente en subdirectorios
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $fullPath = $directory . '/' . $file;
            if (is_dir($fullPath)) {
                $result = $this->findModelRecursively($fullPath, $fileName);
                if ($result) {
                    return $result;
                }
            }
        }

        return null;
    }
    
    public function render()
    {
        // Headers de seguridad para respuestas HTML
        $securityHeaders = [
            'X-Content-Type-Options: nosniff',
            'X-Frame-Options: SAMEORIGIN',
            'X-XSS-Protection: 1; mode=block',
            'Referrer-Policy: no-referrer-when-downgrade',
            'Permissions-Policy: geolocation=(), microphone=()'
        ];
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $securityHeaders[] = 'Strict-Transport-Security: max-age=63072000; includeSubDomains; preload';
        }
        foreach ($securityHeaders as $header) {
            header($header);
        }
        if ($this->view) {
            $controllerName = strtolower(get_class($this));
            $this->view->render($controllerName . '/index');
        }
    }
    
    protected function jsonResponse($data, $statusCode = 200, $headers = [])
    {
        $defaultHeaders = [
            'Content-Type: application/json',
            'Cache-Control: no-cache, must-revalidate',
            'X-Content-Type-Options: nosniff',
            'X-Frame-Options: SAMEORIGIN',
            'X-XSS-Protection: 1; mode=block',
            'Referrer-Policy: no-referrer-when-downgrade',
            'Permissions-Policy: geolocation=(), microphone=()'
        ];
        // Strict-Transport-Security solo si usas HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $defaultHeaders[] = 'Strict-Transport-Security: max-age=63072000; includeSubDomains; preload';
        }
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
    
    /**
     * Configura automáticamente el modelo con la empresa del JWT
     * @return array|false Datos del JWT o false si no es válido
     */
    protected function configureModelWithJWT()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $jwt = str_replace('Bearer ', '', $authHeader);
        
        // Validar JWT usando JwtHelper con validación detallada
        require_once __DIR__ . '/JwtHelper.php';
        $validationResult = JwtHelper::validateJwtDetailed($jwt);
        
        if (!$validationResult['valid']) {
            // Almacenar el error para uso posterior
            $this->jwtError = $validationResult;
            return false;
        }
        
        // Usar el payload del resultado de validación
        $jwtData = $validationResult['payload'];
        
        // Configurar modelo con la empresa del JWT si existe
        if ($this->model && isset($jwtData['empresa']) && method_exists($this->model, 'setEmpresa')) {
            $this->model->setEmpresa($jwtData['empresa']);
        }
        
        return $jwtData;
    }
    
    /**
     * Método helper que combina autenticación JWT y configuración de empresa
     * @param int $requiredMethod 1=GET, 2=POST, 3=PUT, 4=DELETE, 0=cualquiera
     * @return array|false Datos del JWT o false si falla
     */
    protected function authenticateAndConfigureModel($requiredMethod = 0)
    {
        // Verificar método HTTP si se especifica
        if ($requiredMethod > 0) {
            $methods = ['', 'GET', 'POST', 'PUT', 'DELETE'];
            if ($_SERVER['REQUEST_METHOD'] !== $methods[$requiredMethod]) {
                $this->jsonResponse([
                    "success" => false, 
                    'error' => 'Método no permitido',
                    'error_code' => 'METHOD_NOT_ALLOWED'
                ], 405);
                return false;
            }
        }else{
            return true;
        }
        
        $jwtData = $this->configureModelWithJWT();
        if (!$jwtData) {
            // Determinar el código HTTP y mensaje basado en el tipo de error JWT
            $errorInfo = $this->jwtError ?? [
                'error' => 'TOKEN_UNKNOWN_ERROR',
                'message' => 'Error desconocido de autenticación'
            ];
            
            $httpCode = 401; // Por defecto
            $errorResponse = [
                "success" => false,
                'error' => $errorInfo['message'],
                'error_code' => $errorInfo['error']
            ];
            
            // Códigos específicos según el tipo de error
            switch ($errorInfo['error']) {
                case 'TOKEN_MISSING':
                    $httpCode = 401;
                    $errorResponse['requires_login'] = true;
                    break;
                    
                case 'TOKEN_EXPIRED':
                    $httpCode = 401;
                    $errorResponse['requires_login'] = true;
                    $errorResponse['session_expired'] = true;
                    $errorResponse['expired_at'] = $errorInfo['expired_at'] ?? null;
                    break;
                    
                case 'TOKEN_INVALID_SIGNATURE':
                case 'TOKEN_MALFORMED':
                case 'TOKEN_DECODE_ERROR':
                    $httpCode = 401;
                    $errorResponse['requires_login'] = true;
                    $errorResponse['invalid_token'] = true;
                    break;
                    
                default:
                    $httpCode = 401;
                    $errorResponse['requires_login'] = true;
                    break;
            }
            
            $this->jsonResponse($errorResponse, $httpCode);
            return false;
        }
        
        return $jwtData;
    }
    
    /**
     * Obtiene la empresa actual del JWT
     * @return string|null Código de empresa o null
     */
    protected function getCurrentEmpresa()
    {
        $jwtData = $this->configureModelWithJWT();
        return $jwtData ? ($jwtData['empresa'] ?? null) : null;
    }
    
    /**
     * Crea una instancia temporal de un modelo para otra empresa
     * @param string $modelName Nombre del modelo
     * @param string $empresaCode Código de la empresa
     * @return object|false Instancia del modelo o false si falla
     */
    protected function getModelForEmpresa($modelName, $empresaCode)
    {
        try {
            $modelFileName = strtolower($modelName) . 'model.php';
            $modelClassName = ucfirst($modelName) . 'Model';
            $modelFile = null;

            // Método 1: Buscar en carpeta específica si está definida
            if (property_exists($this, 'folder') && $this->folder) {
                $specificPath = 'models/' . $this->folder . '/' . $modelFileName;
                if (file_exists($specificPath)) {
                    $modelFile = $specificPath;
                }
            }

            // Método 2: Búsqueda recursiva automática si no se encontró en la carpeta específica
            if (!$modelFile) {
                $modelFile = $this->findModelRecursively('models', $modelFileName);
            }

            // Método 3: Buscar en la raíz de models como fallback
            if (!$modelFile) {
                $rootPath = 'models/' . $modelFileName;
                if (file_exists($rootPath)) {
                    $modelFile = $rootPath;
                }
            }

            if (!$modelFile) {
                $this->logError("Archivo de modelo '$modelFileName' no encontrado para empresa '$empresaCode'");
                return false;
            }

            require_once $modelFile;

            if (!class_exists($modelClassName)) {
                $this->logError("Clase del modelo '$modelClassName' no encontrada para empresa '$empresaCode'");
                return false;
            }

            // Crear instancia del modelo para la empresa específica
            return new $modelClassName($empresaCode);
            
        } catch (Exception $e) {
            $this->logError("Error al crear modelo para empresa '$empresaCode': " . $e->getMessage());
            return false;
        }
    }
}


?>