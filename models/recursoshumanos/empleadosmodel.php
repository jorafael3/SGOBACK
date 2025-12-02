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


            $sql = "SELECT e.Código as Cedula , e.Nombre , e.Dirección , e.Teléfono3
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




    function ActualizarDatosPersonales($data = [])
    {
        try {
            // Desactivar transacciones implícitas
            $this->query("SET IMPLICIT_TRANSACTIONS OFF", []);

            $sql = "UPDATE EMP_EMPLEADOS 
                    SET Dirección = :direccion, 
                        EstadoCivil = :estadoCivil, 
                        Teléfono3 = :telefono, 
                        email = :email,
                        FechaNac = :fechaNac
                    WHERE ID = :empleadoId";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':direccion' => $data['Dirección'] ?? null,
                ':estadoCivil' => $data['EstadoCivil'] ?? null,
                ':telefono' => $data['Teléfono3'] ?? null,
                ':email' => $data['email'] ?? null,
                ':fechaNac' => $data['FechaNac'] ?? null
            ];

            // IMPORTANTE: Usar execute() para que se guarden los cambios
            $this->db->execute($sql, $params);

            return [
                'success' => true,
                'message' => 'Datos actualizados correctamente PERSONALES'
            ];

        } catch (Exception $e) {
            $this->logError("Error en ActualizarDatosPersonales: " . $e->getMessage());
            error_log("Exception in ActualizarDatosPersonales: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al actualizar: ' . $e->getMessage()
            ];
        }
    }



    function ActualizarCargasEmpleado($data = [])
    {
        try {

            // SQL corregido (cédula con corchetes)
            $sql = "INSERT INTO EMP_EMPLEADOS_CARGAS 
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
            [cédula],
            TipoCarga,
            Carga,
            Ocupación
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
            ''
        )";

            // Parámetros
            $params = [
                ':empleadoId' => $data['empleadoId'] ?? null,
                ':Edad' => $data['Edad'] ?? null,
                ':Sexo' => $data['Sexo'] ?? null,
                ':Nombres' => $data['Nombres'] ?? null,
                ':FechaNacimiento' => $data['FechaNacimiento'] ?? null,
                ':cedula' => $data['cédula'] ?? null, // parámetro SIN tilde
                ':TipoCarga' => $data['TipoCarga'] ?? null,
                ':Creado_Por' => $data['Creado_Por'] ?? 'SISTEMA',
            ];

            // Ejecutar
            $rows = $this->db->execute($sql, $params);

            // Validar si sí insertó
            if ($rows <= 0) {
                throw new Exception("El INSERT no insertó ninguna fila. Verifique los datos enviados.");
            }

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
            anio
           
        )
        VALUES
        (
            :empleadoId,
            :titulo,
            :institucion,
            :anio

        )";

            // Parámetros
            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':titulo' => $data['titulo'] ?? null,
                ':institucion' => $data['institucion'] ?? null,
                ':anio' => $data['anio'] ?? null,

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


            $sql = "SELECT * from SGO_EMP_EMPLEADOS_ESTUDIOS
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

            $sql = "INSERT INTO SGO_EMP_DATOS_MEDICOS_EMPLEADOS
        (
            EmpleadoID,
            alergias,
            contactoEmergenciaNombre,
            contactoEmergenciaRelacion,
            contactoEmergenciaTelefono,
            enfermedades
        )
        VALUES
        (
            :empleadoId,
            :alergias,
            :contactoEmergenciaNombre,
            :contactoEmergenciaRelacion,
            :contactoEmergenciaTelefono,
            :enfermedades
        )";

            $params = [
                ':empleadoId' => $data['empleadoId'] ?? null,
                ':alergias' => $data['alergias'] ?? null,
                ':contactoEmergenciaNombre' => $data['contactoEmergenciaNombre'] ?? null,
                ':contactoEmergenciaRelacion' => $data['contactoEmergenciaRelacion'] ?? null,
                ':contactoEmergenciaTelefono' => $data['contactoEmergenciaTelefono'] ?? null,
                ':enfermedades' => $data['enfermedades'] ?? null,
            ];

            $rows = $this->db->execute($sql, $params);

            if ($rows <= 0) {
                throw new Exception("El INSERT no insertó ninguna fila.");
            }

            return [
                'success' => true,
                'message' => 'Datos médicos guardados correctamente'
            ];

        } catch (Exception $e) {

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

            // Recibimos fecha YYYYMMDD → ejemplo: 20250101
            $fecha = $data['fecha'];

            // Convertir a un objeto DateTime
            $dt = DateTime::createFromFormat('Ymd', $fecha);

            // Primer día del mes
            $inicioMes = $dt->format('Y-m-01');

            // Primer día del siguiente mes
            $dtSiguiente = clone $dt;
            $dtSiguiente->modify('first day of next month');
            $finMes = $dtSiguiente->format('Y-m-d');

            // Consulta SQL usando parámetros simples
            $sql = "SELECT r.Fecha, ep.RolID, ep.EmpleadoID
                FROM EMP_ROLES r
                INNER JOIN EMP_ROLES_EMPLEADOS ep ON r.ID = ep.RolID
                WHERE ep.EmpleadoID = :empleadoId
                AND r.Fecha >= :inicio
                AND r.Fecha < :fin";

            $params = [
                ':empleadoId' => $data['empleadoId'],
                ':inicio' => $inicioMes,
                ':fin' => $finMes
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
            // require_once("fpdf/fpdf.php"); // Eliminado

            // OBTENER DATOS
            // Pasamos un array como espera el método
            $cabeceraResult = $this->cabecera(['rolId' => $rolId]);
            $detalleResult = $this->detalle(['rolId' => $rolId]);

            // Verificar si obtuvimos datos
            if (empty($cabeceraResult['data']) || empty($detalleResult['data'])) {
                return ['success' => false, 'error' => 'No se encontraron datos para el Rol ID: ' . $rolId];
            }

            $row = $cabeceraResult['data'][0];
            $cuerpo = $detalleResult['data'];

            // DIRECTORIO DE SALIDA
            $carpeta = "roles_generados";
            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0777, true);
            }

            $pdf = new \FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', '', 10);

            // LOGO
            $pdf->Image('https://ww.nexxtsolutions.com/wp-content/uploads/2019/02/01-Cartimex-244x122.png', 7, 3, 40);

            // TITULO DERECHA
            $pdf->SetXY(120, 10);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(77, 9, '  ROL DE PAGO NO: ' . $row['ID'], 0, 0, 'R');
            $pdf->Ln();

            // CABECERA
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
            $pdf->Cell(19, 5, "Clase", 'B,T,L', 0, 'C');
            $pdf->Cell(19, 5, "Tipo", 'B,T', 0, 'C');
            $pdf->Cell(19, 5, "Ref", 'B,T', 0, 'C');
            $pdf->Cell(90, 5, "Detalle", 'B,T', 0, 'C');
            $pdf->Cell(20, 5, "Ingreso", 'B,T', 0, 'R');
            $pdf->Cell(21, 5, "Egreso", 'B,T,R', 0, 'R');
            $pdf->Ln(7);

            // CUERPO
            $pdf->SetFont('Arial', '', 8);

            foreach ($cuerpo as $item) {
                $detalleTexto = utf8_decode($item["Detalle"]);
                $ingreso = "$" . number_format($item["Ingreso"], 2);
                $egreso = "$" . number_format($item["Egreso"], 2);

                $pdf->Cell(19, 5, $item["Clase"], 1, 0, 'C');
                $pdf->Cell(19, 5, $item["Tipo"], 1, 0, 'C');
                $pdf->Cell(19, 5, $item["Referencia"], 1, 0, 'L');

                // DETALLE LARGO
                if (strlen($detalleTexto) > 50) {
                    $lineas = explode("\n", wordwrap($detalleTexto, 50, "\n"));
                    $pdf->Cell(90, 5, $lineas[0], 1, 0, 'L');
                    $pdf->Cell(20, 5, $ingreso, 1, 0, 'R');
                    $pdf->Cell(21, 5, $egreso, 1, 0, 'R');
                    $pdf->Ln();

                    for ($j = 1; $j < count($lineas); $j++) {
                        $pdf->Cell(57, 4, "", 0, 0);
                        $pdf->Cell(90, 4, $lineas[$j], 1, 0, 'L');
                        $pdf->Ln();
                    }
                } else {
                    $pdf->Cell(90, 5, $detalleTexto, 1, 0, 'L');
                    $pdf->Cell(20, 5, $ingreso, 1, 0, 'R');
                    $pdf->Cell(21, 5, $egreso, 1, 0, 'R');
                    $pdf->Ln();
                }
            }

            // TOTALES
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(150, 7, "", 'T,R', 0, 'C');
            $pdf->Cell(20, 7, '$' . number_format($row["Ingreso_Total"], 2), 1, 0, 'R');
            $pdf->Cell(20, 7, '$' . number_format($row["Egreso_Total"], 2), 1, 0, 'R');
            $pdf->Ln(15);

            // VALOR NETO
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 8, "Valor Neto a Recibir:     $" . number_format($row["Valor_Neto"], 2), 0, 0, 'C');
            $pdf->Ln(20);

            // FIRMA
            $pdf->SetFont('Times', 'BI', 12);
            $pdf->Cell(0, 10, 'Documento Generado Electronicamente', 0, 1, 'L');

            // NOMBRE ARCHIVO
            date_default_timezone_set('America/Guayaquil');
            $dateString = date('Ymd_His');

            $filename = $carpeta . $row["ID"] . "_" . $dateString . ".pdf";

            // GENERAR PDF
            $pdf->Output($filename, 'F');

            return ['success' => true, 'archivo' => $filename];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }



   

}
