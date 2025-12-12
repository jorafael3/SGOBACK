<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class PrepararFacturas extends Controller
{
    /**
     * Constructor de la clase Empresa
     * Inicializa el modelo de empresa
     */
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('prepararfacturas'); // Cargar el modelo de empresa
    }

    function GetFacturasPorPreparar()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        // Debug temporal: mostrar información del JWT válido
        if (DEBUG) {
            error_log("GetFacturasPorPreparar - JWT válido para empresa: " . ($jwtData['empresa'] ?? 'N/A'));
            error_log("GetFacturasPorPreparar - Usuario: " . ($jwtData['usuario'] ?? 'N/A'));
        }

        $data = $this->getJsonInput();
        $usuario = $data['usrid'] ?? null;

        if (!$usuario) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Usuario no proporcionado'
            ], 400);
            return;
        }

        $result = $this->model->getFacturasPorPreparar($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos de empresas',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function GetFacturasDatos()
    {
        // echo json_encode("asdasdasd");
        // exit();

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        // $usuario = $data['usrid'] ?? null;
        $secuencia = $data['secuencia'] ?? null;
        // $bodega = explode(',', $data['bodega'] ?? '') ?? null;
        $bodega = $data['bodega'] ?? null;


        if (!$secuencia) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Secuencia no proporcionada'
            ], 400);
            return;
        }
        if (!$bodega) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Bodega no proporcionada'
            ], 400);
            return;
        }
        // if (!$usuario) {
        //     $this->jsonResponse([
        //         'success' => false,
        //         'error' => 'Usuario no proporcionado'
        //     ], 400);
        //     return;
        // }

        $cab = $this->model->getFacturasCab($secuencia);
        if (!$cab || !$cab['success']) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos de la cabecera de la factura'
            ], 500);
            return;
        }
        //   echo json_encode($cab);
        //         exit();

        $det = [];
        // for ($i = 0; $i < count($bodega); $i++) {
        //     $ListaDet = $this->model->getFacturasDetalle($cab["data"][0]['Id'], trim($bodega[$i]));
        //     if ($ListaDet && $ListaDet['success'] && count($ListaDet['data']) > 0) {
        //         array_push($det, $ListaDet['data']);
        //     }
        // }
        // $det["data"] = array_merge(...$det);
        $det = $this->model->getFacturasDetalle($cab["data"][0]['Id'], trim($bodega));
        // echo json_encode($det);
        // exit();


        $tieneServicios = 0;
        $sinservicios = 0;
        $BODEGAS = [];
        for ($i = 0; $i < count($det["data"]); $i++) {
            if ($det["data"][$i]['Clase'] === '02') {
                $tieneServicios++;
            } else {
                $sinservicios++;
            }
            if (!in_array($det["data"][$i]['BodegaID'], $BODEGAS)) {
                $BODEGAS[] = $det["data"][$i]['BodegaID'];
            }
            $ubicacion = $this->model->getFacturasDetalleUbicacion($det["data"][$i]['ProductoId'], $det["data"][$i]['BodegaID']);
            $det["data"][$i]['ubi1'] = $ubicacion['data'][0]["ubi1"] ?? '';
            $det["data"][$i]['ubi2'] = $ubicacion['data'][0]["ubi2"] ?? '';
            // $det[$i]['clase'] = 'valor'; // Modificar según sea necesario
        }

        if (count($det["data"]) > 0) {
            // echo json_encode($det);
            // exit();
            $cab['data'][0]['SoloServicios'] = $tieneServicios > 0 && $sinservicios == 0 ? 1 : 0;

            // $cab['data'][0]['sinServicios'] = $sinservicios;
            $result = [
                'success' => true,
                'cabecera' => [$cab['data'][0]] ?? null,
                'detalle' => $det["data"] ?? [],
                'BODEGAS' => $BODEGAS
            ];
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos de empresas',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A'
            ], 200);
        }
    }


    function FinalizarPreparacion()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        $usuario = $data['usrid'] ?? null;
        $factura = $data['factura'] ?? null;
        $bodegas = $data['bodegas'] ?? [];
        $SoloServicios = $data['solo_servicios'] ?? 0;

        if (!$usuario) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Usuario no proporcionado'
            ], 400);
            return;
        }

        if (!$factura) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Factura no proporcionada'
            ], 400);
            return;
        }

        if (count($bodegas) == 0) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Bodegas no proporcionadas'
            ], 400);
            return;
        }
        $id_unico = date('YmdHis') . rand(1000, 9999);
        $data['id_unico'] = '';

        $ERRORES = [];
        $GUARDADAS = [];
        foreach ($bodegas as $bodega) {
            $data['bodega_id'] = $bodega;
            $validar = $this->model->ValidarPreparacion($data);
            if ($validar['success'] && count($validar['data']) > 0) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'La factura ya fue preparada para la bodega ' . $bodega,
                    'detalle' => $validar['data']
                ], 200);
                return;
            }
            $preparar = $this->model->FinalizarPreparacion($data);
            if ($preparar['success'] == true) {
                $GUARDADAS[] = $preparar;
            } else {
                $ERRORES[] = $preparar;
            }
        }

        if (count($ERRORES) == 0) {
            $result = [
                'success' => true,
                'message' => 'Preparación finalizada correctamente',
                'factura' => $factura,
                'bodegas' => $bodegas,
                "data" => $GUARDADAS,
            ];
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Errores al preparar algunas bodegas',
                'detalles' => $ERRORES,
            ], 200);
        }
    }

    // Función temporal de debug para verificar conexiones
    function DebugConnections()
    {
        $jwtData = $this->authenticateAndConfigureModel(1); // Método opcional

        $debugInfo = [
            'jwt_empresa' => $jwtData['empresa'] ?? 'N/A',
            'jwt_empresa_name' => $jwtData['empresa_name'] ?? 'N/A',
            'jwt_usuario' => $jwtData['usuario'] ?? 'N/A',
            'model_empresa' => $this->model->empresaCode ?? 'N/A',
            'active_connections' => Database::getActiveConnections(),
            'timestamp' => date('Y-m-d H:i:s'),
            'headers_received' => getallheaders(),
            'jwt_data_full' => $jwtData
        ];

        $this->jsonResponse([
            'success' => true,
            'debug_info' => $debugInfo
        ], 200);
    }

    // Función simple para verificar empresa actual
    function CheckCurrentEmpresa()
    {
        $jwtData = $this->authenticateAndConfigureModel(1);

        if (!$jwtData) {
            return; // El error ya fue enviado con el código HTTP apropiado
        }

        $this->jsonResponse([
            'success' => true,
            'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
            'empresa_name' => $jwtData['empresa_name'] ?? 'N/A',
            'usuario_actual' => $jwtData['usuario'] ?? 'N/A',
            'token_valid' => true
        ], 200);
    }

    // Endpoint específico para verificar estado del token
    function CheckTokenStatus()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $jwt = str_replace('Bearer ', '', $authHeader);

        if (!$jwt) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Token no proporcionado',
                'error_code' => 'TOKEN_MISSING',
                'requires_login' => true
            ], 401);
            return;
        }

        require_once __DIR__ . '../../../../libs/JwtHelper.php';
        $validationResult = JwtHelper::validateJwtDetailed($jwt);

        if ($validationResult['valid']) {
            $payload = $validationResult['payload'];
            $this->jsonResponse([
                'success' => true,
                'token_valid' => true,
                'empresa' => $payload['empresa'] ?? 'N/A',
                'empresa_name' => $payload['empresa_name'] ?? 'N/A',
                'usuario' => $payload['usuario'] ?? 'N/A',
                'expires_at' => isset($payload['exp']) ? date('Y-m-d H:i:s', $payload['exp']) : 'N/A'
            ], 200);
        } else {
            $httpCode = ($validationResult['error'] === 'TOKEN_EXPIRED') ? 401 : 401;
            $response = [
                'success' => false,
                'token_valid' => false,
                'error' => $validationResult['message'],
                'error_code' => $validationResult['error'],
                'requires_login' => true
            ];

            if ($validationResult['error'] === 'TOKEN_EXPIRED') {
                $response['session_expired'] = true;
                $response['expired_at'] = $validationResult['expired_at'] ?? null;
            }

            $this->jsonResponse($response, $httpCode);
        }
    }
}
