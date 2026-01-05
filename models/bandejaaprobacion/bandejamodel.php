<?php

// require_once __DIR__ . '/../logsmodel.php';
require_once __DIR__ . '/../../libs/EmailService.php';
require_once __DIR__ . '/../../vendor/setasign/fpdf/fpdf.php';



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class BandejaModel extends Model
{
	public function __construct($empresaCode = null)
	{
		// Usar la empresa por defecto del sistema si no se especifica
		parent::__construct($empresaCode);

		// Debug: mostrar qué empresa estás usando
		if (DEBUG) {
			error_log("GuiasPickupModel conectado a: " . $this->empresaCode);
		}
	}

	function GetFacturasAprobacion($params)
	{
		try {
			$inicio = date('Ym01');
			$fin = date('Ymd');

			$sql = "SELECT 
				ID_factura,
				factura_secuencia = secuencia,
				factura_autorizacion = Autorizacion,
				factura_detalle =  Detalle,
				factura_proveedor = Proveedor,
				factura_fecha_solicitud = creado_date,
				factura_fecha_factura = Fecha_factura,
				factura_Fecha_Vencimiento = Fecha_Vencimiento,
				valor_subtotal_12  =  Subtotal_12,
				valor_subtotal_0  =  Subtotal_0,
				valor_iva  =  IVA,
				valor_total = f.Total,
				f.Estado,
				Estado_texto = CASE 
							WHEN f.Estado = 0 then 'Pendiente'
							WHEN f.Estado = 1 then 'Aprobado'
							WHEN f.Estado = 2 then 'Rechazado'
							ELSE ''
							END,
				solicitado_por_usuario = u.usuario,
				solicitado_por_usuario_nombre = u.nombre,
				Buzon = u2.usuario,
				Buzon_nombre = u2.nombre,
				Empresa,
				Tipo_gasto = t.tipo_nombre,
				--
				aprobado_por = isnull(aprobado_por,''),
				aprobado_nota = isnull(Nota_aprobado,''),
				aprobado_fecha = isnull(Fecha_aprobacion,''),
				rechazado_por = isnull(rechazado_por,''),
				rechazado_nota  =  isnull(comentario_rechazo,''),
				rechazado_fecha  =  isnull(fecha_rechazo,''),
				isnull(ds.total,0) as doc_es_total,
				f.CedBeneficiario,
				isnull(f.PREAPROBACION,0) as PREAPROBACION,
				isnull(f.PREAPROBADA,0) as PREAPROBADA,
				isnull(f.PREAPROBADA_POR,'') as PREAPROBADA_POR,
				isnull(f.PREAPROBADA_FECHA,'') as PREAPROBADA_FECHA,
				isnull(f.PREAPROBADA_COMENTARIO,'') as PREAPROBADA_COMENTARIO
				from SGO_PROV_BANCOS_FACTURAS_SUBIDAS f
				left join SERIESUSR u
				on u.usrid = f.Creado_por
				left join SERIESUSR u2
				on u2.usrid = f.Buzon
				left join SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO t
				on t.id = f.TIPO
				LEFT JOIN(
					select  
					sum(Subtotal_0 + Subtotal_12) as total,
					CedBeneficiario
					from SGO_PROV_BANCOS_FACTURAS_SUBIDAS
					where Fecha_creado between '$inicio' and '$fin'
					group by CedBeneficiario
				) as ds on ds.CedBeneficiario = f.CedBeneficiario
				--update SGO_PROV_BANCOS_FACTURAS_SUBIDAS set Buzon = 'KTOMALA'
				where buzon = :buzon
                AND empresa = :empresa
				and f.Estado = :estado
				order by creado_date desc";

			$params = [
				":buzon" => $params['userdata']["usrid"],
				":empresa" => strtoupper($params['empresa']),
				":estado" => $params['estado']
			];
			$result = $this->query($sql, $params);
			return $result;
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error en la consulta: ' . $e->getMessage()
			];
		}
	}



	function GetFacturasDocumentosEspeciales($cedula)
	{

		try {
			$sql = "SELECT
				d.empleado_id,
				d.valor,
				d.empresa,
				d.estado,
				em.Nombre,
				em.Código,
				u.usuario,
				u.nombre as usuario_nombre
				from 
				SGO_PROV_BANCOS_FACTURAS_DOCUMENTOS_ESPECIALES d
				left join EMP_EMPLEADOS em
				on em.id = d.empleado_id
				left join SERIESUSR u
				on u.usrid = d.created_by
				where Código = :cedula
				and estado = 1";

			$params = [
				":cedula" => $cedula
			];
			$result = $this->query($sql, $params);
			return $result;
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error en la consulta: ' . $e->getMessage()
			];
		}

	}



	function GetVacacionesAprobacion($data = [])
	{
		try {

			$sql = "SELECT distinct v.estado as Estado , e.Nombre as Empleado , v.Dia_inicio , v.Dia_fin , v.Dia_regreso ,
			d.Nombre as Departamento , e.email , v.Periodo , e.Código as Cedula , Dias_pendientes , Dias_solicitados
			, e.FechaIngreso , fu.Nombre as Cargo
            , v.ID
            from SGO_VACACIONES_SOLICITADAS_EMPLEADOS v 
            inner join EMP_EMPLEADOS E on v.EmpleadoID = e.ID
			inner join SIS_DEPARTAMENTOS D on d.ID = E.DepartamentoID
			inner join SERIESUSR US on us.EmpleadoId = e.ID
			inner join EMP_EMPLEADOS JF on jf.ID = e.PadreID
			inner join SERIESUSR U on U.EmpleadoId = JF.ID
			inner join EMP_FUNCIONES fu on fu.ID = e.FunciónID
            where U.usrid = :usrid";

			$params = [

				':usrid' => $data['usrid'] ?? null,

			];

			$stmt = $this->query($sql, $params);


			return $stmt;

		} catch (Exception $e) {
			$this->logError("Error en GetVacacionesAprobacion: " . $e->getMessage());
			error_log("Exception in GetVacacionesAprobacion: " . $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}





	function GetVacacionesAprobar($data = [])
	{
		try {

			$sql = "UPDATE  SGO_VACACIONES_SOLICITADAS_EMPLEADOS set estado = :Estado ,
			Aprobado_date = GETDATE() ,
			Aprobado_por = :Creado_Por
			where ID = :ID";

			$params = [
				':Estado' => $data['Estado'] ?? null,
				':ID' => $data['ID'] ?? null,
				':Creado_Por' => $data['Creado_Por'] ?? null
			];

			$stmt = $this->db->execute($sql, $params);

			// Enviar email de confirmación si la actualización fue exitosa
			if ($stmt['success']) {
				try {

					$emailService = new EmailService();

					// Generar PDF de aprobación
					$pdfResult = $this->generarPDFAprobacion($data);

					$pdfPath = null;
					if ($pdfResult['success']) {
						// Guardar PDF temporalmente
						$tempDir = sys_get_temp_dir();
						$pdfPath = $tempDir . '/' . $pdfResult['filename'];
						file_put_contents($pdfPath, $pdfResult['pdfData']);
						error_log("[GetVacacionesAprobar] PDF generado: " . $pdfPath);
					} else {
						error_log("[GetVacacionesAprobar] Error generando PDF: " . $pdfResult['error']);
					}

					// Preparar destinatarios
					$destinatarios = [
						$data['email_empleado'] ?? 'fdelaese@cartimex.com',
						"fdelaese@cartimex.com", // Email de prueba en dominio Cartimex
						"kfranco@cartimex.com" // Email de prueba en dominio Cartimex
					];

					// Datos para la plantilla de vacaciones
					$datos = [
						'empleado_nombre' => $data['Empleado'] ?? 'Empleado',
						'fecha_inicio' => $data['Dia_inicio'] ?? '',
						'fecha_fin' => $data['Dia_fin'] ?? '',
						'fecha_regreso' => $data['Dia_regreso'] ?? '',
						'aprobado_por' => $data['Creado_Por'],
						'estado' => $data['Estado'] == 1 ? 'APROBADA' : 'PENDIENTE'
					];

					// Log para debug
					error_log("[GetVacacionesAprobar] Enviando email a: " . json_encode($destinatarios));
					error_log("[GetVacacionesAprobar] Datos del email: " . json_encode($datos));

					// Preparar documentos adjuntos
					$documentos = [];
					if ($pdfPath && file_exists($pdfPath)) {
						$documentos[] = $pdfPath;
					}

					// Enviar email con PDF adjunto
					$mail = $emailService->enviar(
						$destinatarios,
						'Solicitud de Vacaciones ' . ($datos['estado'] == 'APROBADA' ? 'Aprobada' : 'Actualizada'),
						$datos,
						'vacaciones', // Template de vacaciones
						null,
						"Solicitud de Vacaciones - SGO",
						$documentos, // Adjuntar PDF
						$data["userdata"]["empresa_name"] ?? 'SGO'
					);

					// Eliminar archivo temporal
					if ($pdfPath && file_exists($pdfPath)) {
						unlink($pdfPath);
						error_log("[GetVacacionesAprobar] PDF temporal eliminado");
					}

					error_log("[GetVacacionesAprobar] Resultado del envío: " . ($mail ? 'EXITOSO' : 'FALLIDO'));

					// Agregar información del email al resultado
					$stmt['email_sent'] = true;
					$stmt['email_info'] = $mail;
					$stmt['pdf_generated'] = $pdfResult['success'];
				} catch (Exception $e) {
					error_log("Error enviando email de vacaciones: " . $e->getMessage());
					// No fallar la operación si el email falla
					$stmt['email_sent'] = false;
					$stmt['email_error'] = $e->getMessage();
				}
			}

			return $stmt;

		} catch (Exception $e) {
			$this->logError("Error en GetVacacionesAprobar: " . $e->getMessage());
			error_log("Exception in GetVacacionesAprobar: " . $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}





	function generarPDFAprobacion($data)
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
			$pdf->Cell($lineWidth, 0, utf8_decode(strtoupper(trim($data['Creado_Por']))), 0, 0, 'C');

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





	function GenerarPDFVacacion($data = [])
	{
		try {

			$sql = "SELECT distinct v.estado as Estado , e.Nombre as Empleado , v.Dia_inicio , v.Dia_fin , v.Dia_regreso ,
			d.Nombre as Departamento , e.email , v.Periodo , e.Código as Cedula , Dias_pendientes , Dias_solicitados
			, e.FechaIngreso , fu.Nombre as Cargo
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
			$pdfResult = $this->generarPDFAprobacion2($vacacionData);

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


	function generarPDFAprobacion2($data)
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
			$pdf->Cell($lineWidth, 0, utf8_decode(strtoupper(trim($data['Creado_Por']))), 0, 0, 'C');

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


	
	function GetVacacionesRechazadas($data = [])
	{
		try {

			$sql = "UPDATE  SGO_VACACIONES_SOLICITADAS_EMPLEADOS
			set estado = :Estado , Rechazado_por = :Creado_Por , Rechazado_date = GETDATE() , rechazado_rh_comentario = :Motivo
			where ID = :ID";

			$params = [
				':Estado' => $data['Estado'] ?? null,
				':ID' => $data['ID'] ?? null,
				':Motivo' => $data['Motivo'] ?? null,
				':Creado_Por' => $data['Creado_Por'] ?? null
			];

			$stmt = $this->db->execute($sql, $params);

			// Enviar email de notificación si la actualización fue exitosa
			if ($stmt['success']) {
				try {

					$emailService = new EmailService();

					// Preparar destinatarios
					$destinatarios = [
						//$data['email_empleado'] ?? 'fdelaese@cartimex.com',
						"fdelaese@cartimex.com" // Email de prueba en dominio Cartimex
					];

					// Datos para la plantilla de vacaciones rechazadas
					$datos = [
						'empleado_nombre' => $data['Empleado'] ?? 'Empleado',
						'fecha_inicio' => $data['Dia_inicio'] ?? '',
						'fecha_fin' => $data['Dia_fin'] ?? '',
						'fecha_regreso' => $data['Dia_regreso'] ?? '',
						'rechazado_por' => $data['Creado_Por'],
						'motivo_rechazo' => $data['Motivo'] ?? 'No especificado',
						'estado' => 'RECHAZADA'
					];

					// Log para debug
					error_log("[GetVacacionesRechazadas] Enviando email a: " . json_encode($destinatarios));
					error_log("[GetVacacionesRechazadas] Datos del email: " . json_encode($datos));

					// Enviar email
					$mail = $emailService->enviar(
						$destinatarios,
						'Solicitud de Vacaciones Rechazada',
						$datos,
						'vacaciones_rechazadas', // Template de vacaciones rechazadas
						null,
						"Solicitud de Vacaciones - SGO",
						[],
						$data["userdata"]["empresa_name"] ?? 'SGO'
					);

					error_log("[GetVacacionesRechazadas] Resultado del envío: " . ($mail ? 'EXITOSO' : 'FALLIDO'));

					// Agregar información del email al resultado
					$stmt['email_sent'] = true;
					$stmt['email_info'] = $mail;
				} catch (Exception $e) {
					error_log("Error enviando email de rechazo de vacaciones: " . $e->getMessage());
					// No fallar la operación si el email falla
					$stmt['email_sent'] = false;
					$stmt['email_error'] = $e->getMessage();
				}
			}

			return $stmt;

		} catch (Exception $e) {
			$this->logError("Error en GetVacacionesRechazadas: " . $e->getMessage());
			error_log("Exception in GetVacacionesRechazadas: " . $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}
}
