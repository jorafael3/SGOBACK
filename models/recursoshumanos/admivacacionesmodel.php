<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';
require_once __DIR__ . '/../../libs/EmailService.php';
require_once __DIR__ . '/../../vendor/setasign/fpdf/fpdf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class AdmivacacionesModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
    }




    function getvacaciones($data = [])
    {
        try {
            // Obtener el empleadoId de userdata o usar el que viene en data
            $empleadoId = $data['userdata']['empleadoid'] ?? $data['empleadoId'] ?? null;
            $empresa = $data['userdata']['empresa'] ?? $data['empresa'] ?? null;
            $tipo = 1; // Tipo 1 = Cargar lista principal

            if (!$empleadoId || !$empresa) {
                return [
                    'success' => false,
                    'error' => 'Faltan parámetros requeridos: empleadoId y empresa'
                ];
            }

            // Llamar al SP con prefijo de empresa (CARTIMEX..SP o COMPUTRON..SP)
            $sql = "EXEC {$empresa}..SGO_REC_VACACIONES_SOLICITADAS_RH :empleadoId, :tipo";

            $params = [
                ':empleadoId' => $empleadoId,
                ':tipo' => $tipo
            ];

            $result = $this->query($sql, $params);

            if ($result['success'] && !empty($result['data'])) {
                // Para cada registro, cargar los detalles
                for ($i = 0; $i < count($result['data']); $i++) {
                    $id = $result['data'][$i]['ID'];
                    $detalles = $this->getvacaciones_detalle($id, $empresa);
                    $result['data'][$i]['DATA'] = $detalles;
                }
            }

            return $result;

        } catch (Exception $e) {
            $this->logError("Error en getvacaciones: " . $e->getMessage());
            error_log("Exception in getvacaciones: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Función auxiliar para cargar los detalles de una solicitud de vacaciones
     */
    private function getvacaciones_detalle($id, $empresa)
    {
        try {
            $tipo = 2; // Tipo 2 = Cargar detalles

            // Llamar al SP con prefijo de empresa
            $sql = "EXEC {$empresa}..SGO_REC_VACACIONES_SOLICITADAS_RH :empleadoId, :tipo";

            $params = [
                ':empleadoId' => $id,
                ':tipo' => $tipo
            ];

            $result = $this->query($sql, $params);

            if ($result['success'] && !empty($result['data'])) {
                return $result['data'];
            }

            return [];

        } catch (Exception $e) {
            $this->logError("Error en getvacaciones_detalle: " . $e->getMessage());
            return [];
        }
    }




    function generarpdfvacacion($data = [])
    {
        try {

            $sql = "SELECT distinct v.estado as Estado , e.Nombre as Empleado , v.Dia_inicio , v.Dia_fin , v.Dia_regreso ,
			d.Nombre as Departamento , e.email , v.Periodo , e.Código as Cedula , Dias_pendientes , Dias_solicitados
			, e.FechaIngreso , fu.Nombre as Cargo , jf.Nombre as Jefe_nombre
            , v.ID
            from SGO_VACACIONES_SOLICITADAS_EMPLEADOS v 
            inner join EMP_EMPLEADOS E on v.EmpleadoID = e.ID
			inner join SIS_DEPARTAMENTOS D on d.ID = E.DepartamentoID
			inner join SERIESUSR US on us.EmpleadoId = e.ID
			inner join EMP_EMPLEADOS JF on jf.ID = e.PadreID
			inner join SERIESUSR U on U.EmpleadoId = JF.ID
			inner join EMP_FUNCIONES fu on fu.ID = e.FunciónID
            where v.ID = :ID";

            $params = [
                ':ID' => $data['ID'] ?? null,
            ];

            $result = $this->query($sql, $params);

            if (!$result['success'] || empty($result['data'])) {
                return [
                    'success' => false,
                    'error' => 'No se encontraron datos para el ID proporcionado'
                ];
            }

            // Obtener el primer registro
            $vacacionData = $result['data'][0];

            // Agregar userdata al array de datos
            $vacacionData['userdata'] = $data['userdata'] ?? [];
            $vacacionData['Creado_Por'] = $data['userdata']['nombre'] ?? 'N/A';

            // Generar el PDF
            $pdfResult = $this->generarPDF($vacacionData);

            if ($pdfResult['success']) {
                // Retornar el PDF como base64 para que el frontend lo pueda abrir
                return [
                    'success' => true,
                    'pdfData' => base64_encode($pdfResult['pdfData']),
                    'filename' => $pdfResult['filename']
                ];
            } else {
                return $pdfResult;
            }

        } catch (Exception $e) {
            $this->logError("Error en GenerarPDFVacacion: " . $e->getMessage());
            error_log("Exception in GenerarPDFVacacion: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function generarPDF($data)
    {
        try {
            // Validar que tenemos los datos necesarios
            if (empty($data['Empleado']) || empty($data['Dia_inicio']) || empty($data['Dia_fin'])) {
                return ['success' => false, 'error' => 'Faltan datos necesarios para generar el PDF'];
            }

            // Crear PDF
            $pdf = new \FPDF();
            $pdf->SetMargins(10, 10, 10);
            $pdf->AddPage();

            // Determinar logo según empresa
            $empresa = strtolower($data['userdata']['empresa_name'] ?? $data['empresa'] ?? 'cartimex');

            // LOGO (esquina superior izquierda) - usando archivos locales
            $logoPath = __DIR__ . '/../../logos/';

            if (strpos($empresa, 'computron') !== false) {
                $logoFile = $logoPath . 'computron_logo.png';
            } else {
                $logoFile = $logoPath . 'cartimex_logo.png';
            }

            // Cargar logo si existe
            if (file_exists($logoFile)) {
                $pdf->Image($logoFile, 7, 3, 40);
            } else {
                error_log("[PDF] Logo no encontrado: " . $logoFile);
            }

            // FECHA (esquina superior derecha) - Posicionada correctamente
            $pdf->SetXY(150, 10);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(50, 5, date('Y-m-d'), 0, 0, 'R');

            $pdf->Ln(6);
            $pdf->Ln(14);

            // TÍTULO
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, utf8_decode('Solicitud de Vacaciones'), 0, 1, 'C');
            $pdf->Ln(10);

            // DATOS DEL EMPLEADO
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 10, 'Apellidos y Nombres:', 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 10, utf8_decode($data['Empleado']), 0, 1);

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 10, 'Cargo:', 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 10, utf8_decode($data['Cargo'] ?? 'N/A'), 0, 1);

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 10, utf8_decode('Cédula:'), 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 10, $data['Cedula'] ?? 'N/A', 0, 1);

            $fechaIngreso = 'N/A';
            if (!empty($data['FechaIngreso'])) {
                $fechaIngreso = date('Y-m-d', strtotime($data['FechaIngreso']));
            }
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(70, 10, utf8_decode('Fecha de Ingreso a la Compañía:'), 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 10, $fechaIngreso, 0, 1);

            // PERÍODO DE TRABAJO
            $pdf->Ln(6);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 13, utf8_decode('Estas vacaciones corresponden al período de trabajo:'), 0, 1);
            $pdf->Ln(6);

            // Usar datos del frontend
            $dias_solicitados = intval($data['Dias_solicitados'] ?? 0);
            $dias_pendientes = intval($data['Dias_pendientes'] ?? 0);
            $periodo = $data['Periodo'] ?? date('Y') . ' - ' . date('Y', strtotime('+1 year'));

            // Calcular días pendientes restantes
            $dias_pendientes_restantes = $dias_pendientes - $dias_solicitados;

            $pdf->SetFont('Arial', '', 10);
            $periodo_texto = 'Del año: ' . $periodo .
                ' Días Solicitados: ' . $dias_solicitados .
                ' Días Pendientes: ' . $dias_pendientes_restantes;
            $pdf->Cell(0, 10, utf8_decode($periodo_texto), 0, 1, 'C');

            // TABLA DE FECHAS - Centrada
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 9);

            $tableWidth = 160;
            $xPos = ($pdf->GetPageWidth() - $tableWidth) / 2;
            $pdf->SetX($xPos);

            // Headers de la tabla
            $pdf->Cell(40, 10, utf8_decode('Total días solicitados'), 1, 0, 'C');
            $pdf->Cell(40, 10, 'Desde', 1, 0, 'C');
            $pdf->Cell(40, 10, 'Hasta', 1, 0, 'C');
            $pdf->Cell(40, 10, 'Fecha de Regreso', 1, 1, 'C');

            // Datos de la tabla
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetX($xPos);
            $pdf->Cell(40, 10, $dias_solicitados, 1, 0, 'C');
            $pdf->Cell(40, 10, date('Y-m-d', strtotime($data['Dia_inicio'])), 1, 0, 'C');
            $pdf->Cell(40, 10, date('Y-m-d', strtotime($data['Dia_fin'])), 1, 0, 'C');
            $pdf->Cell(40, 10, date('Y-m-d', strtotime($data['Dia_regreso'])), 1, 1, 'C');

            // ESPACIO después de la tabla para posicionar leyenda en zona roja
            $pdf->Ln(30);

            // NOTA LEGAL - En la zona marcada en rojo
            $pdf->SetFont('Arial', '', 10);
            $pdf->MultiCell(0, 5, utf8_decode('Todo el personal deberá firmar esta solicitud antes de la fecha de salida, caso contrario se considerará como falta injustificada y se aplicará el Art. 172 del Código de Trabajo, literal 1.'), 0, 'C');

            // ESPACIO antes de las firmas
            $pdf->Ln(50);

            $pageWidth = $pdf->GetPageWidth();
            $lineWidth = 50;
            $coordenadaY = $pdf->GetY();

            // Calcular posiciones para centrar todo el bloque de firmas
            $totalWidth = 150;
            $startX = ($pageWidth - $totalWidth) / 2;

            $leftPos = $startX;
            $centerPos = $startX + 50;
            $rightPos = $startX + 100;

            // SOLICITANTE
            $pdf->SetXY($leftPos, $coordenadaY);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($lineWidth, 0, '---------------------------------------', 0, 0, 'C');
            $pdf->SetXY($leftPos, $coordenadaY + 5);
            $pdf->Cell($lineWidth, 0, 'SOLICITANTE', 0, 0, 'C');

            // JEFE INMEDIATO - NOMBRE SOBRE LA LÍNEA (marca verde)
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($centerPos, $coordenadaY - 8);
            $pdf->Cell($lineWidth, 0, utf8_decode(strtoupper(trim($data['Jefe_nombre']))), 0, 0, 'C');

            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY($centerPos, $coordenadaY);
            $pdf->Cell($lineWidth, 0, '---------------------------------------', 0, 0, 'C');
            $pdf->SetXY($centerPos, $coordenadaY + 5);
            $pdf->Cell($lineWidth, 0, 'JEFE INMEDIATO', 0, 0, 'C');

            // RECURSOS HUMANOS
            $pdf->SetXY($rightPos, $coordenadaY);
            $pdf->Cell($lineWidth, 0, '---------------------------------------', 0, 0, 'C');
            $pdf->SetXY($rightPos, $coordenadaY + 5);
            $pdf->Cell($lineWidth, 0, 'RECURSOS HUMANOS', 0, 0, 'C');

            // PDF EN MEMORIA
            $pdfBinary = $pdf->Output('S');

            return [
                'success' => true,
                'pdfData' => $pdfBinary,
                'filename' => 'Solicitud_Vacaciones_' . str_replace(' ', '_', $data['Empleado']) . '.pdf'
            ];

        } catch (Exception $e) {
            error_log("Error generando PDF de vacaciones: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

}
