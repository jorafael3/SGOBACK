<?php
/**
 * EmailService - Servicio simple de envío de emails con PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class EmailService
{
    private $mail;
    private $templatesPath;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->templatesPath = __DIR__ . '/../email-templates/';
        
        // Configuración SMTP
        $this->mail->isSMTP();
        $this->mail->Host = 'mail.cartimex.com';
        $this->mail->Port = 465;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'facturacion';
        $this->mail->Password = 'nubedealgodon$22';
        $this->mail->CharSet = 'UTF-8';
        $this->mail->isHTML(true);
    }

    /**
     * Envía un email simple
     * 
     * @param array|string $destinatarios - Email o array de emails
     * @param string $asunto - Asunto del email
     * @param array $datos - Array con datos para la plantilla
     * @param string $plantilla - Nombre de la plantilla (sin .html)
     * @param string $htmlCustom - [Opcional] HTML personalizado
     * @param string $nombreRemitente - [Opcional] Nombre del remitente (default: 'SGO_')
     * @param array $documentos - [Opcional] Array de rutas de archivos a adjuntar
     * @param string $empresa - [Opcional] Empresa para header/footer (default: 'sgo')
     * @return bool
     */
    public function enviar($destinatarios, $asunto, $datos = [], $plantilla = 'generico', $htmlCustom = null, $nombreRemitente = 'SGO_', $documentos = [], $empresa = 'sgo')
    {
        try {
            // Limpiar destinatarios previos
            $this->mail->clearAddresses();
            $this->mail->clearCCs();
            $this->mail->clearBCCs();
            $this->mail->clearAttachments();
            
            // Setear remitente
            $this->mail->setFrom('info@sgo.com', "SGO ".$nombreRemitente);
            
            // Agregar destinatarios
            if (is_string($destinatarios)) {
                $destinatarios = [$destinatarios];
            }
            
            if (is_array($destinatarios)) {
                foreach ($destinatarios as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->mail->addAddress($email);
                    }
                }
            }
            
            // Si no hay destinatarios válidos, retornar false
            if (empty($this->mail->getToAddresses())) {
                error_log("[EmailService] No hay destinatarios válidos");
                return false;
            }
            
            // Asunto
            $this->mail->Subject = $asunto;
            
            // Obtener HTML
            if ($htmlCustom) {
                $html = $htmlCustom;
            } else {
                $html = $this->cargarPlantilla($plantilla, $datos, strtolower($empresa));
            }
            
            // Body
            $this->mail->Body = $html;
            $this->mail->AltBody = strip_tags($html);
            
            // Agregar documentos/archivos adjuntos
            if (!empty($documentos)) {
                $this->agregarAdjuntos($documentos);
            }
            
            // Enviar
            $resultado = $this->mail->send();
            
            if ($resultado) {
                error_log("[EmailService] Email enviado a: " . implode(', ', array_column($this->mail->getToAddresses(), '0')) . " - Asunto: $asunto");
            } else {
                error_log("[EmailService] Error: " . $this->mail->ErrorInfo);
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("[EmailService] Excepción: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Carga una plantilla y reemplaza variables
     * 
     * @param string $plantilla - Nombre de la plantilla
     * @param array $datos - Datos para reemplazar
     * @param string $empresa - Empresa para header/footer
     * @return string - HTML final
     */
    private function cargarPlantilla($plantilla, $datos = [], $empresa = 'sgo')
    {
        $archivo = $this->templatesPath . $plantilla . '.html';
        
        if (!file_exists($archivo)) {
            throw new Exception("Plantilla no encontrada: $archivo");
        }
        
        $html = file_get_contents($archivo);
        
        // Agregar header y footer según la empresa
        $header = $this->generarHeader($empresa);
        $footer = $this->generarFooter($empresa);
        $html = $header . $html . $footer;
        
        // Reemplazar variables {{variable}}
        foreach ($datos as $clave => $valor) {
            // Convertir valores no-string
            if (is_array($valor) || is_object($valor)) {
                $valor = json_encode($valor);
            } elseif (!is_string($valor)) {
                $valor = (string)$valor;
            }
            
            $html = str_replace('{{' . $clave . '}}', $valor, $html);
        }
        
        return $html;
    }

    /**
     * Configura credenciales SMTP
     */
    public function configurarSMTP($host, $port, $usuario, $password, $secure = PHPMailer::ENCRYPTION_STARTTLS)
    {
        $this->mail->Host = $host;
        $this->mail->Port = $port;
        $this->mail->SMTPSecure = $secure;
        $this->mail->Username = $usuario;
        $this->mail->Password = $password;
    }

    /**
     * Agrega archivos adjuntos al email
     * 
     * @param array $documentos - Array de rutas de archivos
     * @return void
     */
    private function agregarAdjuntos($documentos)
    {
        if (!is_array($documentos)) {
            $documentos = [$documentos];
        }
        
        foreach ($documentos as $archivo) {
            if (file_exists($archivo)) {
                try {
                    $this->mail->addAttachment($archivo);
                    error_log("[EmailService] Adjunto agregado: $archivo");
                } catch (Exception $e) {
                    error_log("[EmailService] Error al agregar adjunto: " . $e->getMessage());
                }
            } else {
                error_log("[EmailService] Archivo no encontrado: $archivo");
            }
        }
    }

    /**
     * Genera header personalizado según la empresa
     * 
     * @param string $empresa - Nombre de la empresa
     * @return string - HTML del header
     */
    private function generarHeader($empresa)
    {
        $empresa = strtolower($empresa);
        
        $headers = [
            'cartimex' => '
                <div style="background: linear-gradient(135deg, #1a5490 0%, #2980b9 100%); padding: 0; margin: 0; text-align: center;">
                    <!-- Logo y Header -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #1a5490 0%, #2980b9 100%);">
                        <tr>
                            <td style="padding: 30px 20px; text-align: center;">
                                <img src="https://www.cartimex.com/assets/img/logo200.png" alt="CARTIMEX" style="max-width: 150px; height: auto; margin-bottom: 15px;">
                                <h1 style="color: #ffffff; margin: 0 0 10px 0; font-size: 32px; font-weight: bold;">CARTIMEX</h1>
                                <p style="color: #ecf0f1; margin: 0; font-size: 14px; font-weight: 300;">Comercializadora Internacional de Servicios</p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style="background-color: #ffffff; height: 4px; background: linear-gradient(90deg, #1a5490 0%, #e74c3c 100%);"></div>
            ',
            'computron' => '
                <div style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); padding: 0; margin: 0; text-align: center;">
                    <!-- Logo y Header -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);">
                        <tr>
                            <td style="padding: 30px 20px; text-align: center;">
                                <img src="https://www.computron.com.ec/wp-content/uploads/2021/01/LOGO-EDITABLE.webp" alt="COMPUTRON" style="max-width: 150px; height: auto; margin-bottom: 15px;">
                                <h1 style="color: #3498db; margin: 0 0 10px 0; font-size: 32px; font-weight: bold;">COMPUTRON</h1>
                                <p style="color: #95a5a6; margin: 0; font-size: 14px; font-weight: 300;">Soluciones Tecnológicas Integradas</p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style="background-color: #ffffff; height: 4px; background: linear-gradient(90deg, #2c3e50 0%, #3498db 100%);"></div>
            '
        ];
        
        return isset($headers[$empresa]) ? $headers[$empresa] : $this->generarHeaderDefault();
    }

    /**
     * Genera header por defecto
     * 
     * @return string - HTML del header
     */
    private function generarHeaderDefault()
    {
        return '
            <div style="background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%); padding: 0; margin: 0; text-align: center;">
                <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);">
                    <tr>
                        <td style="padding: 30px 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0 0 10px 0; font-size: 32px; font-weight: bold;">SGO</h1>
                            <p style="color: #bdc3c7; margin: 0; font-size: 14px; font-weight: 300;">Sistema de Gestión Operacional</p>
                        </td>
                    </tr>
                </table>
            </div>
            <div style="background-color: #ffffff; height: 4px; background: linear-gradient(90deg, #34495e 0%, #16a085 100%);"></div>
        ';
    }

    /**
     * Genera footer personalizado según la empresa
     * 
     * @param string $empresa - Nombre de la empresa
     * @return string - HTML del footer
     */
    private function generarFooter($empresa)
    {
        $empresa = strtolower($empresa);
        
        $footers = [
            'cartimex' => '<div style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #ddd; margin-top: 30px;">
                <p style="margin: 5px 0; color: #666; font-size: 12px;"><strong>CARIEX</strong></p>
                <p style="margin: 5px 0; color: #888; font-size: 11px;">Teléfono: +506 XXXX-XXXX | Email: info@cariex.com</p>
                <p style="margin: 5px 0; color: #aaa; font-size: 10px;">© 2025 CARIEX. Todos los derechos reservados.</p>
            </div>',
            'computron' => '<div style="background-color: #ecf0f1; padding: 20px; text-align: center; border-top: 1px solid #bdc3c7; margin-top: 30px;">
                <p style="margin: 5px 0; color: #2c3e50; font-size: 12px;"><strong>COMPUTRON</strong></p>
                <p style="margin: 5px 0; color: #34495e; font-size: 11px;">Teléfono: +506 XXXX-XXXX | Email: soporte@computron.com</p>
                <p style="margin: 5px 0; color: #7f8c8d; font-size: 10px;">© 2025 COMPUTRON. Todos los derechos reservados.</p>
            </div>',
            'sgo' => '<div style="background-color: #ecf0f1; padding: 20px; text-align: center; border-top: 1px solid #bdc3c7; margin-top: 30px;">
                <p style="margin: 5px 0; color: #34495e; font-size: 12px;"><strong>SGO - Sistema de Gestión Operacional</strong></p>
                <p style="margin: 5px 0; color: #7f8c8d; font-size: 11px;">Teléfono: +506 XXXX-XXXX | Email: soporte@sgo.com</p>
                <p style="margin: 5px 0; color: #95a5a6; font-size: 10px;">© 2025 SGO. Todos los derechos reservados.</p>
            </div>'
        ];
        
        return isset($footers[$empresa]) ? $footers[$empresa] : $footers['sgo'];
    }
}

