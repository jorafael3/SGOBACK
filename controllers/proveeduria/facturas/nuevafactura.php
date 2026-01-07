<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
require_once __DIR__ . '../../../../libs/EmailService.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class nuevafactura extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'proveeduria'; // Especifica la carpeta donde está el modelo
        $this->loadModel('opciones'); // Cargar el modelo correcto
    }

    function GetProveedores()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $result = $this->model->Cargar_Proveedores($data);
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }

    function GetTipoGastos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $result = $this->model->Cargar_TipoGastos($data);
        $monto = $this->model->GetMontoAprobacion();
        $iva = $this->model->GetImpuestoIva();
        
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                'monto_aprobacion' => $monto["data"],
                'iva' => $iva["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }

    function GetResponsables()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $result = $this->model->Cargar_Responsables($data);
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    function SetGuardarFactura()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        $secuencia = $data['secuencia'] ?? null;
        $archivo = $data['documentos'][0]["base64"] ?? [];

        // echo json_encode(constant("UPLOAD_PATH"));
        // exit();

        // Validar que secuencia no esté vacía
        if (!$secuencia) {
            $this->jsonResponse([
                'success' => false,
                'message' => "Secuencia no proporcionada",
            ], 200);
            return;
        }

        // Validar formato: xxx-xxx-xxxxxxxxx
        // Primero separar por guiones
        $partes = explode('-', trim($secuencia));

        if (count($partes) !== 3) {
            $this->jsonResponse([
                'success' => false,
                'message' => "Formato de secuencia inválido. Debe ser: XXX-XXX-XXXXXXXXX (ejemplo: 001-001-000005656)",
            ], 200);
            return;
        }

        // Validar que cada parte sea numérica
        if (!is_numeric($partes[0]) || !is_numeric($partes[1]) || !is_numeric($partes[2])) {
            $this->jsonResponse([
                'success' => false,
                'message' => "Secuencia debe contener solo números. Formato: XXX-XXX-XXXXXXXXX",
            ], 200);
            return;
        }

        // Validar longitud de cada parte
        if (strlen($partes[0]) > 3 || strlen($partes[1]) > 3 || strlen($partes[2]) > 9) {
            $this->jsonResponse([
                'success' => false,
                'message' => "Longitud inválida. Formato correcto: XXX-XXX-XXXXXXXXX",
            ], 200);
            return;
        }

        // Completar con ceros a la izquierda
        $partes[0] = str_pad($partes[0], 3, '0', STR_PAD_LEFT);
        $partes[1] = str_pad($partes[1], 3, '0', STR_PAD_LEFT);
        $partes[2] = str_pad($partes[2], 9, '0', STR_PAD_LEFT);

        // Reconstruir secuencia formateada
        $secuencia_formateada = $partes[0] . '-' . $partes[1] . '-' . $partes[2];
        $data['secuencia'] = $secuencia_formateada;
        $data["preaprobacion"] = 0;
        $data["preaprobacionmonto"] = 0;

        // echo json_encode($data['secuencia']);
        // exit();

        // Validar que no exista esta secuencia y autorización
        $validarSecuenciaAutorizacion = $this->model->ValidarSecuenciaAutorizacion($data);
        if ($validarSecuenciaAutorizacion['success'] && count($validarSecuenciaAutorizacion['data']) > 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => "La secuencia y autorización ya han sido utilizadas. Por favor verifique.",
            ], 200);
            return;
        }

        $validarTipoGasto = $this->model->ValidarTipoGasto($data);
        if (!$validarTipoGasto['success'] || count($validarTipoGasto['data']) === 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => "Tipo de gasto inválido. Por favor verifique.",
            ], 200);
            return;
        }
        $data["preaprobacion"] = $validarTipoGasto['data'][0]['preaprobacion'] ?? 0;

        //** VALIDAR MONTO */

        if (floatval($data["subtotal15"]) + floatval($data["subtotal0"]) >= 1500) {
            $data["preaprobacion"] = 1;
            $data["preaprobacionmonto"] = 1;
        }


        $result = $this->model->GuardarFactura($data);


        if ($result['success']) {
            // Guardar archivo si existe
            if (!empty($archivo)) {
                $uploadDir = UPLOAD_PATH . 'Cartimex/proveeduria/ingresos_facturas/facturas/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $fileData = base64_decode($archivo);
                $filename = $data['documentos'][0]["nombre"]; // Asumir PDF
                $filePath = $uploadDir . $filename;
                if (file_put_contents($filePath, $fileData) === false) {
                    error_log("Error guardando archivo: " . $filePath);
                }
            }

            // Enviar email de confirmación a múltiples destinatarios
            try {
                $emailService = new EmailService();
                // Preparar destinatarios
                $destinatarios = [
                    $data["responsable"]['email_empresa']
                ];
                // Datos para la plantilla
                $datos = [
                    'numero_factura' => $secuencia_formateada,
                    'fecha' => date('d/m/Y'),
                    'proveedor' => $data['proveedor']["proveedor"] ?? 'Proveedor',
                    // 'tipo_gasto' => $data['tipo_gasto'] ?? 'Gasto',
                    'monto' => '$' . number_format($data['total'] ?? 0, 2)
                ];
                // Enviar email
                $mail = $emailService->enviar(
                    $destinatarios,
                    'Factura Registrada: ' . $secuencia_formateada,
                    $datos,
                    'factura',
                    null,
                    "Nueva Factura SGO",
                    [],
                    $data["userdata"]["empresa_name"]
                );
            } catch (Exception $e) {
                error_log("Error enviando email: " . $e->getMessage());
            }

            $this->jsonResponse([
                'success' => true,
                "message" => "Factura guardada exitosamente.",
                "solicitud" => str_pad($result['last_insert_id'], 10, "0", STR_PAD_LEFT),
                "secuencia_formateada" => $secuencia_formateada,
                'data' => $result,
                "email" => $mail
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => "Error guardando factura",
                'data' => $result,
            ], 200);
        }
    }

    //*** SOLICITADAS */

    function GetFacturasSolicitadas()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 1 = GET requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $result = $this->model->Cargar_FacturasSolicitadas();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }
}
