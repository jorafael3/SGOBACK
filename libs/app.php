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

            // Nueva estructura recursiva: /carpeta1/carpeta2/.../controlador/metodo/param1/param2...
            if (!empty($url)) {
                // Buscar controlador recursivamente
                $controllerInfo = $this->findControllerRecursively($url);
                
                if ($controllerInfo) {
                    $this->folder = $controllerInfo['folder'];
                    $this->controller = $controllerInfo['controller'];
                    $methodIndex = $controllerInfo['method_index'];
                    
                    // Extraer método si existe
                    if (isset($url[$methodIndex]) && !empty($url[$methodIndex])) {
                        $this->method = $url[$methodIndex];
                    }
                    
                    // Extraer parámetros si existen
                    if (count($url) > ($methodIndex + 1)) {
                        $this->params = array_slice($url, $methodIndex + 1);
                    }
                } else {
                    // Fallback al comportamiento original para compatibilidad
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
        }
    }

    /**
     * Busca un controlador recursivamente en la estructura de URL
     * @param array $urlSegments Segmentos de la URL
     * @return array|null Información del controlador encontrado o null
     */
    private function findControllerRecursively($urlSegments)
    {
        $segmentCount = count($urlSegments);
        
        // Probar diferentes combinaciones de carpetas/controlador
        for ($i = 0; $i < $segmentCount; $i++) {
            // Construir la ruta de carpetas (desde el inicio hasta el índice i)
            $folderParts = array_slice($urlSegments, 0, $i);
            $folderPath = !empty($folderParts) ? implode('/', $folderParts) : '';
            
            // El siguiente segmento sería el controlador
            if (isset($urlSegments[$i])) {
                $controllerName = strtolower($urlSegments[$i]);
                
                // Construir la ruta del archivo del controlador
                $controllerFile = !empty($folderPath) 
                    ? "controllers/{$folderPath}/{$controllerName}.php"
                    : "controllers/{$controllerName}.php";
                
                // Si el archivo existe, hemos encontrado el controlador
                if (file_exists($controllerFile)) {
                    return [
                        'folder' => $folderPath,
                        'controller' => $controllerName,
                        'method_index' => $i + 1, // El índice donde debería estar el método
                        'file_path' => $controllerFile
                    ];
                }
            }
        }
        
        return null;
    }
    
    private function loadController()
    {
        // Construir la ruta del archivo del controlador
        if (!empty($this->folder)) {
            $controllerFile = 'controllers/' . $this->folder . '/' . $this->controller . '.php';
        } else {
            $controllerFile = 'controllers/' . $this->controller . '.php';
        }
        
        $controllerClass = ucfirst($this->controller);

        // Verificar si el archivo existe
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
        } else {
            // Si no existe, intentar búsqueda recursiva como fallback
            $foundController = $this->findControllerFileRecursively('controllers', $this->controller . '.php');
            if ($foundController) {
                require_once $foundController;
                // Actualizar la carpeta basándose en la ruta encontrada
                $relativePath = str_replace('controllers/', '', dirname($foundController));
                if ($relativePath !== 'controllers' && $relativePath !== '.') {
                    $this->folder = $relativePath;
                }
            } else {
                $this->loadErrorController("Archivo de controlador no encontrado: $controllerFile");
                return;
            }
        }

        // Crear instancia del controlador
        if (class_exists($controllerClass)) {
            $this->controller = new $controllerClass;
            if (is_object($this->controller)) {
                // Asignar la carpeta al controlador para que pueda cargar sus modelos
                if (!empty($this->folder)) {
                    $this->controller->folder = $this->folder;
                }
                
                // Intentar cargar el modelo automáticamente (opcional, puede removerse si no se desea)
                $modelName = strtolower(get_class($this->controller));
                $this->controller->loadModel($modelName);
            }
        } else {
            $this->loadErrorController("Controlador '$controllerClass' no encontrado");
        }
    }

    /**
     * Busca recursivamente un archivo de controlador en todas las subcarpetas
     * @param string $directory Directorio base para buscar
     * @param string $fileName Nombre del archivo a buscar
     * @return string|null Ruta del archivo encontrado o null
     */
    private function findControllerFileRecursively($directory, $fileName)
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
                $result = $this->findControllerFileRecursively($fullPath, $fileName);
                if ($result) {
                    return $result;
                }
            }
        }

        return null;
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

