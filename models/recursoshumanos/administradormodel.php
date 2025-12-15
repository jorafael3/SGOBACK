<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';
require_once __DIR__ . '/../../vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class AdministradorModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
    }




    function GetEmpleadosPorEmpresa($data = [])
    {
        try {


            $sql = "EXEC SGO_EMP_CARGAR_EMPLEADOS  @empresa = :empresa";

            $params = [

                ':empresa' => $data['empresa'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetEmpleadosPorEmpresa: " . $e->getMessage());
            error_log("Exception in GetEmpleadosPorEmpresa: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function getEmpleadoIndividual($data = [])
    {
        try {


            $sql = "EXEC SGO_EMP_CARGAR_EMPLEADOS_INDIVIDUAL  @empresa = :empresa ,  @cedula = :cedula";

            $params = [

                ':empresa' => $data['empresa'] ?? null,
                ':cedula' => $data['cedula'] ?? null,


            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en getEmpleadoIndividual: " . $e->getMessage());
            error_log("Exception in getEmpleadoIndividual: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    function GetSolicitudesCargasFamiliares($data = [])
    {
        try {


            $sql = "SELECT E.Nombre as Solicitado_por , c.Nombres as carga , c.Cedula , c.Edad , c.Sexo
            , c.FechaNacimiento ,c.Archivo_Cedula  , c.EmpleadoID , c.TipoCarga
            FROM EMP_EMPLEADOS_CARGAS_PREVIA C
            inner join EMP_EMPLEADOS E ON C.EmpleadoID = E.ID
            WHERE Estado = 0";

            $params = [


            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetSolicitudesCargasFamiliares: " . $e->getMessage());
            error_log("Exception in GetSolicitudesCargasFamiliares: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function AprobarCargaFamiliar($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("AprobarCargaFamiliar - Datos recibidos: " . json_encode($data));

            $sql = "EXEC SGO_EMP_CARGAR_INSERT_APROBAR
                @empleadoId       = :empleadoId,
                @cedula           = :cedula,
                @edad             = :edad,
                @fechaNacimiento  = :fechaNacimiento,
                @nombres          = :nombres,
                @sexo             = :sexo,
                @usuario  = :usuario,
                @TipoCarga  = :TipoCarga";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':cedula' => $data['cedula'] ?? null,
                ':edad' => $data['edad'] ?? null,
                ':fechaNacimiento' => $data['fechaNacimiento'] ?? null,
                ':nombres' => $data['nombres'] ?? null,
                ':sexo' => $data['sexo'] ?? null,
                ':usuario' => $data['usuario'] ?? null,
                ':TipoCarga' => $data['TipoCarga'] ?? null,
            ];

            error_log("AprobarCargaFamiliar - SQL: " . $sql);
            error_log("AprobarCargaFamiliar - Params: " . json_encode($params));

            $stmt = $this->query($sql, $params);

            error_log("AprobarCargaFamiliar - Resultado exitoso");
            error_log("AprobarCargaFamiliar - Respuesta: " . json_encode($stmt));

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Carga familiar aprobada exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];

            $this->logError("Error en AprobarCargaFamiliar: " . json_encode($errorDetails));
            error_log("=== ERROR DETALLADO en AprobarCargaFamiliar ===");
            error_log("Mensaje: " . $e->getMessage());
            error_log("Código: " . $e->getCode());
            error_log("Archivo: " . $e->getFile() . " (Línea: " . $e->getLine() . ")");
            error_log("Datos enviados: " . json_encode($data));
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("=== FIN ERROR DETALLADO ===");

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }




    function RechazarCargaFamiliar($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("RechazarCargaFamiliar - Datos recibidos: " . json_encode($data));

            $sql = "DELETE FROM EMP_EMPLEADOS_CARGAS_PREVIA WHERE EmpleadoID = :empleadoId     
            and Cedula = :cedula and Nombres = :nombres";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':cedula' => $data['cedula'] ?? null,
                ':nombres' => $data['nombres'] ?? null,
            ];

            $stmt = $this->query($sql, $params);

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Carga familiar rechazada exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }




    function GetSolicitudesEnfermedades($data = [])
    {
        try {


            $sql = "SELECT e.Nombre as Empleado , e.Cédula as Cedula , ef.alergias
            , ef.contactoEmergenciaNombre , ef.contactoEmergenciaRelacion,
            ef.contactoEmergenciaTelefono , ef.enfermedades , ef.Editado 
            , ef.tieneAlergia , ef.tieneEnfermedad , ef.tieneDiscapacidad , ef.porcentajeDiscapacidad ,
            ef.tipoDiscapacidad , ef.archivoDiscapacidadNombre , Tipo_solicitud   , ef.EmpleadoID 
            from SGO_EMP_DATOS_MEDICOS_EMPLEADOS Ef 
            inner join EMP_EMPLEADOS e on ef.EmpleadoID = e.ID
            where Ef.Estado = 0";

            $params = [


            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetSolicitudesEnfermedades: " . $e->getMessage());
            error_log("Exception in GetSolicitudesEnfermedades: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function AprobarDatosMedicos($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("AprobarDatosMedicos - Datos recibidos: " . json_encode($data));

            $sql = "UPDATE SGO_EMP_DATOS_MEDICOS_EMPLEADOS SET Estado = 1
            where EmpleadoID = :empleadoId ";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
            ];

            $stmt = $this->query($sql, $params);


            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Carga familiar aprobada exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];

            $this->logError("Error en AprobarCargaFamiliar: " . json_encode($errorDetails));

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }



    function RechazarDatosMedicos($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("RechazarDatosMedicos - Datos recibidos: " . json_encode($data));

            $sql = "DELETE FROM SGO_EMP_DATOS_MEDICOS_EMPLEADOS WHERE EmpleadoID = :empleadoId";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
            ];

            $stmt = $this->query($sql, $params);

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Datos medicos rechazados exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }




    function GetSolicitudesDatosPersonales($data = [])
    {
        try {


            $sql = "SGO_EMP_DATOS_PERSONALES_APROBAR @empresa = :empresa";

            $params = [

                ':empresa' => $data['empresa'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetSolicitudesDatosPersonales: " . $e->getMessage());
            error_log("Exception in GetSolicitudesDatosPersonales: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function AprobarDatosPersonales($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("AprobarDatosPersonales - Datos recibidos: " . json_encode($data));

            $sql = "EXEC SGO_EMP_APROBAR_DATOS
                        
            @empleadoId = :empleadoId,
            @direccion = :direccion,
            @email = :email,
            @estadoCivil = :estadoCivil,
            @telefono = :telefono,
            @usuario = :usuario,
            @empresa = :empresa";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':direccion' => $data['direccion'] ?? null,
                ':email' => $data['email'] ?? null,
                ':estadoCivil' => $data['estadoCivil'] ?? null,
                ':telefono' => $data['telefono'] ?? null,
                ':usuario' => $data['usuario'] ?? null,
                ':empresa' => $data['empresa'] ?? null,
            ];

            error_log("AprobarDatosPersonales - SQL: " . $sql);
            error_log("AprobarDatosPersonales - Params: " . json_encode($params));

            $stmt = $this->query($sql, $params);

            error_log("AprobarDatosPersonales - Resultado exitoso");
            error_log("AprobarDatosPersonales - Respuesta: " . json_encode($stmt));

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Datos personales aprobados exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];

            $this->logError("Error en AprobarCargaFamiliar: " . json_encode($errorDetails));

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }




    function RechazarDatosPersonales($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("RechazarDatosPersonales - Datos recibidos: " . json_encode($data));

            $sql = "DELETE FROM SGO_EMPLEADOS_VALIDACIONES 
            WHERE EmpleadoID = :empleadoId";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
            ];

            $stmt = $this->query($sql, $params);

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Datos medicos rechazados exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }



    function GetSolicitudesEstudios($data = [])
    {
        try {


            $sql = "SELECT ep.Nombre , es.EmpleadoID , es.institucion , es.titulo
            , es.anio , es.titulo_pdf as Documento , es.Tipo_solicitud ,
            ep.Código as Cedula
            from SGO_EMP_EMPLEADOS_ESTUDIOS es 
            inner join EMP_EMPLEADOS ep on es.EmpleadoID = ep.ID
            where es.Aprobado = 0";

            $params = [


            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetSolicitudesEstudios: " . $e->getMessage());
            error_log("Exception in GetSolicitudesEstudios: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function AprobarEstudio($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("AprobarEstudio - Datos recibidos: " . json_encode($data));

            $sql = "UPDATE SGO_EMP_EMPLEADOS_ESTUDIOS set Aprobado = 1 , Aprobado_por = :usuario 
            , Aprobado_date = GETDATE() 
            where EmpleadoID = :empleadoId and titulo = :titulo and institucion = :institucion";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':usuario' => $data['usuario'] ?? null,
                ':titulo' => $data['titulo'] ?? null,
                ':institucion' => $data['institucion'] ?? null

            ];

            error_log("AprobarEstudio - SQL: " . $sql);
            error_log("AprobarEstudio - Params: " . json_encode($params));

            $stmt = $this->query($sql, $params);

            error_log("AprobarEstudio - Resultado exitoso");

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Estudio aprobado exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            $this->logError("Error en AprobarEstudio: " . $e->getMessage());
            error_log("Exception in AprobarEstudio: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    function RechazarEstudio($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("RechazarEstudio - Datos recibidos: " . json_encode($data));

            $sql = "DELETE FROM SGO_EMP_EMPLEADOS_ESTUDIOS 
            where EmpleadoID = :empleadoId and titulo = :titulo and institucion = :institucion";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':institucion' => $data['institucion'] ?? null,
                ':titulo' => $data['titulo'] ?? null

            ];

            error_log("RechazarEstudio - SQL: " . $sql);
            error_log("RechazarEstudio - Params: " . json_encode($params));

            $stmt = $this->query($sql, $params);

            error_log("RechazarEstudio - Resultado exitoso");

            // Enviar correo de notificación al empleado
            $empleadoId = $data['empleadoId'] ?? null;
            if ($empleadoId) {
                $resultadoCorreo = $this->enviarcorreorechazo($empleadoId);
                error_log("RechazarEstudio - Resultado envío correo: " . json_encode($resultadoCorreo));
            }

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Estudio rechazado exitosamente',
                'data' => $stmt,
                'correoEnviado' => $resultadoCorreo ?? ['success' => false, 'error' => 'No se pudo enviar correo']
            ];

        } catch (Exception $e) {
            $this->logError("Error en RechazarEstudio: " . $e->getMessage());
            error_log("Exception in RechazarEstudio: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }





    function enviarcorreorechazo($empleadoId)
    {
        try {
            // Obtener datos del empleado (email y nombre)
            $sqlEmpleado = "SELECT Nombre, email_personal FROM EMP_EMPLEADOS WHERE ID = :empleadoId";
            $resultEmpleado = $this->query($sqlEmpleado, [':empleadoId' => $empleadoId]);

            if (!$resultEmpleado || !$resultEmpleado['success'] || empty($resultEmpleado['data'])) {
                return [
                    'success' => false,
                    'error' => 'No se encontró el empleado con ID: ' . $empleadoId
                ];
            }

            $empleado = $resultEmpleado['data'][0];
            $empleadoNombre = $empleado['Nombre'] ?? 'Empleado';
            $empleadoEmail = $empleado['email_personal'] ?? null;

            // Validar que el empleado tenga email
            if (!$empleadoEmail) {
                return [
                    'success' => false,
                    'error' => 'El empleado no tiene un email registrado'
                ];
            }

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

            // Remitente y destinatario
            $mail->setFrom('sgoinfocorreo@gmail.com', 'Sistema SGO - Recursos Humanos');
            $mail->addAddress($empleadoEmail, $empleadoNombre);

            // Obtener fecha y hora actual
            date_default_timezone_set('America/Guayaquil');
            $fechaHora = date('d/m/Y H:i:s');

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Solicitud de Estudio Rechazada - Sistema SGO';

            // Construir el cuerpo del mensaje HTML
            $htmlBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #dc3545; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { padding: 20px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
                    .field { margin: 15px 0; padding: 10px; background-color: white; border-left: 3px solid #dc3545; }
                    .label { font-weight: bold; color: #333; display: block; margin-bottom: 5px; }
                    .value { color: #666; }
                    .alert-box { background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 15px 0; border-radius: 5px; color: #721c24; }
                    .footer { margin-top: 20px; padding: 15px; background-color: #f1f1f1; text-align: center; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>❌ SOLICITUD DE ESTUDIO RECHAZADA</h2>
                    </div>
                    <div class='content'>
                        <div class='alert-box'>
                            <strong>Estimado/a " . htmlspecialchars($empleadoNombre) . ",</strong><br><br>
                            Lamentamos informarle que su solicitud de registro de estudio ha sido <strong>RECHAZADA</strong> por el departamento de Recursos Humanos.
                        </div>
                        
                        <div class='field'>
                            <span class='label'>Fecha de Notificación:</span>
                            <span class='value'>" . $fechaHora . "</span>
                        </div>
                        
                        <div class='alert-box'>
                            <strong>Motivos posibles del rechazo:</strong><br>
                            <ul style='margin: 10px 0; padding-left: 20px;'>
                                <li>Documentación incompleta o ilegible</li>
                                <li>Información incorrecta o inconsistente</li>
                                <li>Falta de certificados o documentos de respaldo</li>
                                <li>El título no cumple con los requisitos establecidos</li>
                            </ul>
                        </div>
                        
                        <div class='field'>
                            <span class='label'>¿Qué puede hacer?</span>
                            <span class='value'>
                                Si considera que su solicitud fue rechazada por error o desea volver a presentarla con la documentación correcta, 
                                por favor comuníquese con el departamento de Recursos Humanos o vuelva a registrar su estudio con la información correcta.
                            </span>
                        </div>
                    </div>
                    <div class='footer'>
                        Este es un mensaje automático del Sistema de Gestión SGO.<br>
                        Para más información, contacte al departamento de Recursos Humanos.
                    </div>
                </div>
            </body>
            </html>
            ";

            // Texto plano alternativo
            $mail->Body = $htmlBody;
            $mail->AltBody = "SOLICITUD DE ESTUDIO RECHAZADA\n\n" .
                "Estimado/a " . $empleadoNombre . ",\n\n" .
                "Lamentamos informarle que su solicitud de registro de estudio ha sido RECHAZADA por el departamento de Recursos Humanos.\n\n" .
                "Fecha de Notificación: " . $fechaHora . "\n\n" .
                "Motivos posibles del rechazo:\n" .
                "- Documentación incompleta o ilegible\n" .
                "- Información incorrecta o inconsistente\n" .
                "- Falta de certificados o documentos de respaldo\n" .
                "- El título no cumple con los requisitos establecidos\n\n" .
                "Si considera que su solicitud fue rechazada por error o desea volver a presentarla con la documentación correcta, " .
                "por favor comuníquese con el departamento de Recursos Humanos o vuelva a registrar su estudio con la información correcta.\n\n" .
                "Este es un mensaje automático del Sistema de Gestión SGO.";

            // Enviar correo
            $mail->send();

            return [
                'success' => true,
                'message' => 'Correo de rechazo enviado correctamente a ' . $empleadoEmail
            ];

        } catch (Exception $e) {
            $this->logError("Error en enviarcorreorechazo: " . $e->getMessage());
            error_log("Exception in enviarcorreorechazo: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al enviar el correo: ' . $e->getMessage()
            ];
        }
    }



}
