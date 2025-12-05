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


            $sql = "SELECT e.Código as Cedula , e.Nombre , VD.Direccion as Dirección , VD.Telefono as Teléfono3
                , e.FechaNac , j.Nombre as Jefe, VD.email , e.email_personal , d.Nombre , e.EstadoCivil ,
                CASE WHEN e.PDecimos = 1 THEN 'SI' ELSE 'NO' END AS PDecimos,
                CASE WHEN e.ProvisionaFR = 1 THEN 'SI' ELSE 'NO' END AS PFondos , isnull(vd.Estado, 0) as Estado 
				, FU.Nombre AS Cargo , e.Genero ,  isnull(e.foto_perfil, '') as foto_perfil 
                from EMP_EMPLEADOS e
                inner join EMP_EMPLEADOS j ON e.PadreID = j.ID
                inner join SIS_DEPARTAMENTOS D on E.DepartamentoID = D.ID
				left outer join SGO_EMPLEADOS_VALIDACIONES VD on vd.EmpleadoID = e.ID
				inner join EMP_FUNCIONES FU ON FU.ID = E.FunciónID
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

            SELECT 
                CASE 
                    WHEN Estado = 0 THEN 'PENDIENTE DE APROBACION'
                    WHEN Estado = 1 THEN 'APROBADO'
                    ELSE 'DESCONOCIDO'
                END AS EstadoDescripcion,
                *
            FROM EMP_EMPLEADOS_CARGAS_PREVIA
            WHERE EmpleadoID =  :empleadoId

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



    function enviarAlertaCambio($empleadoId, $seccion, $usuarioModificador, $empleadoNombre = null, $camposModificados = [])
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
            // $mail->addAddress('cpincay@cartimex.com');

            // Obtener fecha y hora actual
            date_default_timezone_set('America/Guayaquil');
            $fechaHora = date('d/m/Y H:i:s');

            // Si no se proporcionó el nombre del empleado, intentar obtenerlo
            if (!$empleadoNombre) {
                $sqlNombre = "SELECT Nombre as NombreCompleto FROM EMP_EMPLEADOS WHERE ID = :empleadoId";
                $resultNombre = $this->query($sqlNombre, [':empleadoId' => $empleadoId]);
                $empleadoNombre = $resultNombre['data'][0]['NombreCompleto'] ?? 'Empleado ID: ' . $empleadoId;
            }

            // Construir HTML de campos modificados
            $camposHtml = '';
            if (!empty($camposModificados)) {
                $camposHtml = "<div class='field'><span class='label'>Campos Modificados:</span><ul style='margin: 5px 0; padding-left: 20px;'>";
                foreach ($camposModificados as $campo => $valor) {
                    $camposHtml .= "<li><strong>" . htmlspecialchars($campo) . ":</strong> " . htmlspecialchars($valor) . "</li>";
                }
                $camposHtml .= "</ul></div>";
            }

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Alerta: Modificación de ' . $seccion . ' - ' . $empleadoNombre;

            // Construir el cuerpo del mensaje
            $htmlBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #FF9800; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { padding: 20px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
                    .field { margin: 15px 0; padding: 10px; background-color: white; border-left: 3px solid #FF9800; }
                    .label { font-weight: bold; color: #333; display: block; margin-bottom: 5px; }
                    .value { color: #666; }
                    .alert-box { background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 5px; }
                    .footer { margin-top: 20px; padding: 15px; background-color: #f1f1f1; text-align: center; font-size: 12px; color: #666; }
                    ul { color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>⚠️ ALERTA DE MODIFICACIÓN DE DATOS</h2>
                    </div>
                    <div class='content'>
                        <div class='alert-box'>
                            <strong>Se ha modificado información de un empleado en el sistema.</strong>
                        </div>
                        
                        <div class='field'>
                            <span class='label'>Empleado:</span>
                            <span class='value'>" . htmlspecialchars($empleadoNombre) . "</span>
                        </div>
                        
                        <div class='field'>
                            <span class='label'>ID Empleado:</span>
                            <span class='value'>" . htmlspecialchars($empleadoId) . "</span>
                        </div>
                        
                        <div class='field'>
                            <span class='label'>Sección Modificada:</span>
                            <span class='value'><strong>" . htmlspecialchars($seccion) . "</strong></span>
                        </div>
                        
                        " . $camposHtml . "
                        
                        <div class='field'>
                            <span class='label'>Modificado por:</span>
                            <span class='value'>" . htmlspecialchars($usuarioModificador) . "</span>
                        </div>
                        
                        <div class='field'>
                            <span class='label'>Fecha y Hora:</span>
                            <span class='value'>" . $fechaHora . "</span>
                        </div>
                        
                        <div class='alert-box'>
                            <strong>Acción Requerida:</strong><br>
                            Por favor, valide la información editada en el sistema para verificar que los cambios sean correctos y estén autorizados.
                        </div>
                    </div>
                    <div class='footer'>
                        Este es un mensaje automático del Sistema de Gestión SGO.<br>
                        No responda a este correo.
                    </div>
                </div>
            </body>
            </html>
            ";

            // Construir texto plano de campos modificados
            $camposTexto = '';
            if (!empty($camposModificados)) {
                $camposTexto = "\nCampos Modificados:\n";
                foreach ($camposModificados as $campo => $valor) {
                    $camposTexto .= "  - " . $campo . ": " . $valor . "\n";
                }
            }

            $mail->Body = $htmlBody;
            $mail->AltBody = "ALERTA DE MODIFICACIÓN DE DATOS\n\n" .
                "Empleado: " . $empleadoNombre . "\n" .
                "ID Empleado: " . $empleadoId . "\n" .
                "Sección Modificada: " . $seccion . "\n" .
                $camposTexto .
                "Modificado por: " . $usuarioModificador . "\n" .
                "Fecha y Hora: " . $fechaHora . "\n\n" .
                "Por favor, valide la información editada en el sistema.";

            // Enviar correo
            $mail->send();

            return [
                'success' => true,
                'message' => 'Alerta enviada correctamente'
            ];

        } catch (Exception $e) {
            $this->logError("Error en enviarAlertaCambio: " . $e->getMessage());
            error_log("Exception in enviarAlertaCambio: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al enviar la alerta: ' . $mail->ErrorInfo
            ];
        }
    }




    // function ActualizarDatosPersonales($data = [])
    // {
    //     try {
    //         // Desactivar transacciones implícitas
    //         $this->query("SET IMPLICIT_TRANSACTIONS OFF", []);

    //         $sql = "UPDATE EMP_EMPLEADOS 
    //                 SET Dirección = :direccion, 
    //                     EstadoCivil = :estadoCivil, 
    //                     Teléfono3 = :telefono, 
    //                     email = :email,
    //                     FechaNac = :fechaNac
    //                 WHERE ID = :empleadoId";

    //         $params = [

    //             ':empleadoId' => $data['empleadoId'] ?? null,
    //             ':direccion' => $data['Dirección'] ?? null,
    //             ':estadoCivil' => $data['EstadoCivil'] ?? null,
    //             ':telefono' => $data['Teléfono3'] ?? null,
    //             ':email' => $data['email'] ?? null,
    //             ':fechaNac' => $data['FechaNac'] ?? null
    //         ];

    //         // IMPORTANTE: Usar execute() para que se guarden los cambios
    //         $this->db->execute($sql, $params);

    //         // Preparar campos modificados para el email
    //         $camposModificados = [];
    //         if (isset($data['Dirección']))
    //             $camposModificados['Dirección'] = $data['Dirección'];
    //         if (isset($data['EstadoCivil']))
    //             $camposModificados['Estado Civil'] = $data['EstadoCivil'];
    //         if (isset($data['Teléfono3']))
    //             $camposModificados['Teléfono'] = $data['Teléfono3'];
    //         if (isset($data['email']))
    //             $camposModificados['Email'] = $data['email'];
    //         if (isset($data['FechaNac']))
    //             $camposModificados['Fecha de Nacimiento'] = $data['FechaNac'];

    //         // Enviar alerta de cambio
    //         $usuarioModificador = $data['Creado_Por'] ?? 'Sistema';
    //         $this->enviarAlertaCambio(
    //             $data['empleadoId'],
    //             'Información Personal',
    //             $usuarioModificador,
    //             null,
    //             $camposModificados
    //         );

    //         return [
    //             'success' => true,
    //             'message' => 'Datos actualizados correctamente PERSONALES'
    //         ];

    //     } catch (Exception $e) {
    //         $this->logError("Error en ActualizarDatosPersonales: " . $e->getMessage());
    //         error_log("Exception in ActualizarDatosPersonales: " . $e->getMessage());
    //         return [
    //             'success' => false,
    //             'error' => 'Error al actualizar: ' . $e->getMessage()
    //         ];
    //     }
    // }





    function ActualizarDatosPersonales($data = [])
    {
        try {
            // Desactivar transacciones implícitas
            $this->query("SET IMPLICIT_TRANSACTIONS OFF", []);

            // ============================
            //  FORMATEAR FECHA → AAAAMMDD
            // ============================
            $fechaBruta = $data['FechaNac'] ?? null;
            $fechaFormateada = null;

            if (!empty($fechaBruta)) {

                // Normalizar formatos tipo 2025/12/20 → 2025-12-20
                $fechaBruta = str_replace('/', '-', $fechaBruta);

                // Convertir a un formato reconocible por PHP
                $timestamp = strtotime($fechaBruta);

                if ($timestamp === false) {
                    throw new Exception("Formato de fecha inválido: " . $fechaBruta);
                }

                // Crear fecha segura
                $fechaObj = new DateTime(date('Y-m-d', $timestamp));

                // FORMATO FINAL → AAAAMMDD
                $fechaFormateada = $fechaObj->format('Ymd');
            }

            // ============================
            //       STORED PROCEDURE
            // ============================
            $sql = "EXEC SGO_EMP_REGISTRO_DATOS_TEMPORAL  
            :EmpleadoID,
            :Cedula,
            :Direccion,
            :EstadoCivil,
            :FechaNac,
            :Jefe,
            :Nombre,
            :PDecimos,
            :PFondos,
            :Telefono,
            :Email,
            :Estado";

            // Parámetros EXACTOS DEL SP
            $params = [
                ':EmpleadoID' => $data['empleadoId'] ?? null,
                ':Cedula' => $data['Cedula'] ?? null,
                ':Direccion' => $data['Dirección'] ?? null,
                ':EstadoCivil' => $data['EstadoCivil'] ?? null,
                ':FechaNac' => $fechaFormateada,   // ← FECHA YA FORMATEADA
                ':Jefe' => $data['Jefe'] ?? null,
                ':Nombre' => $data['Nombre'] ?? null,
                ':PDecimos' => $data['PDecimos'] ?? null,
                ':PFondos' => $data['PFondos'] ?? null,
                ':Telefono' => $data['Teléfono3'] ?? null,
                ':Email' => $data['email'] ?? null,
                ':Estado' => $data['Estado'] ?? 0
            ];

            // Ejecutar
            $this->db->execute($sql, $params);

            // ============================
            //      AUDITORÍA / ALERTA
            // ============================
            $camposModificados = [];
            foreach ($params as $campo => $valor) {
                $nombreCampo = str_replace(":", "", $campo);
                $camposModificados[$nombreCampo] = $valor;
            }

            $usuarioModificador = $data['Creado_Por'] ?? 'Sistema';

            $this->enviarAlertaCambio(
                $data['empleadoId'],
                'Validación de Datos Personales',
                $usuarioModificador,
                null,
                $camposModificados
            );

            return [
                'success' => true,
                'message' => 'Datos guardados correctamente en validación PERSONAL'
            ];

        } catch (Exception $e) {
            $this->logError("Error en ActualizarDatosPersonales: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al insertar: ' . $e->getMessage()
            ];
        }
    }





    function ActualizarCargasEmpleado($data = [])
    {
        try {
            // Desactivar transacciones implícitas para asegurar que se guarde
            $this->query("SET IMPLICIT_TRANSACTIONS OFF", []);

            // SQL corregido (Sin Ocupación)
            $sql = "INSERT INTO EMP_EMPLEADOS_CARGAS_PREVIA
        (
            EmpleadoID,
            Edad,
            Sexo,
            Nombres,
            SucursalID,
            PcID,
            CreadoPor,
            CreadoDate,
            FechaNacimiento,
            Cedula,
            TipoCarga,
            Carga,
            Archivo_Cedula
        )
        VALUES
        (
            :empleadoId,
            :Edad,
            :Sexo,
            :Nombres,
            '00',
            'SGO',
            :Creado_Por,
            GETDATE(),
            :FechaNacimiento,
            :cedula,
            :TipoCarga,
            1,
            :documento_carga
        )";

            // Parámetros
            $params = [
                ':empleadoId' => $data['empleadoId'] ?? null,
                ':Edad' => $data['Edad'] ?? null,
                ':Sexo' => $data['Sexo'] ?? null,
                ':Nombres' => $data['Nombres'] ?? null,
                ':FechaNacimiento' => $data['FechaNacimiento'] ?? null,
                ':cedula' => $data['cedula'] ?? null,
                ':TipoCarga' => $data['TipoCarga'] ?? null,
                ':Creado_Por' => $data['Creado_Por'] ?? 'SISTEMA',
                ':documento_carga' => $data['documento_carga'] ?? null,
            ];

            // Ejecutar
            $rows = $this->db->execute($sql, $params);

            // Asegurar commit explícito
            $this->query("COMMIT");

            // Validar si sí insertó
            if ($rows <= 0) {
                throw new Exception("El INSERT no insertó ninguna fila. Verifique los datos enviados.");
            }

            // Preparar campos modificados para el email
            $camposModificados = [];
            if (isset($data['Nombres']))
                $camposModificados['Nombre'] = $data['Nombres'];
            if (isset($data['TipoCarga']))
                $camposModificados['Tipo de Carga'] = $data['TipoCarga'];
            if (isset($data['Edad']))
                $camposModificados['Edad'] = $data['Edad'];
            if (isset($data['Sexo']))
                $camposModificados['Sexo'] = $data['Sexo'];
            if (isset($data['FechaNacimiento']))
                $camposModificados['Fecha de Nacimiento'] = $data['FechaNacimiento'];
            if (isset($data['cédula']))
                $camposModificados['Cédula'] = $data['cédula'];

            // Enviar alerta de cambio
            $usuarioModificador = $data['Creado_Por'] ?? 'Sistema';
            $this->enviarAlertaCambio(
                $data['empleadoId'],
                'Cargas Familiares',
                $usuarioModificador,
                null,
                $camposModificados
            );

            return [
                'success' => true,
                'message' => 'Carga familiar guardada correctamente'
            ];

        } catch (Exception $e) {

            $this->logError("Error en ActualizarCargasEmpleado: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => $data
            ];
        }
    }





    function ActualizarEstudios($data = [])
    {
        try {

            // SQL corregido (cédula con corchetes)
            $sql = "INSERT INTO SGO_EMP_EMPLEADOS_ESTUDIOS 
        (

            EmpleadoID,
            Titulo,
            Institucion,
            anio,
            titulo_pdf
           
        )
        VALUES
        (
            :empleadoId,
            :titulo,
            :institucion,
            :anio,
            :titulo_pdf

        )";

            // Parámetros
            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':titulo' => $data['titulo'] ?? null,
                ':institucion' => $data['institucion'] ?? null,
                ':anio' => $data['anio'] ?? null,
                ':titulo_pdf' => $data['titulo_pdf'] ?? null,

            ];

            // Ejecutar
            $rows = $this->db->execute($sql, $params);

            // Validar si sí insertó
            if ($rows <= 0) {
                throw new Exception("El INSERT no insertó ninguna fila. Verifique los datos enviados.");
            }

            return [
                'success' => true,
                'message' => 'Estudios guardados correctamente'
            ];

        } catch (Exception $e) {

            $this->logError("Error en ActualizarEstudios: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => $data
            ];
        }
    }




    function GetCargasEstudios($data = [])
    {
        try {


            $sql = "SELECT  EmpleadoID , institucion ,
             titulo , anio ,  isnull(titulo_pdf, '') as titulo_pdf 
			   from SGO_EMP_EMPLEADOS_ESTUDIOS
				where EmpleadoID = :empleadoId
                ";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetCargasEstudios: " . $e->getMessage());
            error_log("Exception in GetCargasEstudios: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function ActualizarEnfermedades($data = [])
    {
        try {
            // Desactivar transacciones implícitas
            $this->query("SET IMPLICIT_TRANSACTIONS OFF", []);

            $sql = "INSERT INTO SGO_EMP_DATOS_MEDICOS_EMPLEADOS
        (
            EmpleadoID,
            alergias,
            tieneAlergia,
            contactoEmergenciaNombre,
            contactoEmergenciaRelacion,
            contactoEmergenciaTelefono,
            tieneEnfermedad,
            enfermedades,
            tieneDiscapacidad,
            porcentajeDiscapacidad,
            tipoDiscapacidad,
            archivoDiscapacidadNombre
        )
        VALUES
        (
            :empleadoId,
            :alergias,
            :tieneAlergia,
            :contactoEmergenciaNombre,
            :contactoEmergenciaRelacion,
            :contactoEmergenciaTelefono,
            :tieneEnfermedad,
            :enfermedades,
            :tieneDiscapacidad,
            :porcentajeDiscapacidad,
            :tipoDiscapacidad,
            :archivoDiscapacidadNombre
            
        )";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':alergias' => $data['alergias'] ?? null,
                ':tieneAlergia' => $data['tieneAlergia'] ?? 'NO',
                ':contactoEmergenciaNombre' => $data['contactoEmergenciaNombre'] ?? null,
                ':contactoEmergenciaRelacion' => $data['contactoEmergenciaRelacion'] ?? null,
                ':contactoEmergenciaTelefono' => $data['contactoEmergenciaTelefono'] ?? null,
                ':tieneEnfermedad' => $data['tieneEnfermedad'] ?? 'NO',
                ':enfermedades' => $data['enfermedades'] ?? null,
                ':tieneDiscapacidad' => $data['tieneDiscapacidad'] ?? 'NO',
                ':porcentajeDiscapacidad' => $data['porcentajeDiscapacidad'] ?? null,
                ':tipoDiscapacidad' => $data['tipoDiscapacidad'] ?? null,
                ':archivoDiscapacidadNombre' => $data['archivoDiscapacidadNombre'] ?? null
               
            ];

            $rows = $this->db->execute($sql, $params);

            // Asegurar commit explícito
            $this->query("COMMIT");

            if ($rows <= 0) {
                throw new Exception("El INSERT no insertó ninguna fila.");
            }

            // --- AUDITORÍA DE CAMBIOS ---
            $camposModificados = [];
            foreach ($params as $campo => $valor) {
                $nombreCampo = str_replace(":", "", $campo);
                $camposModificados[$nombreCampo] = $valor;
            }

            $usuarioModificador = $data['Creado_Por'] ?? 'Sistema';

            $this->enviarAlertaCambio(
                $data['empleadoId'],
                'Actualización de Datos Médicos',
                $usuarioModificador,
                null,
                $camposModificados
            );

            return [
                'success' => true,
                'message' => 'Datos médicos guardados correctamente'
            ];

        } catch (Exception $e) {

            $this->logError("Error en ActualizarEnfermedades: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => $data
            ];
        }
    }



    function Getenfermedades($data = [])
    {
        try {


            $sql = "SELECT * from SGO_EMP_DATOS_MEDICOS_EMPLEADOS
				where EmpleadoID = :empleadoId
                ";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetCargasEstudios: " . $e->getMessage());
            error_log("Exception in GetCargasEstudios: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    function ConsultarRolesPago($data = [])
    {
        try {
            // Recibimos fecha YYYYMMDD → ejemplo: 20250601
            $fecha = $data['fecha'];

            // Convertimos a DateTime
            $dt = DateTime::createFromFormat('Ymd', $fecha);

            // Primer día del mes (ya viene así, pero se normaliza)
            $inicioMes = $dt->format('Ymd');

            // Calcular último día del mes
            $ultimoDiaMes = $dt->format('Ymt');  // <-- Ymt = AñoMesÚltimoDía

            // Consulta SQL con formato correcto
            $sql = "SELECT r.Fecha, ep.RolID, ep.EmpleadoID
                FROM EMP_ROLES r
                INNER JOIN EMP_ROLES_EMPLEADOS ep ON r.ID = ep.RolID
                WHERE ep.EmpleadoID = :empleadoId
                AND r.Fecha >= :inicio
                AND r.Fecha <= :fin";

            $params = [
                ':empleadoId' => $data['empleadoId'],
                ':inicio' => $inicioMes,       // YYYYMM01
                ':fin' => $ultimoDiaMes        // YYYYMMDD (último día del mes)
            ];

            return $this->query($sql, $params);

        } catch (Exception $e) {
            $this->logError("Error en ConsultarRolesPago: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    function detalle($data = [])
    {
        try {


            $sql = "SELECT
            rr.DocumentoID,
            rr.Tipo as Clase,
            rub.Nombre as Detalle,
            CASE WHEN rr.Tipo = 'Ingreso' THEN rr.Calculado ELSE 0 END as Ingreso,
            CASE WHEN rr.Tipo = 'Egreso' THEN rr.Valor ELSE 0 END as Egreso,
            CASE WHEN rr.DocumentoID = '' THEN rub.Nombre ELSE de.Detalle END as Detalle,
            ISNULL(de.Tipo,'') as Tipo ,
            de.DocumentoID as Referencia
            FROM EMP_ROLES_RUBROS rr
            JOIN EMP_RUBROS rub ON rr.RubroID = rub.ID
            LEFT JOIN EMP_EMPLEADOS_DEUDAS de ON de.ID = rr.DocumentoID
            WHERE rr.RolID = :rolId AND rr.Tipo <> 'Provision'
            ORDER BY LEN(Detalle) ASC, Ingreso DESC, Egreso ASC";

            $params = [

                ':rolId' => $data['rolId'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en DescargarRolPago: " . $e->getMessage());
            error_log("Exception in DescargarRolPago: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    function cabecera($data = [])
    {
        try {


            $sql = "SELECT 
                Detalle as Concepto , 
                ID, 
                Fecha , 
                Ingresos as Ingreso_Total, 
                Egresos as Egreso_Total, 
                Total as Valor_Neto 
                from EMP_ROLES 
                where ID = :rolId";

            $params = [

                ':rolId' => $data['rolId'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en DescargarRolPago: " . $e->getMessage());
            error_log("Exception in DescargarRolPago: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    function GenerarPDFRolPago($data = [])
    {
        try {


            $sql = "SELECT
            rr.DocumentoID,
            rr.Tipo as Clase,
            rub.Nombre as Detalle,
            CASE WHEN rr.Tipo = 'Ingreso' THEN rr.Calculado ELSE 0 END as Ingreso,
            CASE WHEN rr.Tipo = 'Egreso' THEN rr.Valor ELSE 0 END as Egreso,
            CASE WHEN rr.DocumentoID = '' THEN rub.Nombre ELSE de.Detalle END as Detalle,
            ISNULL(de.Tipo,'') as Tipo ,
            de.DocumentoID as Referencia
            FROM EMP_ROLES_RUBROS rr
            JOIN EMP_RUBROS rub ON rr.RubroID = rub.ID
            LEFT JOIN EMP_EMPLEADOS_DEUDAS de ON de.ID = rr.DocumentoID
            WHERE rr.RolID = :rolId AND rr.Tipo <> 'Provision'
            ORDER BY LEN(Detalle) ASC, Ingreso DESC, Egreso ASC";

            $params = [

                ':rolId' => $data['rolId'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en DescargarRolPago: " . $e->getMessage());
            error_log("Exception in DescargarRolPago: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function generarPDF($rolId)
    {
        try {

            // Obtener los datos (LOS MÉTODOS ESPERAN ARRAY)
            $cabeceraResult = $this->cabecera(['rolId' => $rolId]);
            $detalleResult = $this->detalle(['rolId' => $rolId]);

            if (empty($cabeceraResult['data']) || empty($detalleResult['data'])) {
                return ['success' => false, 'error' => 'No se encontraron datos para el Rol ID: ' . $rolId];
            }

            $row = $cabeceraResult['data'][0];
            $cuerpo = $detalleResult['data'];

            // Crear PDF
            $pdf = new \FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', '', 10);

            // LOGO
            $pdf->Image('https://ww.nexxtsolutions.com/wp-content/uploads/2019/02/01-Cartimex-244x122.png', 7, 3, 40);

            // TÍTULO DERECHA
            $pdf->SetXY(120, 10);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(77, 9, '  ROL DE PAGO NO: ' . $row['ID'], 0, 0, 'R');
            $pdf->Ln(15);

            // CABECERA PRINCIPAL
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(120, 6, "Concepto", 'L,T', 0, 'L');
            $pdf->Cell(25, 6, "ID", 'T', 0, 'C');
            $pdf->Cell(43, 6, "Fecha", 'T,R', 0, 'C');
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(120, 5, utf8_decode($row["Concepto"]), 'L,B', 0, 'L');
            $pdf->Cell(25, 5, $row["ID"], 'B', 0, 'C');
            $pdf->Cell(43, 5, date('d/m/Y', strtotime($row["Fecha"])), 'B,R', 0, 'C');
            $pdf->Ln(10);

            // ENCABEZADO DETALLE
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(19, 5, "Clase", 0, 0, 'C');
            $pdf->Cell(19, 5, "Tipo", 0, 0, 'C');
            $pdf->Cell(19, 5, "Ref", 0, 0, 'C');
            $pdf->Cell(90, 5, "Detalle", 0, 0, 'C');
            $pdf->Cell(20, 5, "Ingreso", 0, 0, 'R');
            $pdf->Cell(21, 5, "Egreso", 0, 0, 'R');
            $pdf->Ln(6);

            // Línea gris suave
            $pdf->SetDrawColor(200, 200, 200);
            $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
            $pdf->Ln(2);

            // CUERPO DETALLE
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetDrawColor(220, 220, 220); // gris suave entre filas

            foreach ($cuerpo as $item) {

                $detalleTexto = utf8_decode($item["Detalle"]);
                $ingreso = "$" . number_format($item["Ingreso"], 2);
                $egreso = "$" . number_format($item["Egreso"], 2);

                // Imprimir fila sin bordes
                $pdf->Cell(19, 5, $item["Clase"], 0, 0, 'C');
                $pdf->Cell(19, 5, $item["Tipo"], 0, 0, 'C');
                $pdf->Cell(19, 5, $item["Referencia"], 0, 0, 'L');

                // DETALLE LARGO
                if (strlen($detalleTexto) > 50) {
                    $lineas = explode("\n", wordwrap($detalleTexto, 50, "\n"));
                    $pdf->Cell(90, 5, $lineas[0], 0, 0, 'L');
                    $pdf->Cell(20, 5, $ingreso, 0, 0, 'R');
                    $pdf->Cell(21, 5, $egreso, 0, 0, 'R');
                    $pdf->Ln();

                    for ($j = 1; $j < count($lineas); $j++) {
                        $pdf->Cell(57, 4, "");
                        $pdf->Cell(90, 4, $lineas[$j], 0, 0, 'L');
                        $pdf->Ln();
                    }
                } else {
                    $pdf->Cell(90, 5, $detalleTexto, 0, 0, 'L');
                    $pdf->Cell(20, 5, $ingreso, 0, 0, 'R');
                    $pdf->Cell(21, 5, $egreso, 0, 0, 'R');
                    $pdf->Ln();
                }

                // Línea gris separadora
                $pdf->SetDrawColor(210, 210, 210);
                $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
            }

            // TOTALES
            $pdf->Ln(8);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(150, 7, "", 0, 0, 'C');
            $pdf->Cell(20, 7, '$' . number_format($row["Ingreso_Total"], 2), 0, 0, 'R');
            $pdf->Cell(20, 7, '$' . number_format($row["Egreso_Total"], 2), 0, 0, 'R');
            $pdf->Ln(15);

            // VALOR NETO
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 8, "Valor Neto a Recibir:     $" . number_format($row["Valor_Neto"], 2), 0, 1, 'C');

            // PIE DE FIRMA
            $pdf->Ln(15);
            $pdf->SetFont('Times', 'BI', 12);
            $pdf->Cell(0, 10, 'Documento Generado Electronica­mente', 0, 1, 'L');

            // PDF EN MEMORIA
            $pdfBinary = $pdf->Output('S');

            return ['success' => true, 'pdfData' => $pdfBinary, 'rolId' => $row["ID"]];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }






    function ActualizarPassword($data = [])
    {
        try {
            $empleadoId = $data['empleadoId'] ?? null;
            $currentPassword = $data['currentPassword'] ?? null;
            $newPassword = $data['newPassword'] ?? null;

            if (!$empleadoId || !$currentPassword || !$newPassword) {
                return [
                    'success' => false,
                    'error' => 'Faltan datos requeridos (ID, contraseña actual o nueva)'
                ];
            }

            // 1. Verificar la contraseña actual
            $sqlCheck = "SELECT clave FROM SERIESUSR WHERE EmpleadoID = :empleadoId";
            $resultCheck = $this->query($sqlCheck, [':empleadoId' => $empleadoId]);

            if (empty($resultCheck['data'])) {
                return [
                    'success' => false,
                    'error' => 'Usuario no encontrado'
                ];
            }

            $storedPassword = $resultCheck['data'][0]['clave'];

            // Comparación (usando la misma lógica que LoginModel: strtolower)
            if (strtolower($currentPassword) !== strtolower($storedPassword)) {
                return [
                    'success' => false,
                    'error' => 'La contraseña actual es incorrecta'
                ];
            }

            // 2. Actualizar la contraseña
            $sql = "UPDATE SERIESUSR 
                SET clave = :newPassword
                WHERE EmpleadoID = :empleadoId";

            $params = [
                ':empleadoId' => $empleadoId,
                ':newPassword' => $newPassword
            ];

            // Usar execute para UPDATE, no query
            $rows = $this->db->execute($sql, $params);

            // Si llega aquí es que no hubo excepción
            return [
                'success' => true,
                'message' => 'Contraseña actualizada correctamente',
                'rowsAffected' => $rows
            ];


        } catch (Exception $e) {
            $this->logError("Error en ActualizarPassword: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    function SubirFotoPerfil($data = [])
    {
        try {
            $sql = "UPDATE EMP_EMPLEADOS SET foto_perfil = :name
            where ID = :empleadoId";

            $params = [
                ':empleadoId' => $data['empleadoId'] ?? null,
                ':name' => $data['name'] ?? null,
            ];

            // Usar execute para UPDATE, no query
            $rows = $this->db->execute($sql, $params);

            return [
                'success' => true,
                'message' => 'Foto actualizada correctamente',
                'rowsAffected' => $rows
            ];

        } catch (Exception $e) {
            $this->logError("Error en SubirFotoPerfil: " . $e->getMessage());
            error_log("Exception in SubirFotoPerfil: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function ActualizarEstudios2($data = [])
    {
        try {
            $sql = "UPDATE SGO_EMP_EMPLEADOS_ESTUDIOS SET 
            titulo = :titulo,
            institucion = :institucion,
            anio = :anio,
            titulo_pdf = :titulo_pdf
            WHERE EmpleadoID = :empleadoId";

            $params = [
                ':empleadoId' => $data['empleadoId'] ?? null,
                ':titulo' => $data['titulo'] ?? null,
                ':institucion' => $data['institucion'] ?? null,
                ':anio' => $data['anio'] ?? null,
                ':titulo_pdf' => $data['titulo_pdf'] ?? null
            ];

            // Usar execute para UPDATE, no query
            $rows = $this->db->execute($sql, $params);

            return [
                'success' => true,
                'message' => 'Estudio actualizado correctamente',
                'rowsAffected' => $rows
            ];

        } catch (Exception $e) {
            $this->logError("Error en ActualizarEstudios2: " . $e->getMessage());
            error_log("Exception in ActualizarEstudios2: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    // function generarPDF($rolId)
    // {
    //     try {
    //         // require_once("fpdf/fpdf.php"); // Eliminado

    //         // OBTENER DATOS
    //         // Pasamos un array como espera el método
    //         $cabeceraResult = $this->cabecera(['rolId' => $rolId]);
    //         $detalleResult = $this->detalle(['rolId' => $rolId]);

    //         // Verificar si obtuvimos datos
    //         if (empty($cabeceraResult['data']) || empty($detalleResult['data'])) {
    //             return ['success' => false, 'error' => 'No se encontraron datos para el Rol ID: ' . $rolId];
    //         }

    //         $row = $cabeceraResult['data'][0];
    //         $cuerpo = $detalleResult['data'];

    //         // DIRECTORIO DE SALIDA
    //         $carpeta = "roles_generados";
    //         if (!file_exists($carpeta)) {
    //             mkdir($carpeta, 0777, true);
    //         }

    //         $pdf = new \FPDF();
    //         $pdf->AddPage();
    //         $pdf->SetFont('Arial', '', 10);

    //         // LOGO
    //         $pdf->Image('https://ww.nexxtsolutions.com/wp-content/uploads/2019/02/01-Cartimex-244x122.png', 7, 3, 40);

    //         // TITULO DERECHA
    //         $pdf->SetXY(120, 10);
    //         $pdf->SetFont('Arial', 'B', 10);
    //         $pdf->Cell(77, 9, '  ROL DE PAGO NO: ' . $row['ID'], 0, 0, 'R');
    //         $pdf->Ln();

    //         // CABECERA
    //         $pdf->SetFont('Arial', 'B', 10);
    //         $pdf->Cell(120, 6, "Concepto", 'L,T', 0, 'L');
    //         $pdf->Cell(25, 6, "ID", 'T', 0, 'C');
    //         $pdf->Cell(43, 6, "Fecha", 'T,R', 0, 'C');
    //         $pdf->Ln();

    //         $pdf->SetFont('Arial', '', 8);
    //         $pdf->Cell(120, 5, utf8_decode($row["Concepto"]), 'L,B', 0, 'L');
    //         $pdf->Cell(25, 5, $row["ID"], 'B', 0, 'C');
    //         $pdf->Cell(43, 5, date('d/m/Y', strtotime($row["Fecha"])), 'B,R', 0, 'C');
    //         $pdf->Ln(10);

    //         // ENCABEZADO DETALLE
    //         $pdf->SetFont('Arial', 'B', 9);
    //         $pdf->Cell(19, 5, "Clase", 'B,T,L', 0, 'C');
    //         $pdf->Cell(19, 5, "Tipo", 'B,T', 0, 'C');
    //         $pdf->Cell(19, 5, "Ref", 'B,T', 0, 'C');
    //         $pdf->Cell(90, 5, "Detalle", 'B,T', 0, 'C');
    //         $pdf->Cell(20, 5, "Ingreso", 'B,T', 0, 'R');
    //         $pdf->Cell(21, 5, "Egreso", 'B,T,R', 0, 'R');
    //         $pdf->Ln(7);

    //         // CUERPO
    //         $pdf->SetFont('Arial', '', 8);

    //         foreach ($cuerpo as $item) {
    //             $detalleTexto = utf8_decode($item["Detalle"]);
    //             $ingreso = "$" . number_format($item["Ingreso"], 2);
    //             $egreso = "$" . number_format($item["Egreso"], 2);

    //             $pdf->Cell(19, 5, $item["Clase"], 1, 0, 'C');
    //             $pdf->Cell(19, 5, $item["Tipo"], 1, 0, 'C');
    //             $pdf->Cell(19, 5, $item["Referencia"], 1, 0, 'L');

    //             // DETALLE LARGO
    //             if (strlen($detalleTexto) > 50) {
    //                 $lineas = explode("\n", wordwrap($detalleTexto, 50, "\n"));
    //                 $pdf->Cell(90, 5, $lineas[0], 1, 0, 'L');
    //                 $pdf->Cell(20, 5, $ingreso, 1, 0, 'R');
    //                 $pdf->Cell(21, 5, $egreso, 1, 0, 'R');
    //                 $pdf->Ln();

    //                 for ($j = 1; $j < count($lineas); $j++) {
    //                     $pdf->Cell(57, 4, "", 0, 0);
    //                     $pdf->Cell(90, 4, $lineas[$j], 1, 0, 'L');
    //                     $pdf->Ln();
    //                 }
    //             } else {
    //                 $pdf->Cell(90, 5, $detalleTexto, 1, 0, 'L');
    //                 $pdf->Cell(20, 5, $ingreso, 1, 0, 'R');
    //                 $pdf->Cell(21, 5, $egreso, 1, 0, 'R');
    //                 $pdf->Ln();
    //             }
    //         }

    //         // TOTALES
    //         $pdf->Ln(5);
    //         $pdf->SetFont('Arial', 'B', 9);
    //         $pdf->Cell(150, 7, "", 'T,R', 0, 'C');
    //         $pdf->Cell(20, 7, '$' . number_format($row["Ingreso_Total"], 2), 1, 0, 'R');
    //         $pdf->Cell(20, 7, '$' . number_format($row["Egreso_Total"], 2), 1, 0, 'R');
    //         $pdf->Ln(15);

    //         // VALOR NETO
    //         $pdf->SetFont('Arial', 'B', 10);
    //         $pdf->Cell(0, 8, "Valor Neto a Recibir:     $" . number_format($row["Valor_Neto"], 2), 0, 0, 'C');
    //         $pdf->Ln(20);

    //         // FIRMA
    //         $pdf->SetFont('Times', 'BI', 12);
    //         $pdf->Cell(0, 10, 'Documento Generado Electronicamente', 0, 1, 'L');

    //         // GENERAR PDF EN MEMORIA (no guardar en archivo)
    //         $pdfBinary = $pdf->Output('S'); // 'S' devuelve el PDF como string

    //         return ['success' => true, 'pdfData' => $pdfBinary, 'rolId' => $row["ID"]];

    //     } catch (Exception $e) {
    //         return ['success' => false, 'error' => $e->getMessage()];
    //     }
    // }
}
