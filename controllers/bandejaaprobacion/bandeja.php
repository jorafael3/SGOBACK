<?php
require_once __DIR__ . '../../../libs/JwtHelper.php';
require_once __DIR__ . '../../../libs/EmailService.php';

class Bandeja extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'bandejaaprobacion/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('bandeja'); // Cargar el modelo correcto
    }

    //*** BANDEJA FACTURAS */

    function GetFacturasAprobacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $params = $this->getJsonInput();

        // echo json_encode($params);
        // exit();

        $result = $this->model->GetFacturasAprobacion($params);
        $result["ruta_archivos"] = constant("DOWNLOAD_PATH") . "Cartimex/proveeduria/ingresos_facturas/facturas/";

        for ($i = 0; $i < count($result["data"]); $i++) {
            $d = $this->model->GetFacturasDocumentosEspeciales($result["data"][$i]['CedBeneficiario']);
            $result["data"][$i]['documentos_especiales'] = $d["data"];
        }

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }

    function GetUsuariosEmail()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = $this->model->GetUsuariosSgo();
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

    function GetUsuariosAprobadores()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = $this->model->GetUsuariosAprobadores();

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

    function GuardarAprobacionRegular()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $result = $this->model->GuardarAprobacionRegular($data);


        if ($result['success']) {
            try {
                $emailService = new EmailService();
                // Preparar destinatarios
                $destinatarios = [];
                if ($data["factura"]["solicitado_por_usuario_email"] != "") {
                    $destinatarios[] = $data["factura"]["solicitado_por_usuario_email"];
                }

                for ($i = 0; $i < count($data["usuarioEmail"]); $i++) {
                    if ($data["usuarioEmail"][$i]["email_sgo"] != "") {
                        $destinatarios[] = $data["usuarioEmail"][$i]["email_sgo"];
                    }
                }


                // Datos para la plantilla
                $datos = [
                    'solicitante' => $data['factura']["solicitado_por_usuario_nombre"] ?? 'Usuario',
                    'solicitud' => $data['factura']["ID_factura"] ?? '0000',
                    'numero_factura' => $data['factura']["factura_secuencia"] ?? '0000',
                    'fecha_solicitud' => $data['factura']["factura_fecha_solicitud"] ?? '00/00/0000',
                    'proveedor' => $data['factura']["factura_proveedor"] ?? 'Proveedor',
                    'valor_total' => '$' . number_format($data['factura']["valor_total"] ?? 0, 2),
                    'Aprobador' => $data['userdata']["nombre"] ?? 'Aprobador',
                    "comentario" => $data['comentario'] ?? ''
                ];
                // Enviar email
                $mail = $emailService->enviar(
                    $destinatarios,
                    'Factura Aprobada: ' . $data['factura']["factura_secuencia"],
                    $datos,
                    'factura_aprobada',
                    null,
                    "Factura Aprobada " . $data['factura']["Empresa"],
                    [],
                    $data["factura"]["Empresa"]
                );
            } catch (Exception $e) {
                error_log("Error enviando email: " . $e->getMessage());
            }

            $this->jsonResponse([
                'success' => true,
                "message" => "Aprobación guardada correctamente",
                'data' => $result,
                "email" => $mail,
                "destinatarios" => $destinatarios
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "message" => "Error al guardar la aprobación",
                'data' => $result,
            ], 200);
        }
    }

    function GuardarPreaprobacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $result = $this->model->GuardarPreaprobacion($data);

        if ($result['success']) {

            try {
                $emailService = new EmailService();
                // Preparar destinatarios
                $destinatarios = [];
                if ($data["factura"]["solicitado_por_usuario_email"] != "") {
                    $destinatarios[] = $data["factura"]["solicitado_por_usuario_email"];
                }

                for ($i = 0; $i < count($data["usuarioEmail"]); $i++) {
                    if ($data["usuarioEmail"][$i]["email_sgo"] != "") {
                        $destinatarios[] = $data["usuarioEmail"][$i]["email_sgo"];
                    }
                }

                // Datos para la plantilla
                $datos = [
                    'solicitante' => $data['factura']["solicitado_por_usuario_nombre"] ?? 'Usuario',
                    'solicitud' => $data['factura']["ID_factura"] ?? '0000',
                    'numero_factura' => $data['factura']["factura_secuencia"] ?? '0000',
                    'fecha_solicitud' => $data['factura']["factura_fecha_solicitud"] ?? '00/00/0000',
                    'proveedor' => $data['factura']["factura_proveedor"] ?? 'Proveedor',
                    'valor_total' => '$' . number_format($data['factura']["valor_total"] ?? 0, 2),
                    'PreAprobador' => $data['userdata']["nombre"] ?? 'Aprobador',
                    'Aprobador_Principal' => $data['usuarioPreaprobacion']["nombre"] ?? 'Aprobador',
                    "comentario" => $data['comentario'] ?? ''
                ];
                // Enviar email
                $mail = $emailService->enviar(
                    $destinatarios,
                    'Factura PreAprobada: ' . $data['factura']["factura_secuencia"],
                    $datos,
                    'factura_preaprobada',
                    null,
                    "Factura PreAprobada " . $data['factura']["Empresa"],
                    [],
                    $data["factura"]["Empresa"]
                );


                $destinatarios = [];
                if ($data["usuarioPreaprobacion"]["nombre"] != "") {
                    $destinatarios[] = $data["usuarioPreaprobacion"]["email_empresa"];
                }

                $mail = $emailService->enviar(
                    $destinatarios,
                    'Factura Por Aprobar: ' . $data['factura']["factura_secuencia"],
                    $datos,
                    'factura_preaprobada_aprobador_principal',
                    null,
                    "Factura Por Aprobar " . $data['factura']["Empresa"],
                    [],
                    $data["factura"]["Empresa"]
                );
            } catch (Exception $e) {
                error_log("Error enviando email: " . $e->getMessage());
            }


            $this->jsonResponse([
                'success' => true,
                "message" => "Aprobación guardada correctamente",
                'data' => $result,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "message" => "Error al guardar la aprobación",
                'data' => $result,
            ], 200);
        }
    }

    function RechazarFactura()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();


        $result = $this->model->RechazarFactura($data);

        if ($result['success']) {

            try {
                $emailService = new EmailService();
                // Preparar destinatarios
                $destinatarios = [];
                if ($data["factura"]["solicitado_por_usuario_email"] != "") {
                    $destinatarios[] = $data["factura"]["solicitado_por_usuario_email"];
                }
                // Datos para la plantilla
                $datos = [
                    'solicitante' => $data['factura']["solicitado_por_usuario_nombre"] ?? 'Usuario',
                    'solicitud' => $data['factura']["ID_factura"] ?? '0000',
                    'numero_factura' => $data['factura']["factura_secuencia"] ?? '0000',
                    'fecha_solicitud' => $data['factura']["factura_fecha_solicitud"] ?? '00/00/0000',
                    'proveedor' => $data['factura']["factura_proveedor"] ?? 'Proveedor',
                    'valor_total' => '$' . number_format($data['factura']["valor_total"] ?? 0, 2),
                    'Rechazado_por' => $data['userdata']["nombre"] ?? 'Aprobador',
                    'comentario' => $data['comentario'] ?? 'Sin comentario',
                ];
                // Enviar email
                $mail = $emailService->enviar(
                    $destinatarios,
                    'Factura Rechazada: ' . $data['factura']["factura_secuencia"],
                    $datos,
                    'factura_rechazada',
                    null,
                    "Factura Rechazada " . $data['factura']["Empresa"],
                    [],
                    $data["factura"]["Empresa"]
                );
            } catch (Exception $e) {
                error_log("Error enviando email: " . $e->getMessage());
            }


            $this->jsonResponse([
                'success' => true,
                "message" => "Aprobación guardada correctamente",
                'data' => $result,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "message" => "Error al guardar la aprobación",
                'data' => $result,
            ], 200);
        }
    }

    //********************************************/
    //*** BANDEJA VACACIONES */

    function GetVacacionesAprobacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetVacacionesAprobacion($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos personales',
                'details' => $result
            ], 200);
        }
    }

    function GetVacacionesAprobar()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetVacacionesAprobar($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos personales',
                'details' => $result
            ], 200);
        }
    }

    function GetVacacionesRechazadas()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetVacacionesRechazadas($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos personales',
                'details' => $result
            ], 200);
        }
    }

    function GenerarPDFVacacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->GenerarPDFVacacion($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos personales',
                'details' => $result
            ], 200);
        }
    }

    //********************************************/
    ///** ORDENES DE COMPRA */

    function GetOrdenesDeCompraAprobacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();
        $aprobador = $data["userdata"]["aprobador"] ?? 0;
        $modulos_aprobacion = $data["userdata"]["modulos_aprobados"] ?? [];
        $modulos_aprobacion = explode(",", $modulos_aprobacion);

        if ($aprobador == 1 && in_array("ordenes_compra", $modulos_aprobacion)) {
            $result = $this->model->GetOrdenesDeCompraAprobacion($data);

            if ($result && $result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'data' => $result['data'],
                    "params" => $data,
                    "aprobador" => $aprobador,
                    "modulos_aprobacion" => $modulos_aprobacion
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Error al obtener datos',
                    'details' => $result,
                    "params" => $data
                ], 200);
            }
        }else{
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'No tiene permisos para aprobar órdenes de compra',
            ], 200);
        }
    }

    function GetOrdenesDeCompraAprobacionDetalles()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetOrdenesDeCompraAprobacionDetalle($data);

        if ($result && $result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result['data'],
                "params" => $data
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos personales',
                'details' => $result
            ], 200);
        }
    }
    function AprobarOrdenDeCompra()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();



        $result = $this->model->AprobarOrdenDeCompra($data);

        if ($result && $result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                "params" => $data,

            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos personales',
                'details' => $result
            ], 200);
        }
    }
}
