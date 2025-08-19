<?php
// $link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
// $u = "$_SERVER[HTTP_HOST]";
// define('URL','http://'.$u.'/solc/');// ip local:puerto

// define('HOST', '');
// define('DB', '');
// define('USER', '');
// define('PASSWORD', '');
// define('CHARSET', 'utf8mb4');


// =====================================================
// ARCHIVO: config/config.php
// =====================================================
/**
 * Configuración principal de la aplicación
 */

// Configuración del entorno
define('ENVIRONMENT', $_ENV['APP_ENV'] ?? 'development'); // development, testing, production

// Configuración de URL base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = '/solc/'; // Cambiar según tu estructura

define('URL', $protocol . '://' . $host . $basePath);
define('CURRENT_URL', $protocol . '://' . $host . $_SERVER['REQUEST_URI']);

// Configuración de base de datos
define('DB_HOST', $_ENV['DB_HOST'] ?? '50.87.184.179');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'wsoqajmy_salvaceroCrm');
define('DB_USER', $_ENV['DB_USER'] ?? 'wsoqajmy_salvaceroCrmDs');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? ';RGGNw,TiG{i');
define('DB_CHARSET', 'utf8mb4');
define('DB_DRIVER', $_ENV['DB_DRIVER'] ?? 'mysql'); // mysql, pgsql, sqlsrv, sqlite

// Configuraciones adicionales
define('TIMEZONE', 'America/Guayaquil');
define('DEBUG', ENVIRONMENT === 'development');
define('API_VERSION', '1.0.0');

// Configuración de sesión
define('SESSION_TIMEOUT', 3600); // 1 hora
define('SESSION_NAME', 'SOLC_API_SESSION');

// Configuración de seguridad
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? '123456');
define('BCRYPT_ROUNDS', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutos
define('TOKEN_EXPIRATION', 24 * 60 * 60); // 24 horas
// define('ROOT_PATH', dirname(__DIR__));

// Configuración de archivos
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Configurar zona horaria
date_default_timezone_set(TIMEZONE);

// Configurar sesión
ini_set('session.name', SESSION_NAME);
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $protocol === 'https');

// Mostrar errores solo en desarrollo
if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
