<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
require_once __DIR__ . '../../../../libs/EmailService.php';

require('fpdf/fpdf.php');
require_once('tcpdf/tcpdf.php');


class VerificacionMotos extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/';
        $this->loadModel('verificacionmotos');
    }

    public function FACTURAS_PENDIENTES_VERIFICAR()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $data = $this->getJsonInput();
        $result = $this->model->FACTURAS_PENDIENTES_VERIFICAR($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener facturas pendientes',
                'respuesta' => $result
            ], 200);
        }
    }

    public function FACTURAS_PENDIENTES_VERIFICAR_DETALLE()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $data = $this->getJsonInput();
        $facturaId = $data['factura_id'] ?? null;

        if (!$facturaId) {
            $this->jsonResponse(['success' => false, 'error' => 'ID de factura no proporcionado'], 400);
            return;
        }

        $result = $this->model->FACTURAS_PENDIENTES_VERIFICAR_DETALLE($facturaId);
        $this->jsonResponse($result, 200);
    }

    public function Guardar_Centro()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;
        $data = $this->getJsonInput();
        $data['usuario'] = $data['userdata']['usuario'] ?? null;
        $result = $this->model->Guardar_Centro($data);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al guardar centro autorizado',
                'respuesta' => $result,
                'parametros' => $data
            ], 200);
        }
    }
    public function Registrar_SRI()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;
        $data = $this->getJsonInput();
        $data['usuario'] = $data['userdata']['usuario'] ?? null;
        $result = $this->model->Registrar_SRI($data);

        if ($result['success']) {
            $emailService = new EmailService();
            $facturaInfo = $result['data'];
            $Facturaid = $data["FACTURA_ID"];
            $adjuntos = [];
            $documentosEncontrados = [];
            $SO = PHP_OS;
            if ($SO != "Linux") {
                $rutaBase = 'C:/xampp/htdocs/sgo_docs/Computronsa/verificacionmotos/';
            } else {
                $rutaBase = '/var/www/html/sgo_docs/Computronsa/verificacionmotos/';
            }
            $documentos = [
                'documento2_firmado' => 'Documento 1 Firmado',
            ];
            foreach ($facturaInfo as $campo => $descripcion) {
                if (!empty($facturaInfo[$campo])) {
                    $nombreDocumento = $facturaInfo[$campo];
                    $rutaArchivo = $rutaBase . $Facturaid . '/' . $nombreDocumento;
                    if (file_exists($rutaArchivo)) {
                        $adjuntos[] = [
                            'ruta' => $rutaArchivo,
                            'nombre' => $nombreDocumento
                        ];
                        $documentosEncontrados[] = $descripcion;
                        // echo json_encode($adjuntos);
                    }
                }
            }
            if (empty($adjuntos)) {
                $adjuntos = null;
            }
            $Detalle = $facturaInfo['Detalle'] ?? 'N/A';
            $mensajeDocumentos = "";
            if (!empty($documentosEncontrados)) {
                $mensajeDocumentos = "<p><strong>Documentos adjuntos:</strong></p><ul>";
                foreach ($documentosEncontrados as $doc) {
                    $mensajeDocumentos .= "<li>$doc  (Cedula, Papeleta de Votacion, RIDE)</li>";
                }
                $mensajeDocumentos .= "</ul><br>";
            }
            $mensaje = "
                <html>
                <head>
                    <title>Registro SRI</title>
                </head>
                <body>
                    <br>
                    <p><strong>Detalles:</strong></p>
                    <ul>
                        <li>Factura: $Facturaid</li>
                        <li>Cliente: $Detalle</li>
                    </ul>
                    <br>
                    $mensajeDocumentos
                </body>
                </html>";

            // $destinatarios = ['azamora@cartimex.com', 'mbarre@cartimex.com', 'jalvarado@cartimex.com'];
            $destinatarios = ['sistema@cartimex.com'];
            $empresa = $data["userdata"]["empleado_empresa"];
            $mail = $emailService->enviar(
                $destinatarios,
                'Notificación: Registro SRI Completado - Factura ' . $Facturaid,
                [],
                null,
                $mensaje,
                null,
                [],
                $empresa
            );

            // Preparar mensaje de respuesta
            $mensajeRespuesta = "Guardado";
            if (!empty($documentosEncontrados)) {
                $cantidadDocs = count($documentosEncontrados);
                $mensajeRespuesta .= " - $cantidadDocs documento(s) enviado(s) por correo";
            }
        }
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al guardar centro autorizado',
                'respuesta' => $result,
                'parametros' => $data
            ], 200);
        }
    }
    public function Registrar_Matricula()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;
        $data = $this->getJsonInput();
        $data['usuario'] = $data['userdata']['usuario'] ?? null;
        $result = $this->model->Registrar_Matricula($data);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al guardar centro autorizado',
                'respuesta' => $result,
                'parametros' => $data
            ], 200);
        }
    }
    public function Registrar_Placa()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;
        $data = $this->getJsonInput();
        $data['usuario'] = $data['userdata']['usuario'] ?? null;
        $result = $this->model->Registrar_Placa($data);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al guardar centro autorizado',
                'respuesta' => $result,
                'parametros' => $data
            ], 200);
        }
    }
    public function Registrar_Guia()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;
        $data = $this->getJsonInput();
        $data['usuario'] = $data['userdata']['usuario'] ?? null;
        $result = $this->model->Registrar_Guia($data);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al guardar centro autorizado',
                'respuesta' => $result,
                'parametros' => $data
            ], 200);
        }
    }
    public function Registrar_Valores_Matriculacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;
        $data = $this->getJsonInput();
        $data['usuario'] = $data['userdata']['usuario'] ?? null;
        $result = $this->model->Registrar_Valores_Matriculacion($data);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al guardar centro autorizado',
                'respuesta' => $result,
                'parametros' => $data
            ], 200);
        }
    }

    public function Mostrar_Series_Disponibles()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $data = $this->getJsonInput();
        $datosFactura = $data['DATOS'] ?? null;

        if (!$datosFactura || !isset($datosFactura[0])) {
            $this->jsonResponse(['success' => false, 'error' => 'Datos no proporcionados'], 400);
            return;
        }

        $result = $this->model->Mostrar_Series_Disponibles($datosFactura[0]);
        $this->jsonResponse($result, 200);
    }

    public function Mostrar_Tiendas_retiro()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $data = $this->getJsonInput();
        $datosFactura = $data['DATOS'] ?? null;

        if (!$datosFactura || !isset($datosFactura[0])) {
            $this->jsonResponse(['success' => false, 'error' => 'Datos no proporcionados'], 400);
            return;
        }

        $result = $this->model->Mostrar_Tiendas_retiro($datosFactura[0]);
        $this->jsonResponse($result, 200);
    }

    public function Guardar_Datos_Serie()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData)
                return;

            $data = $this->getJsonInput();

            if (!isset($data['DATOS_FACTURA']) || !isset($data['DATOS_SERIE'])) {
                $this->jsonResponse(['success' => false, 'error' => 'Datos incompletos'], 400);
                return;
            }

            $data['usuario'] = $jwtData['usuario'] ?? $jwtData['usrid'] ?? null;

            $result = $this->model->Guardar_Datos_Serie($data);

            if ($result && $result['success']) {
                $this->jsonResponse($result, 200);
            } else {
                $this->jsonResponse(['success' => false, 'error' => $result['mensaje'] ?? 'Error', 'detalles' => $result], 200);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function Recibir_moto()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData)
                return;

            $data = $this->getJsonInput();

            if (!isset($data['ARRAY_DATOS_RECIBIR']['FacturaID'])) {
                $this->jsonResponse(['success' => false, 'error' => 'Datos incompletos'], 400);
                return;
            }

            $data['usuario'] = $jwtData['usuario'] ?? $jwtData['usrid'] ?? null;
            $result = $this->model->Recibir_moto($data);
            $this->jsonResponse($result, 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function Entregar_moto()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData)
                return;

            $data = $this->getJsonInput();

            if (!isset($data['ARRAY_DATOS_ENTREGAR']['FacturaID'])) {
                $this->jsonResponse(['success' => false, 'error' => 'Datos incompletos'], 400);
                return;
            }

            $data['usuario'] = $jwtData['usuario'] ?? $jwtData['usrid'] ?? null;
            $result = $this->model->Entregar_moto($data);
            $this->jsonResponse($result, 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function Motos_por_recibir()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData)
                return;

            $params = $this->getJsonInput();
            $params['usuario'] = $jwtData['username'] ?? $jwtData['usrid'] ?? null;
            $params['usrid'] = $params['userdata']['usrid'] ?? null;

            $result = $this->model->Motos_por_recibir($params);
            $this->jsonResponse($result, 200);
            // $this->jsonResponse([$result, $params, $jwtData], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage(), 'params' => $params], 500);
        }
    }

    function Generar_Acta_entrega($param)
    {
        $meses = [
            'January' => 'enero',
            'February' => 'febrero',
            'March' => 'marzo',
            'April' => 'abril',
            'May' => 'mayo',
            'June' => 'junio',
            'July' => 'julio',
            'August' => 'agosto',
            'September' => 'septiembre',
            'October' => 'octubre',
            'November' => 'noviembre',
            'December' => 'diciembre'
        ];
        $date = new DateTime();
        $dia = $date->format('d');
        $mesIngles = $date->format('F');
        $anio = $date->format('Y');
        $mesEsp = $meses[$mesIngles];
        $FECHA_ENTREGA = "$dia días de $mesEsp del $anio";


        $CLIENTE = trim($param["cliente"]);
        $CEDULA = trim($param["Ruc"]);
        $FACTURA = $param["Secuencia"];
        $FACTURA_ID = $param["FacturaID"];
        $SUCURSAL = $param["SUCURSAL_NOMBRE"];

        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain'); // Intenta varias variantes
        $fecha = $param["Fecha"];
        $date = new DateTime($fecha);

        $dia = $date->format('d');
        $mesIngles = $date->format('F');
        $anio = $date->format('Y');
        $mesEsp = $meses[$mesIngles];

        $FECHA_FACTURA = "$dia de $mesEsp del $anio";


        $DATOS_M = $this->Datos_Moto($param["ProductoID"], $param["Serie"], $param["FacturaID"]);
        $DATOS_MOTO = $DATOS_M[0][0];
        $DATOS_F = $DATOS_M[1][0];

        $MARCA = $DATOS_MOTO["Marca"];
        $MODELO = $DATOS_MOTO["Modelo"];
        $AÑO = $DATOS_MOTO["AÑO_MODELO"];
        $COLOR = $DATOS_MOTO["Color"];
        $CHASIS = $DATOS_MOTO["Chasis"];
        $MOTOR = $DATOS_MOTO["Motor"];
        $RAMV = $DATOS_MOTO["Ramv"];
        $PLACA = $DATOS_F["placa_moto"];

        $VALOR_MATRICULA = $param["VALOR_MATRICULA"];
        $VALOR_PLACA = $param["VALOR_PLACA"];
        $VALOR_CERTIFICADO = $param["VALOR_CERTIFICADO"];
        $VALOR_HONORARIOS = $param["VALOR_HONORARIOS"];
        $VALORES_TOTAL = $VALOR_MATRICULA + $VALOR_PLACA + $VALOR_CERTIFICADO + $VALOR_HONORARIOS;

        $COMPAÑIA = "COMPUTRON S.A";
        $CIUDAD = "Guayaquil";

        $ENTREGADO_POR = '';

        $pdf = new TCPDF();
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'ACTA DE ENTREGA-RECEPCIÓN', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 11);

        // Datos generales
        $texto = "$COMPAÑIA en la ciudad de $CIUDAD, a los " . $FECHA_ENTREGA . ", realiza la entrega al Señor(a) " . $CLIENTE . " con Cédula de Identidad/RUC No. " . $CEDULA . " de la Moto que consta en la factura No. " . $FACTURA . " emitida en la Sucursal " . $SUCURSAL . " el " . $FECHA_FACTURA . " cuyas características se detallan a continuación:";

        $pdf->MultiCell(0, 6, $texto);
        $pdf->Ln(4);

        // Tabla de características
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(35, 6, 'MARCA:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, $MARCA, 0, 1);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(35, 6, 'MODELO:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, $MODELO, 0, 1);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(35, 6, 'AÑO:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, $AÑO, 0, 1);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(35, 6, 'COLOR:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, $COLOR, 0, 1);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(35, 6, 'CHASIS:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, $CHASIS, 0, 1);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(35, 6, 'MOTOR:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, $MOTOR, 0, 1);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(35, 6, 'RAMV:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, $RAMV, 0, 1);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(35, 6, 'PLACA:', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, $PLACA, 0, 1);
        $pdf->Ln(6);

        // Texto de gastos
        $pdf->SetFont('helvetica', '', 11);
        $gastos = "GASTOS Conforme consta en el contrato, la compañía " . $COMPAÑIA . " se hizo cargo de todos los gastos y gestiones relacionados con la matriculación y demás gastos legales que se requirieron para la inicial circulación del vehículo, esto es: Matrícula, Placa, Certificado de Revisión y otros. Para tal efecto se incurrió en un gasto total de USD " . $VALORES_TOTAL . " el cual el comprador reconoce que fue asumido completamente por la compañía.";

        $pdf->MultiCell(0, 6, trim($gastos));
        $pdf->Ln(4);

        // Tabla de gastos
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(90, 8, 'Detalle', 1, 0, 'C');
        $pdf->Cell(40, 8, 'Valor US$', 1, 1, 'C');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(90, 8, 'Matrícula vehicular', 1, 0);
        $pdf->Cell(40, 8, $VALOR_MATRICULA, 1, 1, 'R'); //'74.45'
        $pdf->Cell(90, 8, 'Placa', 1, 0);
        $pdf->Cell(40, 8, $VALOR_PLACA, 1, 1, 'R'); //'13.00'
        $pdf->Cell(90, 8, 'Certificado de revision veh.', 1, 0);
        $pdf->Cell(40, 8, $VALOR_CERTIFICADO, 1, 1, 'R');  //'30.00'
        $pdf->Cell(90, 8, 'Honorarios Matriculación ', 1, 0);
        $pdf->Cell(40, 8, $VALOR_HONORARIOS, 1, 1, 'R');  //'30.00'
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(90, 8, 'Total Gastos', 1, 0);
        $pdf->Cell(40, 8, $VALORES_TOTAL, 1, 1, 'R');
        $pdf->Ln(6);

        // Pie de texto
        $final = "La Moto es entregada en perfecto estado y a entera satisfacción del Cliente. " . $COMPAÑIA . ", no se responsabiliza por el uso y trato de la misma. Se deja constancia de que se ha informado al Cliente de todos los requisitos y leyes que rigen la tenencia de este vehículo.";
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 6, $final);
        $pdf->Ln(10);

        // Firmas
        $pdf->Cell(90, 6, 'ENTREGADO POR', 0, 0, 'C');
        $pdf->Cell(90, 6, 'RECIBIDO POR', 0, 1, 'C');
        $pdf->Ln(15);
        $pdf->Cell(90, 6, '_________________________', 0, 0, 'C');
        $pdf->Cell(90, 6, '_________________________', 0, 1, 'C');
        $pdf->Cell(90, 6, $ENTREGADO_POR, 0, 0, 'C');
        $pdf->Cell(90, 6, $CLIENTE, 0, 1, 'C');
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(90, 6, 'C.I. No. ', 0, 0, 'C');
        $pdf->Cell(90, 6, 'C.I. No. ' . $CEDULA, 0, 1, 'C');

        // $pdf->Output('I', 'acta_entrega.pdf');

        $SO = PHP_OS;
        // echo $SO;
        if ($SO != "Linux") {
                $ruta_pdf = 'C:/xampp/htdocs/sgo_docs/Computronsa/verificacionmotos/';
            } else {
                $ruta_pdf = '/var/www/html/sgo_docs/Computronsa/verificacionmotos/';
            }

        $CARPETA = $ruta_pdf . $FACTURA_ID;

        if (!is_dir($CARPETA)) {
            mkdir($CARPETA, 0777, true); // 0755 = permisos, true = crea carpetas anidadas
        }

        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);

        $NOMBRE_DOC = "acta_" . $FACTURA_ID . "_" . $CEDULA . ".pdf";

        // chmod($ruta_pdf, 0777);


        // $pdf->Output($ruta_pdf . $NOMBRE_DOC, 'F'); // Guarda el PDF en la carpeta creada
        if (!$pdf->Output($CARPETA . "/" . $NOMBRE_DOC, 'F')) {
            // echo "❌ Error al guardar el PDF en: $RUTA_COMPLETA<br>";
            // var_dump(error_get_last());
            // exit();
        } else {
            // echo "✅ PDF guardado correctamente en: $RUTA_COMPLETA<br>";
        }
        $res = array(
            "success" => true,
            "ruta" => $ruta_pdf,
            "NOMBRE_DOC" => $NOMBRE_DOC,
            "param" => $param,
            "datos_moto" => $DATOS_MOTO,
            "usrid" => $_SESSION["usrid"],
            "FACTURA_ID" => $FACTURA_ID,
        );

        return $this->jsonResponse($res);
    }

    function Generar_Datos_ATM()
    {
        $param = $this->getJsonInput();

        $campos_requeridos = [
            "FacturaID" => "Factura ID",
            "Sexo" => "Sexo",
            "Nacimiento" => "Fecha de Nacimiento",
            "Provincia" => "Provincia",
            "Ciudad" => "Ciudad",
            "Dirección" => "Dirección",
            "Email" => "Correo Electrónico"
        ];


        $campos_faltantes = [];
        foreach ($campos_requeridos as $campo => $nombre) {
            if (!isset($param[$campo]) || trim($param[$campo]) === '') {
                $campos_faltantes[] = $nombre;
            }
        }

        if (!empty($campos_faltantes)) {
            $mensaje = "Los siguientes campos son obligatorios: " . implode(", ", $campos_faltantes);
            $res = array(
                "success" => false,
                "mensaje" => $mensaje
            );
            return $this->jsonResponse($res);
        }

        if (trim($param["Ruc"]) === '' && trim($param["Cédula"]) === '') {
            $res = array(
                "success" => false,
                "mensaje" => "Debe proporcionar al menos un número de identificación (RUC o Cédula)"
            );
            return $this->jsonResponse($res);
        }
        if (trim($param["cliente"]) === '' && trim($param["PrimerNombre"]) === '' && trim($param["PrimerApellido"]) === '' && trim($param["SegundoApellido"]) === '' && trim($param["SegundoNombre"]) === '') {
            $res = array(
                "success" => false,
                "mensaje" => "Debe proporcionar al menos dato para el cliente"
            );
            return $this->jsonResponse($res);
        }

        if (trim($param["Teléfono1"]) === '' && trim($param["TMovil"]) === '') {
            $res = array(
                "success" => false,
                "mensaje" => "Debe proporcionar al menos un número de teléfono"
            );
            return $this->jsonResponse($res);
        }

        // Asignar valores después de la validación
        $CLIENTE = trim($param["cliente"]);
        $partes = preg_split('/\s+/', $CLIENTE);
        $cantidad = count($partes);
        if ($cantidad == 3) {
            $param["nombres"] = $partes[0] . ' ' . $partes[1];
            $param["apellidos"] = $partes[2];
        } elseif ($cantidad == 4) {
            $param["apellidos"] = $partes[0] . ' ' . $partes[1];
            $param["nombres"] = $partes[2] . ' ' . $partes[3];
        } elseif ($cantidad > 4) {
            $param["nombres"] = $partes[$cantidad - 2] . ' ' . $partes[$cantidad - 1];
            $param["apellidos"] = implode(' ', array_slice($partes, 0, $cantidad - 2));
        } else {
            $param["nombres"] = $partes[0];
            $param["apellidos"] = isset($partes[1]) ? $partes[1] : '';
        }

        if (strlen(trim($param["Ruc"])) >= 13) {
            $param["nombres"] = $param["cliente"];
        }


        $CEDULA = !empty(trim($param["Ruc"])) ? trim($param["Ruc"]) : trim($param["Cédula"]);
        $FACTURA_ID = trim($param["FacturaID"]);
        $nombres = trim($param["PrimerNombre"]) . ' ' . trim($param["SegundoNombre"]);
        $apellidos = trim($param["PrimerApellido"]) . ' ' . trim($param["SegundoApellido"]);
        $fechaNacimiento = date('d/m/Y', strtotime(trim($param["Nacimiento"])));
        $sexo = (trim($param["Sexo"]) === 'M') ? 'Masculino' : 'Femenino';
        $celular = trim($param["TMovil"]);
        $provincia = trim($param["Provincia"]);
        $canton = trim($param["Ciudad"]);
        $direccion = trim($param["Dirección"]);
        $telefonoDomicilio = !empty(trim($param["Teléfono1"])) ? trim($param["Teléfono1"]) : trim($param["TMovil"]);
        $correo = trim($param["Email"]);


        if (strlen(trim($param["Ruc"])) >= 13) {
            $nombres = $param["cliente"];
        }

        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);

        // HEADER con imagen y text
        $pdf->Image('public\atm.png', 10, 10, 50);
        $pdf->SetXY(60, 20);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'DECLARACIÓN DE DATOS', 0, 1, 'L');
        $pdf->Ln(8);

        // Línea separadora
        $pdf->SetLineWidth(0.5);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->SetLineWidth(0);
        $pdf->Ln(3);

        // --- DATOS PERSONALES ---
        $pdf->SetX(15);
        $pdf->SetFont('helvetica', 'BU', 12);
        $pdf->Cell(0, 8, 'DATOS PERSONALES', 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(60, 6, 'N° Documento de Identidad:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 6, $CEDULA, 'B', 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(60, 6, 'Nombres:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 6, $nombres, 'B', 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(60, 6, 'Apellidos:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 6, $apellidos, 'B', 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(60, 6, 'Fecha Nacimiento:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 6, $fechaNacimiento, 'B', 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(60, 6, 'Sexo:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 6, $sexo, 'B', 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(60, 6, 'Teléfono Celular:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 6, $celular, 'B', 1);

        // --- DOMICILIO ---
        $pdf->Ln(2);
        $pdf->SetX(15);
        $pdf->SetFont('helvetica', 'BU', 12);
        $pdf->Cell(0, 8, 'DOMICILIO DE LA PERSONA', 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(60, 6, 'Provincia:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 6, $provincia, 'B', 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(60, 6, 'Cantón:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 6, $canton, 'B', 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(60, 6, 'Dirección Domicilio:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 6, $direccion, 'B', 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(60, 6, 'Teléfono Domicilio:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 6, $telefonoDomicilio, 'B', 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(15);
        $pdf->Cell(60, 6, 'Correo Electrónico:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 6, $correo, 'B', 1);

        // --- BLOQUE LEGAL ---
        $pdf->Ln(5);
        $textoLey = "Con el fin de poder cumplir con lo estipulado en la Ley Orgánica de Transporte Terrestre, Tránsito y Seguridad Vial (LOTTTSV): Art. 179, incisos 2 y 3 donde se indica que las contravenciones de tránsito podrán ser también detectadas y notificadas por medios electrónicos y tecnológicos y donde se indica también que los registros electrónicos de los sistemas de seguridad, cámaras de vigilancia de seguridad y otros implementados por las instituciones públicas, o los GAD's, serán consideradas pruebas suficientes para la aplicación de los delitos y contravenciones; y de acuerdo a lo que se estipula en el Reglamento para la Aplicación de la LOTTTSV: Art. 238 en el cual se indica que: 'el propietario de un vehículo está obligado, al momento de su matriculación y revisión anual o semestral, a proporcionar una dirección de correo electrónico a fin de ser notificado con las citaciones que se detecten por medios electrónicos y/o tecnológicos. La misma obligación tendrán las personas que renueven sus licencias de conducir. Para tales efectos, se suscribirá una declaración en la que el propietario del vehículo consigne una dirección de correo electrónico que se comprometa a revisar periódicamente y acepte que las citaciones enviadas a esa dirección electrónica se entenderán como válidamente notificadas.'";

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetLineWidth(0.5);
        $pdf->MultiCell(0, 5.5, $textoLey, 1, 'L');
        $pdf->SetLineWidth(0);

        // --- DECLARACIÓN ---
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'DECLARACIÓN', 0, 1, 'C');

        $declaracion = "Libre y voluntariamente declaro que los datos consignados en este documento son ciertos y autorizo para que los mismos sean utilizados por la Agencia Nacional de Tránsito, la Autoridad de Tránsito Municipal demás organismos de tránsito competentes, para que estas instituciones puedan realizar cualquier notificación de infracciones de tránsito a las direcciones consignadas, entendiéndolas como válidamente notificadas. Me comprometo a revisar este o estos correos electrónicos consignados en este Documento y mantener actualizado de manera periódica los datos personales que estipula la ley.";

        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 5.5, $declaracion, 0, 'L');

        // --- FIRMAS ---
        $pdf->Ln(12);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 6, 'Nombre:', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(90, 6, $nombres . ' ' . $apellidos, 'T', 1, 'C');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 6, 'Cédula/Ruc:', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(90, 6, $CEDULA, 0, 1, 'C');

        // --- NOTA FINAL ---
        $pdf->Ln(6);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->MultiCell(0, 5, "Nota: Estos datos serán utilizados única y exclusivamente para fines de comunicación y notificación, no tienen fines tributarios y no serán entregados a terceros sin su consentimiento.",0, 'L');

        // $pdf->Output('I', 'Declaracion_Datos_ATM.pdf');

        $SO = PHP_OS;
        // echo $SO;
        if ($SO != "Linux") {
                $ruta_pdf = 'C:/xampp/htdocs/sgo_docs/Computronsa/verificacionmotos/';
            } else {
                $ruta_pdf = '/var/www/html/sgo_docs/Computronsa/verificacionmotos/';
            }

        $CARPETA = $ruta_pdf . $FACTURA_ID;

        if (!is_dir($CARPETA)) {
            mkdir($CARPETA, 0777, true); // 0755 = permisos, true = crea carpetas anidadas
        }

        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);

        $NOMBRE_DOC = "declaracion_datos_atm_" . $FACTURA_ID . "_" . $CEDULA . ".pdf";

        // chmod($ruta_pdf, 0777);


        // $pdf->Output($ruta_pdf . $NOMBRE_DOC, 'F'); // Guarda el PDF en la carpeta creada
        if (!$pdf->Output($CARPETA . "/" . $NOMBRE_DOC, 'F')) {
            // echo "❌ Error al guardar el PDF en: $RUTA_COMPLETA<br>";
            // var_dump(error_get_last());
            // exit();
        } else {
            // echo "✅ PDF guardado correctamente en: $RUTA_COMPLETA<br>";
        }
        $res = array(
            "success" => true,
            "ruta" => $ruta_pdf,
            "NOMBRE_DOC" => $NOMBRE_DOC,
            "FACTURA_ID" => $FACTURA_ID
        );

        return $this->jsonResponse($res);
    }

    function Generar_Contrato_CompraVenta()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;
        $param = $this->getJsonInput();
        $result = $this->model->Generar_Contrato_CompraVenta($param["FacturaID"]);
        if ($result && $result['success']) {

            $result = $result['data'];

            $campos_requeridos = [
                "cliente" => "Cliente",
                "FacturaID" => "Factura ID",
                "ProductoID" => "Producto ID",
                "Dirección" => "Domicilio",
                "Plazo" => "Plazo",
                "Interes" => "Interes",
                "FechaPrimerPago" => "Fecha de Inicio",
                "Valor" => "Valor Total",
                "Serie" => "Serie",
            ];

            foreach ($campos_requeridos as $campo => $nombre) {
                if (!isset($param[$campo]) || trim($param[$campo]) === '') {
                    $res = array(
                        "success" => false,
                        "mensaje" => "El campo {$nombre} no puede estar vacío"
                    );
                    return $this->jsonResponse($res);
                }
            }
            if (trim($param["Ruc"]) === '' && trim($param["Cédula"]) === '') {
                $res = array(
                    "success" => false,
                    "mensaje" => "Debe proporcionar al menos un número de identificación (RUC o Cédula)"
                );
                return $this->jsonResponse($res);
            }
            $CLIENTE = trim($param["cliente"]);
            $partes = preg_split('/\s+/', $CLIENTE);
            $cantidad = count($partes);
            if ($cantidad == 3) {
                $param["nombres"] = trim($partes[0] . ' ' . $partes[1]);
                $param["apellidos"] = trim($partes[2]);
            } elseif ($cantidad == 4) {
                $param["apellidos"] = trim($partes[0] . ' ' . $partes[1]);
                $param["nombres"] = trim($partes[2] . ' ' . $partes[3]);
            } elseif ($cantidad > 4) {
                $param["nombres"] = trim($partes[$cantidad - 2] . ' ' . $partes[$cantidad - 1]);
                $param["apellidos"] = trim(implode(' ', array_slice($partes, 0, $cantidad - 2)));
            } else {
                $param["nombres"] = trim($partes[0]);
                $param["apellidos"] = isset($partes[1]) ? trim($partes[1]) : '';
            }

            $CEDULA = !empty(trim($param["Ruc"])) ? trim($param["Ruc"]) : trim($param["Cédula"]);
            $FACTURA_ID = trim($param["FacturaID"]);

            $DATOS_RES = $this->model->Datos_Moto($param["ProductoID"], $param["Serie"], $param["FacturaID"]);
            $DATOS_MOTO = $DATOS_RES['data'][0] ?? [];


            $articulo = "MOTO";

            $MARCA = trim($DATOS_MOTO["Marca"]);
            $MODELO = trim($DATOS_MOTO["Modelo"]);
            $AÑO = trim($DATOS_MOTO["AÑO_MODELO"]);
            $COLOR = trim($DATOS_MOTO["Color"]);
            $CHASIS = trim($DATOS_MOTO["Chasis"]);
            $MOTOR = trim($DATOS_MOTO["Motor"]);

            $empresaNombre = "COMPUTRONSA S.A";
            $empresaDireccion = "Av. Francisco de Orellana Sl. 31 Mz. 110";
            $gerenteNombre = "Jose Luis Larrea Jurado";
            $gerenteCedula = "0991400427001";

            $compradorNombre = trim($param["cliente"]);
            $compradorCedula = !empty(trim($param["Ruc"])) ? trim($param["Ruc"]) : trim($param["Cédula"]);
            $domicilio = trim($param["Dirección"]);

            $cuotas = intval(trim($result[0]["Plazo"]));

            // Calcular el valor total como la suma de todas las cuotas
            $valorTotal = 0;
            for ($i = 0; $i < $cuotas; $i++) {
                $valorTotal += floatval(trim($result[$i]["Cuota"]));
            }

            $precioTexto = $this->numero_a_letras($valorTotal);
            $fechahoy = date("Y-m-d");
            $fechahoy = new DateTime($fechahoy);

            $pdf = new TCPDF();
            $pdf->AddPage();
            $pdf->SetMargins(15, 10, 15);
            $pdf->SetAutoPageBreak(true, 15);

            // TÍTULO
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Contrato de Compra - Venta con Reserva de Dominio', 0, 1, 'C');
            $pdf->Ln(8);

            // INICIO DE CONTENIDO


            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "Conste por el presente instrumento de Contrato Compraventa con Reserva de Dominio que se otorga de conformidad con lo que dispone la Ley y de acuerdo a las siguientes cláusulas:");
            $pdf->Ln(10);

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "PRIMERA.- COMPARECIENTES: ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "Comparecen a la celebración del presente Contrato de Compraventa con Reserva de Dominio por una parte $empresaNombre con domicilio en $empresaDireccion debidamente representado por su Gerente y Representante Legal, ");
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "$gerenteNombre ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "con C.C. ");
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "$gerenteCedula ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "de nacionalidad ecuatoriana, compañía a quien también se denominará propietario, vendedor o acreedor; y por otra parte ");
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "$compradorNombre ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "con C.C. ");
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "$compradorCedula ");
            $pdf->SetFont('helvetica', '', 10);
            // $pdf->Write(6, "de nacionalidad ecuatoriana de profesión $profesion domiciliado en $domicilio a quien también se llamará comprador o deudor."));
            $pdf->Write(6, "de nacionalidad ecuatoriana domiciliado en $domicilio a quien también se llamará comprador o deudor.");

            $pdf->Ln(10);

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "SEGUNDA.- DESCRIPCIÓN DE LOS BIENES: ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "El vendedor es propietario de los bienes muebles, cuyas características y valores se describen:  ....................");
            $pdf->Ln(7);

            // DATOS DE LA MOTO
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(40, 6, "ARTICULO:", 0, 0);
            $pdf->Cell(0, 6, $articulo, 0, 1);
            $pdf->Cell(40, 6, "MARCA:", 0, 0);
            $pdf->Cell(0, 6, $MARCA, 0, 1);
            $pdf->Cell(40, 6, "COLOR:", 0, 0);
            $pdf->Cell(0, 6, $COLOR, 0, 1);
            $pdf->Cell(40, 6, "MODELO:", 0, 0);
            $pdf->Cell(0, 6, $MODELO, 0, 1);
            $pdf->Cell(40, 6, "AÑO:", 0, 0);
            $pdf->Cell(0, 6, $AÑO, 0, 1);
            $pdf->Cell(40, 6, "MOTOR:", 0, 0);
            $pdf->Cell(0, 6, $MOTOR, 0, 1);
            $pdf->Cell(40, 6, "CHASIS:", 0, 0);
            $pdf->Cell(0, 6, $CHASIS, 0, 1);
            $pdf->Ln(5);


            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "TERCERA.- COMPRAVENTA: ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "El propietario vende los objetos detallados en la cláusula que antecede al comprador ");
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "$compradorNombre ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "reservándose el derecho de dominio que tiene sobre ellos la total cancelación del precio estipulado es decir, que la venta se perfeccionará en el instante en que sea cubierta la totalidad del precio pactado.");
            $pdf->Ln(10);


            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "CUARTA.- INSCRIPCIÓN:");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "ART.357. Tanto el contrato de venta con reserva de dominio, como sus cesiones, de haberlas, se formalizaran por escrito, se suscribirán por las partes y se lo inscribirá en Registro Mercantil de la jurisdicción donde sea entregada físicamente la cosa.");
            $pdf->Ln(10);


            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "QUINTA.- PRECIO: ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, ": El precio pactado por los objetos materia de la compraventa es el de... $precioTexto ... Por el que ha suscrito las siguientes obligaciones:");
            $pdf->Ln(10);


            // CUOTAS
            $anchoTabla = 120;
            $margenIzquierdo = ($pdf->GetPageWidth() - $anchoTabla) / 2;

            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetX($margenIzquierdo);
            $pdf->Cell(40, 6, 'C.I.', 1, 0, 'C');
            $pdf->Cell(40, 6, 'Fecha', 1, 0, 'C');
            $pdf->Cell(40, 6, 'Valor', 1, 1, 'C');

            // Primera fila: cuota inicial $0.00
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX($margenIzquierdo);
            $pdf->Cell(40, 6, 'C.I.', 1, 0, 'C');
            $pdf->Cell(40, 6, $fechahoy->format('d/m/Y'), 1, 0, 'C');
            $pdf->Cell(40, 6, '$0.00', 1, 1, 'C');

            // Cuotas automáticas
            for ($i = 0; $i < $cuotas; $i++) {
                $ci = "L/$i/$cuotas";
                $montoCuota = trim($result[$i]["Cuota"]);
                $fecha = new DateTime(trim($result[$i]["Fecha"]));
                $fechaPago = clone $fecha;

                $pdf->SetX($margenIzquierdo);
                $pdf->Cell(40, 6, $ci, 1, 0, 'C');
                $pdf->Cell(40, 6, $fechaPago->format('d/m/Y'), 1, 0, 'C');
                $pdf->Cell(40, 6, '$' . number_format($montoCuota, 2), 1, 1, 'C');
            }
            $pdf->Ln(10);


            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "la misma que se encuentra garantizada por el señor $compradorNombre quien se somete expresamente a la estipulaciones de este contrato.");
            $pdf->Ln(10);


            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "SEXTA.- RECEPCIÓN Y DOMICILIO: ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "Lo descrito en la cláusula segunda lo ha recibido el comprador a su entera satisfacción y deberá conservarlo y mantenerlo durante la vigencia del contrato en su domicilio antes mencionado, con la obligación de notificar al vendedor el cambio de su domicilio o residencia, a más tardar dentro de los ocho días posteriores a dicho cambio. En ningún caso, podrá el comprador sacar fuera del país lo que es objeto de este contrato, ni entregarlo a otras personas sin autorización escrita del vendedor.");
            $pdf->Ln(10);


            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "SÉPTIMA.- RESERVA DE DOMINIO: ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "El vendedor se reserva el dominio de lo vendido hasta que el comprador haya pagado la totalidad del precio. El comprador adquirirá el dominio, esto es, será dueño de la cosa vendida únicamente cuando, haya pagado la totalidad del precio, y podrá entonces pedir al vendedor que le otorgue el respectivo Titulo de Propiedad. Sin embargo, el comprador asume los riesgos que corre tal cosa desde la fecha de este contrato por haberla recibido del poder del vendedor. En consecuencia, el comprador no podrá verificar contrato alguno de venta permuta, arrendamiento, prenda, etc., sobre lo que es objeto de este contrato mientras no haya pagado le totalidad del precio.");
            $pdf->Ln(10);


            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "OCTAVA.- FALTA DE PAGO: ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "Si el comprador no pagare uno de los documentos indicados en este contrato, el vendedor tiene derecho a dar por vencidos los plazos fijados y tienen la opción de proceder en cualquiera de las siguientes formas:
    1. Acudir a un Juez competente presentando el respectivo contrato y el certificado otorgado por el Registrador Mercantil, para que el Juez disponga que uno de los alguaciles aprehendan las cosas materia de este contrato donde quiera que se encuentren al vendedor, o si el vendedor lo prefiere podrá pedir al Juez que disponga el remate del o de los objetos vendidos con reserva de dominio de acuerdo con lo dispuesto en este Código y en las disposiciones generales pertinentes del procedimiento civil ecuatoriano, pudiendo además alternativamente, proceder conforme al tramite establecido para el remate de la prenda comercial. (Art. 361 del código de comercio)
2. En este caso, las cuotas parciales pagadas en concepto del precio quedará en beneficio del vendedor a título de indemnización, pero ésta, en ningún caso podrá exceder de la tercera parte del precio fijado en el contrato, incluido la cuota de contado; si las cantidades abonadas excedieren de la tercera parte el vendedor devolverá dicho exceso al comprador. Sin embargo, el comprador podrá recuperar los objetos adquiridos si dentro de los quince días posteriores a dicho vencimiento, se pone al día en el pago de las cuotas u ofrece suficiente garantía a satisfacción del vendedor; o
El producto del remate se aplicará al pago de las cuotas vencidas y a cubrir además los gastos del remate, debiendo entregarse al comprador el saldo que hubiere. Si dicho producto no alcanzaren cubrir el valor del crédito, el vendedor podrá iniciar una nueva acción contra el comprador para obtener la cancelación del saldo que le quedare adeudando inclusive los gastos judiciales.");
            $pdf->Ln(10);


            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "NOVENA.- LEGISLACIÓN: ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "En todo cuanto no estuviere previsto en este contrato, se someten las partes al código de comercio y demás leyes conexas.");
            $pdf->Ln(10);


            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "DÉCIMA.- JURISDICCIÓN: ");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "Las partes contratantes dejan constancia y estipulan expresamente que se someten a la jurisdicción y competencia de los Jueces de lo Civil de Guayaquil para resolver toda divergencia o juicio que pudiese resultar de este contrato, y señalan como domicilio la ciudad de Guayaquil, para lo cual renuncian domicilio, señalando expresamente a Guayaquil como sus domicilios legales.");
            $pdf->Ln(10);


            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Write(6, "DÉCIMA PRIMERA.- ACEPTACIÓN y RATIFICACIÓN:");
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Write(6, "Las partes declaran expresamente que aceptan todas y cada una de las cláusulas del presente contrato, por así convenir a sus intereses y se ratifican en el pleno conocimiento del contenido, alcance y significado del contrato, firmando en unidad de acto.");
            $pdf->Ln(10);

            $pdf->Ln(5);

            // Pie
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(95, 6, 'Ciudad: GUAYAQUIL', 0, 0, 'L');
            $pdf->Cell(95, 6, 'Fecha: ' . $fechahoy->format('d/m/Y'), 0, 1, 'R');
            $pdf->Ln(10);


            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetX(40);
            $pdf->Cell(0, 6, 'Por: Computron S.A.', 0, 1, 'L');
            $pdf->SetX(45);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, 'RUC: 1790001590001', 0, 1, 'L');


            $pdf->Ln(25);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(90, 6, '...................................................', 0, 0, 'C');
            $pdf->Cell(90, 6, '...................................................', 0, 1, 'C');

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(90, 6, $gerenteNombre, 0, 0, 'C');
            $pdf->Cell(90, 6, $compradorNombre, 0, 1, 'C');
            $pdf->Cell(90, 6, $gerenteCedula, 0, 0, 'C');
            $pdf->Cell(90, 6, "C.C. Nº $compradorCedula", 0, 1, 'C');
            $pdf->Cell(90, 6, 'Gerente', 0, 0, 'C');

            // Salida
            // $pdf->Output('I', 'Contrato_Compra_Venta.pdf');

            $SO = PHP_OS;
            if ($SO != "Linux") {
                $ruta_pdf = 'C:/xampp/htdocs/sgo_docs/Computronsa/verificacionmotos/';
            } else {
                $ruta_pdf = '/var/www/html/sgo_docs/Computronsa/verificacionmotos/';
            }

            $CARPETA = $ruta_pdf . $FACTURA_ID;

            if (!is_dir($CARPETA)) {
                mkdir($CARPETA, 0777, true); // 0755 = permisos, true = crea carpetas anidadas
            }

            // ini_set('display_errors', 1);
            // ini_set('display_startup_errors', 1);
            // error_reporting(E_ALL);

            $NOMBRE_DOC = "contrato_compra_venta_" . $FACTURA_ID . "_" . $CEDULA . ".pdf";

            // chmod($ruta_pdf, 0777);


            // $pdf->Output($ruta_pdf . $NOMBRE_DOC, 'F'); // Guarda el PDF en la carpeta creada
            if (!$pdf->Output($CARPETA . "/" . $NOMBRE_DOC, 'F')) {
                // echo "❌ Error al guardar el PDF en: $RUTA_COMPLETA<br>";
                // var_dump(error_get_last());
                // exit();
            } else {
                // echo "✅ PDF guardado correctamente en: $RUTA_COMPLETA<br>";
            }
            $res = array(
                "success" => true,
                "ruta" => $ruta_pdf,
                "NOMBRE_DOC" => $NOMBRE_DOC,
                "FACTURA_ID" => $FACTURA_ID,
            );
            return $this->jsonResponse($res);
        } else {
            return $this->jsonResponse(array(
                "success" => false,
                "mensaje" => "Error al ejecutar GENERAR_CONTRATO_COMPRA_VENTA",
            ));
        }
    }
    function Datos_Moto($PRODUCTOID, $serie, $Facturaid)
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData)
                return;
            $result = $this->model->Datos_Moto($PRODUCTOID, $serie, $Facturaid);
            if ($result && $result['success']) {
                return $this->jsonResponse($result, 200);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Error al obtener datos de la moto',
                    'respuesta' => $result
                ], 200);
            }
        } catch (PDOException $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Error de base de datos: ' . $e->getMessage()
            ], 500);
        }
        return [];
    }

    private function numero_a_letras($numero)
    {
        $unidades = array(
            '',
            'UNO',
            'DOS',
            'TRES',
            'CUATRO',
            'CINCO',
            'SEIS',
            'SIETE',
            'OCHO',
            'NUEVE'
        );
        $decenas = array(
            '',
            'DIEZ',
            'VEINTE',
            'TREINTA',
            'CUARENTA',
            'CINCUENTA',
            'SESENTA',
            'SETENTA',
            'OCHENTA',
            'NOVENTA'
        );
        $especiales = array(
            11 => 'ONCE',
            12 => 'DOCE',
            13 => 'TRECE',
            14 => 'CATORCE',
            15 => 'QUINCE',
            16 => 'DIECISÉIS',
            17 => 'DIECISIETE',
            18 => 'DIECIOCHO',
            19 => 'DIECINUEVE',
            21 => 'VEINTIUNO',
            22 => 'VEINTIDÓS',
            23 => 'VEINTITRÉS',
            24 => 'VEINTICUATRO',
            25 => 'VEINTICINCO',
            26 => 'VEINTISÉIS',
            27 => 'VEINTISIETE',
            28 => 'VEINTIOCHO',
            29 => 'VEINTINUEVE'
        );
        $centenas = array(
            '',
            'CIENTO',
            'DOSCIENTOS',
            'TRESCIENTOS',
            'CUATROCIENTOS',
            'QUINIENTOS',
            'SEISCIENTOS',
            'SETECIENTOS',
            'OCHOCIENTOS',
            'NOVECIENTOS'
        );

        $partes = explode(".", $numero);
        $entero = intval($partes[0]);
        $decimal = isset($partes[1]) ? intval($partes[1]) : 0;

        if ($entero == 0) {
            $texto = "CERO";
        } else {
            $texto = "";
            $grupos = array();

            // Procesar grupos de 3 dígitos
            while ($entero > 0) {
                $grupos[] = $entero % 1000;
                $entero = floor($entero / 1000);
            }

            $grupos = array_reverse($grupos);
            $nGrupos = count($grupos);

            foreach ($grupos as $i => $grupo) {
                if ($grupo == 0)
                    continue;

                $centena = floor($grupo / 100);
                $decena = floor(($grupo % 100) / 10);
                $unidad = $grupo % 10;

                if ($centena > 0) {
                    if ($grupo == 100) {
                        $texto .= "CIEN";
                    } else {
                        $texto .= $centenas[$centena];
                    }
                }

                if ($grupo % 100 > 0) {
                    if (isset($especiales[$grupo % 100])) {
                        $texto .= $especiales[$grupo % 100];
                    } else {
                        if ($decena > 0) {
                            $texto .= $decenas[$decena];
                            if ($unidad > 0) {
                                $texto .= " Y " . $unidades[$unidad];
                            }
                        } else if ($unidad > 0) {
                            $texto .= $unidades[$unidad];
                        }
                    }
                }

                // Agregar sufijos para miles, millones, etc.
                if ($i < $nGrupos - 1) {
                    if ($grupo == 1) {
                        if ($nGrupos - $i == 2)
                            $texto .= " MIL ";
                        else if ($nGrupos - $i == 3)
                            $texto .= " MILLÓN ";
                        else if ($nGrupos - $i == 4)
                            $texto .= " MIL MILLONES ";
                    } else {
                        if ($nGrupos - $i == 2)
                            $texto .= " MIL ";
                        else if ($nGrupos - $i == 3)
                            $texto .= " MILLONES ";
                        else if ($nGrupos - $i == 4)
                            $texto .= " MIL MILLONES ";
                    }
                }
            }
        }

        $texto .= " DÓLARES";

        if ($decimal > 0) {
            $texto .= " CON " . $this->convertirDecimal($decimal) . " CENTAVOS";
        }
        return $texto;
    }

    private function convertirDecimal($decimal)
    {
        $unidades = array(
            '',
            'UNO',
            'DOS',
            'TRES',
            'CUATRO',
            'CINCO',
            'SEIS',
            'SIETE',
            'OCHO',
            'NUEVE'
        );
        $decenas = array(
            '',
            'DIEZ',
            'VEINTE',
            'TREINTA',
            'CUARENTA',
            'CINCUENTA',
            'SESENTA',
            'SETENTA',
            'OCHENTA',
            'NOVENTA'
        );

        if ($decimal < 10) {
            return $unidades[$decimal];
        } else if ($decimal < 100) {
            $d = floor($decimal / 10);
            $u = $decimal % 10;
            if ($u == 0) {
                return $decenas[$d];
            } else {
                return $decenas[$d] . " Y " . $unidades[$u];
            }
        }
        return "";
    }
}