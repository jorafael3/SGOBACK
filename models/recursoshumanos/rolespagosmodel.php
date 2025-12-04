<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class RolespagosModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
    }




    function enviarrolespagos($data = [])
    {
        try {


            $sql = "";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en enviarrolespagos: " . $e->getMessage());
            error_log("Exception in enviarrolespagos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function BuscarRolIndividual($data = [])
    {
        try {

            // ========================================================
            // 1. Calcular fecha_inicio y fecha_fin (formato AAAAMMDD)
            // ========================================================
            if (!empty($data['fecha'])) {

                // Fecha que viene del front: AAAAMMDD
                $fecha = DateTime::createFromFormat('Ymd', $data['fecha']);

                // Fecha inicio = igual a la enviada
                $data['fecha_inicio'] = $fecha->format('Ymd');

                // Fecha fin = último día del mes
                $fecha_fin = clone $fecha;
                $fecha_fin->modify('last day of this month');
                $data['fecha_fin'] = $fecha_fin->format('Ymd');
            } else {
                throw new Exception("No se envió la fecha.");
            }

            // ========================================================
            // 2. Ejecutar SP con los 4 parámetros
            // ========================================================
            $sql = "EXEC SGO_EMP_TOMAR_DATOS_ROL_INDIVIDUAL
                @cedula = :cedula,
                @empresa = :empresa,
                @fecha_inicio = :fecha_inicio,
                @fecha_fin = :fecha_fin";

            $params = [
                ':cedula' => $data['cedula'] ?? null,
                ':empresa' => $data['empresa'] ?? null,
                ':fecha_inicio' => $data['fecha_inicio'],
                ':fecha_fin' => $data['fecha_fin']
            ];

            $stmt = $this->query($sql, $params);
            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en BuscarRolIndividual: " . $e->getMessage());
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





    function email_roles_individual($empleadoId, $seccion, $usuarioModificador, $empleadoNombre = null, $camposModificados = [])
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



    

    function enviarRolCorreo($data = [])
    {
        try {
            $rolId = $data['rolId'] ?? null;
            $cedula = $data['cedula'] ?? null;
            $empresa = $data['empresa'] ?? null;

            if (!$rolId) {
                return ['success' => false, 'error' => 'rolId es requerido'];
            }

            // 1. Generar el PDF
            error_log("Generando PDF para rolId: " . $rolId);
            $pdfResult = $this->generarPDF($rolId);
            if (!$pdfResult['success']) {
                error_log("Error al generar PDF: " . json_encode($pdfResult));
                return $pdfResult;
            }
            $pdfData = $pdfResult['pdfData'];
            error_log("PDF generado exitosamente");

            // 2. Obtener datos del empleado usando la cédula
            if ($cedula) {
                // Buscar por cédula directamente
                $sqlEmp = "SELECT Nombre, Correo FROM EMP_EMPLEADOS WHERE Cedula = :cedula";
                $resEmp = $this->query($sqlEmp, [':cedula' => $cedula]);
                error_log("Query por cédula: " . $cedula . " - Resultado: " . json_encode($resEmp));
            } else {
                // Buscar por rolId (método alternativo)
                $sqlRol = "SELECT EmpleadoID, Fecha FROM EMP_ROLES WHERE ID = :rolId";
                $resRol = $this->query($sqlRol, [':rolId' => $rolId]);
                error_log("Query EMP_ROLES: " . json_encode($resRol));

                if (empty($resRol['data'])) {
                    return ['success' => false, 'error' => 'No se encontró el Rol ID: ' . $rolId];
                }

                $empleadoId = $resRol['data'][0]['EmpleadoID'];
                $sqlEmp = "SELECT Nombre, Correo FROM EMP_EMPLEADOS WHERE ID = :empleadoId";
                $resEmp = $this->query($sqlEmp, [':empleadoId' => $empleadoId]);
                error_log("Query EMP_EMPLEADOS por ID: " . json_encode($resEmp));
            }

            if (empty($resEmp['data'])) {
                return ['success' => false, 'error' => 'No se encontró el empleado. Cédula: ' . $cedula];
            }

            $empleadoNombre = $resEmp['data'][0]['Nombre'];
            $empleadoCorreo = $resEmp['data'][0]['Correo'];

            error_log("Empleado encontrado: " . $empleadoNombre . " - Correo: " . $empleadoCorreo);

            if (empty($empleadoCorreo)) {
                return ['success' => false, 'error' => 'El empleado ' . $empleadoNombre . ' no tiene un correo registrado.'];
            }

            // Obtener fecha del rol para el nombre del archivo
            $sqlRolFecha = "SELECT Fecha FROM EMP_ROLES WHERE ID = :rolId";
            $resRolFecha = $this->query($sqlRolFecha, [':rolId' => $rolId]);
            $fechaRol = $resRolFecha['data'][0]['Fecha'] ?? date('Y-m-d');
            $mesRol = date('m/Y', strtotime($fechaRol));

            // 3. Configurar PHPMailer
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

            // Remitente y Destinatario
            $mail->setFrom('sgoinfocorreo@gmail.com', 'Sistema SGO - RRHH');
            $mail->addAddress($empleadoCorreo, $empleadoNombre);
            $mail->addBCC('nadelaese@gmail.com'); // Copia oculta a RRHH

            // Adjuntar PDF
            $mail->addStringAttachment($pdfData, 'Rol_Pago_' . $mesRol . '.pdf');

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = 'Rol de Pago - ' . $mesRol;

            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                    .header { background-color: #0056b3; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { padding: 20px; }
                    .footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Rol de Pago</h2>
                    </div>
                    <div class='content'>
                        <p>Estimado(a) <strong>" . htmlspecialchars($empleadoNombre) . "</strong>,</p>
                        <p>Adjunto encontrará su rol de pago correspondiente al mes de <strong>" . $mesRol . "</strong>.</p>
                        <p>Por favor, revise el documento adjunto.</p>
                        <br>
                        <p>Saludos cordiales,<br>Departamento de Recursos Humanos</p>
                    </div>
                    <div class='footer'>
                        Este es un mensaje automático, por favor no responda a este correo.
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->AltBody = "Estimado(a) " . $empleadoNombre . ",\n\nAdjunto encontrará su rol de pago correspondiente al mes de " . $mesRol . ".\n\nSaludos cordiales,\nDepartamento de Recursos Humanos";

            error_log("Enviando correo a: " . $empleadoCorreo);
            $mail->send();
            error_log("Correo enviado exitosamente");

            return ['success' => true, 'message' => 'Correo enviado al usuario correctamente'];

        } catch (Exception $e) {
            $this->logError("Error en enviarRolCorreo: " . $e->getMessage());
            error_log("Exception completa en enviarRolCorreo: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
            return ['success' => false, 'error' => 'Error al enviar el correo: ' . $e->getMessage()];
        }
    }
}
