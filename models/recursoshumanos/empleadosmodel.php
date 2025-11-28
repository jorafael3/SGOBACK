<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmpleadosModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
    }


    function getDatosPersonales($data = [])
    {
        try {


            $sql = "
               select e.Código as Cedula , e.Nombre , e.Dirección , e.Teléfono3
                , e.FechaNac , j.Nombre as Jefe, e.email , d.Nombre , e.EstadoCivil ,
                CASE WHEN e.PDecimos = 1 THEN 'SI' ELSE 'NO' END AS PDecimos,
                CASE WHEN e.ProvisionaFR = 1 THEN 'SI' ELSE 'NO' END AS PFondos
                from EMP_EMPLEADOS e
                inner join EMP_EMPLEADOS j ON e.PadreID = j.ID
                inner join SIS_DEPARTAMENTOS D on E.DepartamentoID = D.ID
                WHERE e.ID = :empleadoId
                ";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en getDatosPersonales: " . $e->getMessage());
            error_log("Exception in getDatosPersonales: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function GetCargasFamiliares($data = [])
    {
        try {


            $sql = "
               SELECT * FROM EMP_EMPLEADOS_CARGAS WHERE EmpleadoID = :empleadoId
                ";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetCargasFamiliares: " . $e->getMessage());
            error_log("Exception in GetCargasFamiliares: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }





    function GetVacaciones($data = [])
    {
        try {


            $sql = "EXEC  CARTIMEX..SGO_Reporte_Vacaciones_Tomadas :empleadoId";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetVacaciones: " . $e->getMessage());
            error_log("Exception in GetVacaciones: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    function SolicitudActualizacionDatos($data = [])
    {
        try {
            $mail = new PHPMailer(true);

            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'sgoinfocorreo@gmail.com';
            $mail->Password = 'csxj xbqb uncn yuuc';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';

            // Destinatario
            $mail->setFrom('sgoinfocorreo@gmail.com', 'Sistema SGO');
            $mail->addAddress('nadelaese@gmail.com');

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Solicitud de Actualización de Datos - ' . ($data['tipoSolicitud'] ?? 'N/A');

            // Construir el cuerpo del mensaje
            $htmlBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; background-color: #f9f9f9; }
                    .field { margin: 10px 0; }
                    .label { font-weight: bold; color: #333; }
                    .value { color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Solicitud de Actualización de Datos</h2>
                    </div>
                    <div class='content'>
                        <div class='field'>
                            <span class='label'>Empleado ID:</span>
                            <span class='value'>" . htmlspecialchars($data['empleadoId'] ?? 'N/A') . "</span>
                        </div>
                        <div class='field'>
                            <span class='label'>Nombre:</span>
                            <span class='value'>" . htmlspecialchars($data['empleadoNombre'] ?? 'N/A') . "</span>
                        </div>
                        <div class='field'>
                            <span class='label'>Tipo de Solicitud:</span>
                            <span class='value'>" . htmlspecialchars($data['tipoSolicitud'] ?? 'N/A') . "</span>
                        </div>
                        <div class='field'>
                            <span class='label'>Campo a Actualizar:</span>
                            <span class='value'>" . htmlspecialchars($data['campoActualizar'] ?? 'N/A') . "</span>
                        </div>
                        <div class='field'>
                            <span class='label'>Valor Correcto:</span>
                            <span class='value'>" . htmlspecialchars($data['valorCorrecto'] ?? 'N/A') . "</span>
                        </div>
                        <div class='field'>
                            <span class='label'>Motivo:</span>
                            <span class='value'>" . htmlspecialchars($data['motivo'] ?? 'N/A') . "</span>
                        </div>
                        <div class='field'>
                            <span class='label'>Fecha de Solicitud:</span>
                            <span class='value'>" . htmlspecialchars($data['fechaSolicitud'] ?? 'N/A') . "</span>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->Body = $htmlBody;
            $mail->AltBody = "Solicitud de Actualización de Datos\n\n" .
                "Empleado ID: " . ($data['empleadoId'] ?? 'N/A') . "\n" .
                "Nombre: " . ($data['empleadoNombre'] ?? 'N/A') . "\n" .
                "Tipo: " . ($data['tipoSolicitud'] ?? 'N/A') . "\n" .
                "Campo: " . ($data['campoActualizar'] ?? 'N/A') . "\n" .
                "Valor: " . ($data['valorCorrecto'] ?? 'N/A') . "\n" .
                "Motivo: " . ($data['motivo'] ?? 'N/A') . "\n" .
                "Fecha: " . ($data['fechaSolicitud'] ?? 'N/A');

            // Enviar correo
            $mail->send();

            return [
                'success' => true,
                'message' => 'Solicitud enviada correctamente',
                'data' => [
                    'empleadoId' => $data['empleadoId'] ?? null,
                    'tipoSolicitud' => $data['tipoSolicitud'] ?? null,
                    'fechaSolicitud' => $data['fechaSolicitud'] ?? null
                ]
            ];

        } catch (Exception $e) {
            $this->logError("Error en SolicitudActualizacionDatos: " . $e->getMessage());
            error_log("Exception in SolicitudActualizacionDatos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al enviar la solicitud: ' . $mail->ErrorInfo
            ];
        }
    }

}
