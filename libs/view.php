<?php 

// =====================================================
// ARCHIVO: libs/view.php
// =====================================================
/**
 * Clase View
 * Maneja la renderización de vistas y templates
 */
class View 
{
    private $data = [];
    private $layoutPath = 'views/layouts/';
    private $viewPath = 'views/';
    
    /**
     * Asigna datos a la vista
     */
    public function assign($key, $value) 
    {
        $this->data[$key] = $value;
    }
    
    /**
     * Renderiza una vista
     */
    public function render($viewName, $layout = null) 
    {
        try {
            $viewFile = $this->viewPath . $viewName . '.php';
            
            if (!file_exists($viewFile)) {
                throw new Exception("Vista no encontrada: $viewFile");
            }
            
            // Extraer datos para usar como variables en la vista
            extract($this->data);
            
            if ($layout) {
                $layoutFile = $this->layoutPath . $layout . '.php';
                if (file_exists($layoutFile)) {
                    // Capturar contenido de la vista
                    ob_start();
                    include $viewFile;
                    $content = ob_get_clean();
                    
                    // Renderizar layout con contenido
                    include $layoutFile;
                } else {
                    include $viewFile;
                }
            } else {
                include $viewFile;
            }
            
        } catch (Exception $e) {
            if (DEBUG) {
                echo "Error en vista: " . $e->getMessage();
            } else {
                include_once 'views/errores/500.php';
            }
        }
    }
    
    /**
     * Renderiza JSON
     */
    public function json($data, $statusCode = 200) 
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}


?>