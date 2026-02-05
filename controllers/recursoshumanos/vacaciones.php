<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';
require_once __DIR__ . '/../../libs/EmailService.php';
// require_once __DIR__ . '/../models/empresamodel.php';

require('fpdf/fpdf.php');
require_once('tcpdf/tcpdf.php');

class vacaciones extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'recursoshumanos/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('vacaciones'); // Cargar el modelo correcto
    }

    function Cargar_Vacaciones_Pedidas()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $params = $this->getJsonInput();
        // $param['EmpleadoID'] = $param['userdata']['EmpleadoID'] ?? null;
        $result = $this->model->Cargar_Vacaciones_Pedidas($params);
        if ($result && $result['success']) {

            $SO = PHP_OS;
            if ($SO == 'Linux') {
                // $destination_folder = '../../../sgo_docs/Cartimex/recusos_humanos/vacaciones/';
                $destination_folder = '/sgo_docs/Cartimex/recusos_humanos/vacaciones/';
                $server_root = '/var/www/html';
            } else {
                // Windows (XAMPP)
                $destination_folder = '/sgo_docs/Cartimex/recusos_humanos/vacaciones/';
                $server_root = 'C:/xampp/htdocs';
            }

            // Añadir información de existencia y URL del PDF a cada registro
            foreach ($result['data'] as &$row) {
                $id = $row['ID'];

                if ($id) {
                    $server_path = $server_root . $destination_folder . $id . '.pdf';
                    if (file_exists($server_path)) {
                        $row['pdf_url'] = $destination_folder . $id . '.pdf';
                        $row['has_pdf'] = true;
                    } else {
                        $row['pdf_url'] = '';
                        $row['has_pdf'] = false;
                    }
                } else {
                    $row['pdf_url'] = '';
                    $row['has_pdf'] = false;
                }
            }

            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }

    function Guardar_Vacaciones_empleados()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;

        $result = $this->model->Guardar_Vacaciones_empleados($params);
        if ($result && $result['success']) {
            try {
                $emailService = new EmailService();

                $empleado = $params["userdata"]['nombre'];
                $jefe = $params["DATOS_EMPLEADO"]['Jefe'];
                $jefeCorreo = $params["DATOS_EMPLEADO"]['CorreoJefe'];
                $dia_solicitados = $params['dias'];
                $dia_inicio = $params['inicio'];
                $dia_fin = $params['fin'];
                $dia_regreso = $params['regreso'];

                // Preparar destinatarios
                $destinatarios = [
                    $jefeCorreo,
                    "jalvaradoe3@gmail.com",
                    "sistema@cartimex.com"
                ];

                // Datos para la plantilla
                $datos = [
                    'jefe' => $jefe,
                    'empleado' => $empleado,
                    'dias_solicitados' => $dia_solicitados,
                    'dia_inicio' => date('Y-m-d', strtotime($dia_inicio)),
                    'dia_fin' => date('Y-m-d', strtotime($dia_fin)),
                    'dia_regreso' => date('Y-m-d', strtotime($dia_regreso))
                ];

                $empresa = $params["userdata"]["empleado_empresa"];

                switch ($empresa) {
                    case 'CARTIMEX':
                        $plantilla = 'solicitar_vacaciones_cartimex';
                        break;
                    case 'COMPUTRON':
                        $plantilla = 'solicitar_vacaciones_COMPUTRON';
                        break;
                    default:
                        $plantilla = 'solicitar_vacaciones_cartimex';
                        break;
                }

                // Enviar email
                $mail = $emailService->enviar(
                    $destinatarios,
                    'Solicitud de Vacaciones -> ' . $empleado,
                    $datos,
                    $plantilla,
                    null,
                    "Solicitud de Vacaciones",
                    [],
                    // $params["userdata"]["empresa_name"]
                    $empresa
                );

                $params['ID'] = $result['params'][':ID'] ?? null;
                $pdf = $this->Crear_pdf($params);

            } catch (Exception $e) {
                error_log("Error enviando email: " . $e->getMessage());
            }
            $this->jsonResponse([
                "success" => true,
                "message" => "Vacaciones solicitadas correctamente",
                "data" => $result,
                "email" => $mail,
                "pdf" => $pdf
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }

    function Crear_pdf($params)
    {
        // return $params;
        $solicitudID = $params['ID'];

        $empleado = $params["userdata"]['nombre'];
        $departamento = $params["userdata"]['EMPLEADO_DEPARTAMENTO_NOMBRE'];
        $cedula = $params["DATOS_EMPLEADO"]['Cedula'];
        $ingresoCompañia = $params["DATOS_EMPLEADO"]['FechaIngreso'];
        $jefe = $params["DATOS_EMPLEADO"]['Jefe'];

        $dia_inicio = $params['inicio'];
        $dia_fin = $params['fin'];
        $dia_regreso = $params['regreso'];

        $pdf = new TCPDF();
        $pdf->setPrintHeader(false); // Opcional: No imprimir encabezado en el PDF
        $pdf->SetMargins(10, 10, 10); // Establecer márgenes

        // Agregar una página al PDF
        $pdf->AddPage();

        // Logo
        $pdf->Image('https://www.cartimex.com/assets/img/logo200.png', 7, 3, 40);
        $pdf->Ln(6); // Salto de línea
        $pdf->Cell(0, 10, date('Y-m-d'), 0, 1);

        $pdf->Ln(14); // Salto de línea
        // Título
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Solicitud de Vacaciones', 0, 1, 'C');
        $pdf->Ln(10); // Salto de línea

        // Datos del empleado
        $pdf->SetFont('helvetica', 'B', 10); // Fuente en negrilla para títulos
        $pdf->Cell(40, 10, 'Apellidos y Nombres:', 0, 0); // Título en negrilla
        $pdf->SetFont('helvetica', '', 10); // Fuente normal para contenido
        $pdf->Cell(0, 10, $empleado, 0, 1);

        $pdf->SetFont('helvetica', 'B', 10); // Fuente en negrilla para títulos
        $pdf->Cell(40, 10, 'Cargo:', 0, 0); // Título en negrilla
        $pdf->SetFont('helvetica', '', 10); // Fuente normal para contenido
        $pdf->Cell(0, 10, $departamento, 0, 1);

        $pdf->SetFont('helvetica', 'B', 10); // Fuente en negrilla para títulos
        $pdf->Cell(40, 10, 'Cedula:', 0, 0); // Título en negrilla
        $pdf->SetFont('helvetica', '', 10); // Fuente normal para contenido
        $pdf->Cell(0, 10, $cedula, 0, 1);

        $fechaIngreso = date('Y-m-d', strtotime($ingresoCompañia));
        $pdf->SetFont('helvetica', 'B', 10); // Fuente en negrilla para títulos
        $pdf->Cell(70, 10, 'Fecha de Ingreso a la Compañia:', 0, 0); // Título en negrilla
        $pdf->SetFont('helvetica', '', 10); // Fuente normal para contenido
        $pdf->Cell(0, 10, $fechaIngreso, 0, 1);


        // $pdf->Cell(0, 10, 'Fecha : ' . date('Y-m-d'), 0, 1);
        $pdf->Ln(6); // Salto de línea
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 13, 'Estas vacaciones corresponden al periodo de trabajo:', 0, 1);
        $pdf->Ln(6); // Salto de línea

        $SUMDIAS = 0;
        for ($i = 0; $i < count($params['DATOS']); $i++) {
            $periodo = $params['DATOS'][$i]['Periodo'];
            $dia_solicitados = $params['DATOS'][$i]['solicitados'];
            $pendientes = $params['DATOS'][$i]['Disponibles'];
            $SUMDIAS = $SUMDIAS + intval($dia_solicitados);
            $pdf->Cell(
                0,
                10,
                'Del año: ' . $periodo . ' Dias Solicitados ' . $dia_solicitados . ' Dias Pendientes ' . (intval($pendientes) - intval($dia_solicitados)),
                0,
                1,
                'C'
            );
        }


        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 9);

        $tableWidth = 160;

        $xPos = ($pdf->GetPageWidth() - $tableWidth) / 2;
        $pdf->SetX($xPos); // Posiciona la tabla en el centro

        $pdf->Cell(40, 10, 'Total días solicitados', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Desde', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Hasta', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Fecha de Regreso', 1, 1, 'C');

        $pdf->SetFont('helvetica', '', 10);

        $fechaInicio = date('Y-m-d', strtotime($dia_inicio));
        $fechaFin = date('Y-m-d', strtotime($dia_fin));
        $fechaRegreso = date('Y-m-d', strtotime($dia_regreso));

        $pdf->SetX($xPos);
        $pdf->Cell(40, 10, $SUMDIAS, 1, 0, 'C');
        $pdf->Cell(40, 10, $fechaInicio, 1, 0, 'C');
        $pdf->Cell(40, 10, $fechaFin, 1, 0, 'C');
        $pdf->Cell(40, 10, $fechaRegreso, 1, 1, 'C');
        $pdf->Ln(3); // Salto de línea

        $pdf->Ln(13);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 10, 'Todo el personal deberá firmar esta solicitud antes de la fecha de salida, caso contrario se considerará como falta injustificada y se aplicará el Art. 172 del Código de Trabajo, literal 1.', 0, 'C');

        // Salto de línea para dar espacio antes de las firmas
        $pdf->Ln(30);

        $pageWidth = $pdf->GetPageWidth();
        $lineWidth = 50; // Ancho estimado para las líneas de firma

        // Coordenadas iniciales
        $coordenadaY = $pdf->GetY();

        $pdf->Ln(20);

        // Obtener el ancho de la página
        $pageWidth = $pdf->GetPageWidth();
        $lineWidth = 50; // Ancho estimado para las líneas de firma
        $coordenadaY = $pdf->GetY();

        // Posición "SOLICITANTE"
        $pdf->SetXY($pageWidth / 4 - ($lineWidth / 2), $coordenadaY); // Centrado en el primer cuarto
        $pdf->Cell($lineWidth, 0, '---------------------------------', 0, 1, 'C');
        $pdf->SetXY($pageWidth / 4 - ($lineWidth / 2), $coordenadaY + 5);
        $pdf->Cell($lineWidth, 10, 'SOLICITANTE', 0, 0, 'C');

        // Posición "JEFE INMEDIATO" y Firma
        $pdf->SetFont('helvetica', 'B', 7); // Fuente para la firma del jefe inmediato
        $pdf->SetXY($pageWidth / 2 - ($lineWidth / 2), $coordenadaY - 5); // Ajustar la altura para la firma
        $pdf->Cell($lineWidth, 10, $jefe, 0, 0, 'C');

        $pdf->SetFont('helvetica', '', 10); // Restablecer la fuente normal
        $pdf->SetXY($pageWidth / 2 - ($lineWidth / 2), $coordenadaY); // Centrado en la mitad
        $pdf->Cell($lineWidth, 0, '---------------------------------------', 0, 1, 'C');
        $pdf->SetXY($pageWidth / 2 - ($lineWidth / 2), $coordenadaY + 5);
        $pdf->Cell($lineWidth, 10, 'JEFE INMEDIATO', 0, 0, 'C');

        // Posición "RECURSOS HUMANOS"
        $pdf->SetXY(3 * $pageWidth / 4 - ($lineWidth / 2), $coordenadaY); // Centrado en el último cuarto
        $pdf->Cell($lineWidth, 0, '----------------------------------', 0, 1, 'C');
        $pdf->SetXY(3 * $pageWidth / 4 - ($lineWidth / 2), $coordenadaY + 5);
        $pdf->Cell($lineWidth, 10, 'RECURSOS HUMANOS', 0, 0, 'C');

        // Nombre del archivo con la fecha actual
        $fecha_actual = date('Ymd');
        $nombre_archivo = $solicitudID . '.pdf';

        // $fecha_actual = date('Ymd');
        // $nombre_archivo = $ID . $fecha_actual . '.pdf';
        //  Ruta local      
        //  Ruta Servidor    
        $SO = PHP_OS;
        // echo $SO;
        if ($SO != "Linux") {
            $ruta_pdf = 'C:/xampp/htdocs/sgo_docs/Cartimex/recusos_humanos/vacaciones/';
        } else {
            $ruta_pdf = '/var/www/html/sgo_docs/Cartimex/recusos_humanos/vacaciones/';
        }
        if (!file_exists($ruta_pdf)) {
            mkdir($ruta_pdf, 0777, true); // Crea la carpeta si no existe
        }
        $pdf->Output($ruta_pdf . $nombre_archivo, 'F'); // Guarda el PDF en la carpeta creada
        return $ruta_pdf . $nombre_archivo;
    }

    function Anular_Solicitud_Vacaciones()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $params = $this->getJsonInput();

        $result = $this->model->Anular_Solicitud_Vacaciones($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result,
                "params" => $params
            ], 200);
        }
    }
}
