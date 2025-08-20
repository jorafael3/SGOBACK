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
    private $folder = null;
    
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

            // Nueva estructura: /carpeta/controlador/metodo/param1/param2...
            if (!empty($url[0]) && !empty($url[1])) {
                $this->folder = strtolower($url[0]);
                $this->controller = strtolower($url[1]);
            } elseif (!empty($url[0])) {
                $this->controller = strtolower($url[0]);
                $this->folder = null;
            }

            if (isset($url[2]) && !empty($url[2])) {
                $this->method = $url[2];
            }

            if (count($url) > 3) {
                $this->params = array_slice($url, 3);
            }
        }
    }
    
    private function loadController()
    {
        // Nueva lógica: si hay carpeta, buscar en carpeta/controlador.php
        if (!empty($this->folder)) {
            $controllerFile = 'controllers/' . $this->folder . '/' . $this->controller . '.php';
        } else {
            $controllerFile = 'controllers/' . $this->controller . '.php';
        }
        $controllerClass = ucfirst($this->controller);

        if (file_exists($controllerFile)) {
            require_once $controllerFile;
        } else {
            $this->loadErrorController("Archivo de controlador no encontrado: $controllerFile");
            return;
        }

        if (class_exists($controllerClass)) {
            $this->controller = new $controllerClass;
            if (is_object($this->controller)) {
                if (!empty($this->folder)) {
                    $this->controller->folder = $this->folder;
                }
                $modelName = strtolower(get_class($this->controller));
                if (!empty($this->folder)) {
                    $modelFile = 'models/' . $this->folder . '/' . $modelName . '.php';
                    $modelModelFile = 'models/' . $this->folder . '/' . $modelName . 'model.php';
                } else {
                    $modelFile = 'models/' . $modelName . '.php';
                    $modelModelFile = 'models/' . $modelName . 'model.php';
                }

                if (file_exists($modelFile)) {
                    require_once $modelFile;
                } elseif (file_exists($modelModelFile)) {
                    require_once $modelModelFile;
                }
                $this->controller->loadModel($modelName);
            }
        } else {
            $this->loadErrorController("Controlador '$controllerClass' no encontrado");
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

