<?php
// =====================================================
// ARCHIVO: controllers/errores.php
// =====================================================
/**
 * Controlador de Errores
 */
class Errores extends Controller 
{
    private $errorMessage = '';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function render()
    {
        http_response_code(404);
        $this->view->assign('error_message', $this->errorMessage);
        $this->view->render('errores/404');
    }
    
    public function setErrorMessage($message)
    {
        $this->errorMessage = $message;
    }
    
    public function error500()
    {
        http_response_code(500);
        $this->view->render('errores/500');
    }
    
    public function error403()
    {
        http_response_code(403);
        $this->view->render('errores/403');
    }
}

?>

<?php
// =====================================================
// ARCHIVO: index.php
// =====================================================
/**
 * Punto de entrada principal de la aplicación
 */

define('ROOT_PATH', __DIR__);
define('LIBS_PATH', ROOT_PATH . '/libs');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('MODELS_PATH', ROOT_PATH . '/models');
define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');

function autoloadClasses($className)
{
    $paths = [
        LIBS_PATH . '/' . strtolower($className) . '.php',
        MODELS_PATH . '/' . strtolower($className) . '.php',
        CONTROLLERS_PATH . '/' . strtolower($className) . '.php',
        INCLUDES_PATH . '/' . strtolower($className) . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
}

spl_autoload_register('autoloadClasses');

try {
    // 1. Cargar configuración
    if (!file_exists('config/config.php')) {
        throw new Exception('Archivo de configuración no encontrado');
    }
    require_once 'config/config.php';
    
    // 2. Validar configuración
    validateConfiguration();
    
    // 3. Cargar librerías core
    $coreFiles = [
        'libs/database.php',
        'libs/model.php',
        'libs/view.php', 
        'libs/controller.php',
        'libs/app.php'
    ];
    
    foreach ($coreFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Archivo core requerido no encontrado: $file");
        }
        require_once $file;
    }
    
    // 4. Inicializar aplicación
    initializeApplication();
    
    // 5. Iniciar la aplicación
    $app = new App();
    
} catch (Exception $e) {
    handleFatalError($e);
} catch (Error $e) {
    handleFatalError($e);
}

function validateConfiguration()
{
    $requiredConstants = ['URL', 'DB_HOST', 'DB_NAME', 'DB_USER'];
    
    foreach ($requiredConstants as $constant) {
        if (!defined($constant)) {
            throw new Exception("Constante requerida no definida: $constant");
        }
    }
}

function initializeApplication()
{
    set_error_handler('customErrorHandler');
    set_exception_handler('customExceptionHandler');
    register_shutdown_function('shutdownHandler');
    
    // Verificar conexión a base de datos
    try {
        $db = Database::getInstance();
        $connection = $db->connect();
        
        if (!$connection) {
            throw new Exception('No se pudo conectar a la base de datos');
        }
        
        $testQuery = $db->query("SELECT 1 as test");
        if (!$testQuery) {
            throw new Exception('Base de datos no responde correctamente');
        }
        
    } catch (Exception $e) {
        if (DEBUG) {
            throw new Exception('Error de base de datos: ' . $e->getMessage());
        } else {
            error_log("Error de conexión BD: " . $e->getMessage());
            showDatabaseErrorPage();
            exit;
        }
    }
}

function customErrorHandler($severity, $message, $file, $line)
{
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Error: {$message} en {$file}:{$line}";
    error_log($logMessage);
    
    if (DEBUG && $severity === E_ERROR) {
        displayError('Error PHP', $message, $file, $line);
    }
    
    return true;
}

function customExceptionHandler($exception)
{
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Excepción: {$exception->getMessage()} en {$exception->getFile()}:{$exception->getLine()}";
    error_log($logMessage);
    
    handleFatalError($exception);
}

function shutdownHandler()
{
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
        $errorMessage = "Error fatal: {$error['message']} en {$error['file']}:{$error['line']}";
        error_log($errorMessage);
        
        if (!DEBUG) {
            showGenericErrorPage();
        }
    }
}

function handleFatalError($error)
{
    $message = $error instanceof Throwable ? $error->getMessage() : $error;
    $file = $error instanceof Throwable ? $error->getFile() : 'Desconocido';
    $line = $error instanceof Throwable ? $error->getLine() : 0;
    
    error_log("[" . date('Y-m-d H:i:s') . "] Error Fatal: $message en $file:$line");
    
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    if (DEBUG) {
        displayError('Error Fatal', $message, $file, $line);
    } else {
        showGenericErrorPage();
    }
    
    exit(1);
}

function displayError($title, $message, $file = '', $line = 0)
{
    http_response_code(500);
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>$title</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            .error-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error-title { color: #d32f2f; font-size: 24px; margin-bottom: 20px; }
            .error-message { color: #333; font-size: 16px; margin-bottom: 15px; }
            .error-file { color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <div class='error-title'>$title</div>
            <div class='error-message'>$message</div>";
    
    if ($file && $line) {
        echo "<div class='error-file'>Archivo: $file (Línea: $line)</div>";
    }
    
    echo "</div></body></html>";
}

function showGenericErrorPage()
{
    http_response_code(500);
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Error del Servidor</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; background: #f5f5f5; }
            .error-container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; }
            .error-title { color: #d32f2f; font-size: 28px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <div class='error-title'>Error del Servidor</div>
            <div>Lo sentimos, ha ocurrido un error interno.</div>
        </div>
    </body>
    </html>";
}

function showDatabaseErrorPage()
{
    http_response_code(503);
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Servicio No Disponible</title>
        <meta http-equiv='refresh' content='30'>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; background: #f5f5f5; }
            .error-container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; }
            .error-title { color: #ff9800; font-size: 28px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <div class='error-title'>Servicio Temporalmente No Disponible</div>
            <div>Estamos experimentando problemas técnicos.</div>
        </div>
    </body>
    </html>";
}

?>