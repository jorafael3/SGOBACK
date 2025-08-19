<?php
require_once "controllers/errores.php";
// =====================================================
// ARCHIVO: libs/app.php
// =====================================================
/**
 * Clase principal de la aplicación
 */
class App 
{
    private $controller = 'principal';
    private $method = 'render';
    private $params = [];
    
    public function __construct()
    {
        $this->parseUrl();
        $this->loadController();
        $this->callMethod();
    }
    
    private function parseUrl()
    {
        if (isset($_GET['url'])) {
            $url = filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            
            $url = array_map(function($segment) {
                return preg_replace('/[^a-zA-Z0-9_-]/', '', $segment);
            }, array_filter($url));
            
            if (!empty($url[0])) {
                $this->controller = strtolower($url[0]);
            }
            
            if (isset($url[1]) && !empty($url[1])) {
                $this->method = $url[1];
            }
            
            if (count($url) > 2) {
                $this->params = array_slice($url, 2);
            }
        }
    }
    
    private function loadController()
    {
        $controllerFile = 'controllers/' . $this->controller . '.php';
        
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controllerClass = ucfirst($this->controller);
            
            if (class_exists($controllerClass)) {
                $this->controller = new $controllerClass;
                
                if (is_object($this->controller)) {
                    $this->controller->loadModel(strtolower(get_class($this->controller)));
                }
            } else {
                $this->loadErrorController("Controlador '$controllerClass' no encontrado");
            }
        } else {
            $this->loadErrorController("Archivo de controlador no encontrado: $controllerFile");
        }
    }
    
    private function callMethod()
    {
        if (is_object($this->controller)) {
            if (method_exists($this->controller, $this->method)) {
                $reflection = new ReflectionMethod($this->controller, $this->method);
                if ($reflection->isPublic()) {
                    call_user_func_array([$this->controller, $this->method], $this->params);
                } else {
                    $this->loadErrorController("Método '{$this->method}' no es accesible");
                }
            } else {
                $this->loadErrorController("Método '{$this->method}' no encontrado");
            }
        }
    }
    
    private function loadErrorController($message = "Error desconocido")
    {
        require_once 'controllers/errores.php';
        $this->controller = new Errores();
        
        if (DEBUG) {
            error_log("Error de enrutamiento: $message");
        }
        
        $this->method = 'render';
        $this->params = [];
    }
}

