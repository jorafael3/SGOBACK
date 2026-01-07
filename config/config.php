<?php
// =====================================================
// ARCHIVO: config/config.php
// =====================================================

/**
 * Configuración principal de la aplicación
 */

// Cargar variables de entorno desde .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Saltar comentarios
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Configuración del entorno
define('ENVIRONMENT', $_ENV['APP_ENV'] ?? 'development'); // development, testing, production

// Configuración de URL base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = '/SGONUEVO/back2/'; // Cambiar según tu estructura

define('URL', $protocol . '://' . $host . $basePath);
define('CURRENT_URL', $protocol . '://' . $host . $_SERVER['REQUEST_URI']);

// // Configuración de base de datos
// define('DB_HOST', $_ENV['DB_HOST'] ?? '10.5.1.86');
// define('DB_NAME', $_ENV['DB_NAME'] ?? 'CARTIMEX');
// define('DB_USER', $_ENV['DB_USER'] ?? 'jalvarado');
// define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '12345');
// define('DB_CHARSET', 'utf8mb4');
// define('DB_DRIVER', $_ENV['DB_DRIVER'] ?? 'sqlsrv'); // mysql, pgsql, sqlsrv, sqlite

// Multi-empresa
$EMPRESAS = [];

for ($i = 1; $i <= 10; $i++) { // soporta hasta 10 empresas (puedes ajustar)
    if (isset($_ENV["EMPRESA{$i}_CODE"])) {
        $EMPRESAS[$_ENV["EMPRESA{$i}_CODE"]] = [
            'code'     => $_ENV["EMPRESA{$i}_CODE"],
            'name'     => $_ENV["EMPRESA{$i}_NAME"] ?? "Empresa {$i}",
            'host'     => $_ENV["EMPRESA{$i}_DB_HOST"],
            'database' => $_ENV["EMPRESA{$i}_DB_NAME"],
            'username' => $_ENV["EMPRESA{$i}_DB_USER"],
            'password' => $_ENV["EMPRESA{$i}_DB_PASSWORD"],
            'driver'   => $_ENV["EMPRESA{$i}_DB_DRIVER"] ?? 'mysql',
            'charset'  => 'utf8mb4',
            'port'     => $_ENV["EMPRESA{$i}_DB_PORT"] ?? 3306,
        ];
    }
}

// Empresa por defecto
define('DEFAULT_EMPRESA', $_ENV['EMPRESA3_CODE'] ?? array_key_first($EMPRESAS));

// Hacer la variable $EMPRESAS disponible globalmente
$GLOBALS['EMPRESAS'] = $EMPRESAS;


// Configuraciones adicionales
define('TIMEZONE', 'America/Guayaquil');
define('DEBUG', ENVIRONMENT === 'development');
define('API_VERSION', '1.0.0');

// Configuración de sesión
define('SESSION_TIMEOUT', 3600); // 1 hora
define('SESSION_NAME', 'SOLC_API_SESSION');

// Configuración de seguridad
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? '$s($ecretKey123');
define('BCRYPT_ROUNDS', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutos
define('TOKEN_EXPIRATION', 76 * 60 * 60); // 76 horas
// define('ROOT_PATH', dirname(__DIR__));

// Configuración de archivos
if (DIRECTORY_SEPARATOR === '\\') { // Windows
    $uploadPath = 'C:\\xampp\\htdocs\\sgo_docs\\';
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
} else {
    $uploadPath = '/var/www/html/sgo_docs/'; // Asumiendo Apache en Linux
}
$downloadPath = $protocol . '://' . $host . '/sgo_docs/';

define('UPLOAD_PATH', $uploadPath);
define('DOWNLOAD_PATH', $downloadPath);
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
