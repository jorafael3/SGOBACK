<?php
/**
 * Script de Prueba para PHPMailer en EmailService
 * 
 * Uso: 
 *   1. Configurar las credenciales SMTP en las variables de abajo
 *   2. Ejecutar: php test_email.php
 *   3. Revisar el resultado en la consola
 * 
 * Nota: Este es un script de testing. Cambiar el email de prueba por uno real.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║        Test de Configuración PHPMailer - EmailService      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ===================== CONFIGURACIÓN DE PRUEBA =====================
$SMTP_HOST = 'smtp.gmail.com';          // Cambiar según tu proveedor
$SMTP_PORT = 587;                       // 587 para TLS, 465 para SSL
$SMTP_USERNAME = 'tu_email@gmail.com';  // Tu email SMTP
$SMTP_PASSWORD = 'tu_contraseña_app';   // Contraseña de aplicación
$EMAIL_DESTINO = 'prueba@ejemplo.com';  // Email donde enviar prueba
$EMPRESA = 'COMPUTRONSA';               // Empresa a usar
// ====================================================================

echo "CONFIGURACIÓN ACTUAL:\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "  Host SMTP      : $SMTP_HOST\n";
echo "  Puerto         : $SMTP_PORT\n";
echo "  Usuario        : $SMTP_USERNAME\n";
echo "  Contraseña     : " . (strlen($SMTP_PASSWORD) > 0 ? "***" : "SIN CONFIGURAR") . "\n";
echo "  Email Destino  : $EMAIL_DESTINO\n";
echo "  Empresa        : $EMPRESA\n";
echo "─────────────────────────────────────────────────────────────\n\n";

// Validaciones previas
echo "VALIDACIONES PREVIAS:\n";
echo "─────────────────────────────────────────────────────────────\n";

$validaciones = [];

// Verificar que EmailService exista
if (!file_exists(__DIR__ . '/libs/EmailService.php')) {
    $validaciones['EmailService'] = '✗ No encontrado';
} else {
    $validaciones['EmailService'] = '✓ Encontrado';
}

// Verificar que PHPMailer esté instalado
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    $validaciones['PHPMailer'] = '✗ No instalado (ejecutar: composer require phpmailer/phpmailer)';
} else {
    $validaciones['PHPMailer'] = '✓ Instalado';
}

// Verificar carpeta de templates
if (!is_dir(__DIR__ . '/email-templates')) {
    $validaciones['Templates'] = '✗ No encontrada carpeta email-templates/';
} else {
    $templates = glob(__DIR__ . '/email-templates/*.html');
    $validaciones['Templates'] = '✓ Encontrada (' . count($templates) . ' templates)';
}

// Verificar logs writable
if (!is_writable(__DIR__)) {
    $validaciones['Permisos de escritura'] = '✗ No hay permisos para escribir logs';
} else {
    $validaciones['Permisos de escritura'] = '✓ OK';
}

// Mostrar validaciones
foreach ($validaciones as $item => $estado) {
    printf("  %-30s %s\n", $item, $estado);
}

echo "\n";

// Si hay errores críticos, detener
$hayErrores = false;
foreach ($validaciones as $estado) {
    if (strpos($estado, '✗') === 0) {
        $hayErrores = true;
    }
}

if ($hayErrores) {
    echo "⚠️  ERRORES ENCONTRADOS - No se puede continuar\n";
    echo "Resuelve los errores anteriores y vuelve a intentar.\n\n";
    exit(1);
}

// ==================== PRUEBA DE ENVÍO ====================
echo "INTENTANDO ENVIAR EMAIL DE PRUEBA:\n";
echo "─────────────────────────────────────────────────────────────\n\n";

try {
    // Requerir archivos necesarios
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/libs/EmailService.php';
    
    use libs\EmailService;
    use PHPMailer\PHPMailer\PHPMailer;
    
    // Crear instancia de EmailService
    $emailService = new EmailService($EMPRESA);
    
    // Configurar SMTP manualmente para prueba
    $emailService->configurarSMTP(
        $SMTP_HOST,
        $SMTP_PORT,
        $SMTP_USERNAME,
        $SMTP_PASSWORD,
        PHPMailer::ENCRYPTION_STARTTLS
    );
    
    echo "1. Instancia de EmailService creada: ✓\n";
    echo "2. Configuración SMTP aplicada: ✓\n";
    echo "3. Validando email de destino ($EMAIL_DESTINO): ";
    
    if (filter_var($EMAIL_DESTINO, FILTER_VALIDATE_EMAIL)) {
        echo "✓\n";
    } else {
        throw new Exception("Email de destino inválido: $EMAIL_DESTINO");
    }
    
    echo "4. Intentando enviar email... ";
    
    $resultado = $emailService->enviar(
        $EMAIL_DESTINO,
        'Test PHPMailer - EmailService',
        'generico',
        [
            'contenido' => 'Este es un email de prueba para validar la configuración de PHPMailer.',
            'mensaje' => 'Si recibes este email, la configuración es correcta.'
        ]
    );
    
    if ($resultado) {
        echo "✓\n\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║  ✓ EMAIL ENVIADO EXITOSAMENTE                             ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";
        
        echo "Próximos pasos:\n";
        echo "  1. Revisar el email en: $EMAIL_DESTINO\n";
        echo "  2. Verificar que contenga branding de la empresa\n";
        echo "  3. Si no llega, revisar spam/junk\n";
        echo "  4. Guardar configuración en variables de entorno o .env\n\n";
        
        exit(0);
    } else {
        echo "✗\n\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║  ✗ FALLO AL ENVIAR EMAIL                                  ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";
        
        echo "Posibles causas:\n";
        echo "  • Credenciales SMTP incorrectas\n";
        echo "  • Host SMTP inaccesible desde el servidor\n";
        echo "  • Puerto SMTP bloqueado por firewall\n";
        echo "  • Email FROM no configurado en la empresa\n\n";
        
        echo "Soluciones a intentar:\n";
        echo "  1. Verificar credenciales:\n";
        echo "     - Usuario: $SMTP_USERNAME\n";
        echo "     - Contraseña: " . (strlen($SMTP_PASSWORD) > 0 ? "configurada" : "SIN CONFIGURAR") . "\n";
        echo "  2. Para Gmail: generar contraseña de aplicación\n";
        echo "  3. Revisar logs en: php_errors.log\n";
        echo "  4. Ejecutar: grep 'PHPMailer' php_errors.log\n\n";
        
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗\n\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ✗ EXCEPCIÓN DURANTE LA PRUEBA                            ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    
    echo "Checklist de solución:\n";
    echo "  ☐ ¿PhpMailer instalado? (composer require phpmailer/phpmailer)\n";
    echo "  ☐ ¿Credenciales SMTP correctas?\n";
    echo "  ☐ ¿Email de destino es válido?\n";
    echo "  ☐ ¿Permiso de escritura para logs?\n\n";
    
    exit(1);
}
?>
