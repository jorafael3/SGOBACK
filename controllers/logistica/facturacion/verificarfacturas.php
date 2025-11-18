<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class VerificarFacturas extends Controller
{
    /**
     * Constructor de la clase Empresa
     * Inicializa el modelo de empresa
     */
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('verificarfacturas'); // Cargar el modelo de empresa
    }

    function GetFacturasPorVerificar()
    {
        // echo json_encode("asdasdasd");
        // exit();

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
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
        $result = $this->model->getFacturasPorVerificar($data);
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
        $bodega = explode(',', $data['bodega'] ?? '') ?? null;
        // $bodega = trim($data['bodega']) ?? null;

        // echo json_encode($data);
        // exit();

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



        $BOD = [];
        for ($i = 0; $i < count($bodega); $i++) {
            $BOD[] = trim($bodega[$i]);
            // $ListaDet = $this->model->getFacturasDetalleVerificar($cab["data"][0]['Id'], trim($bodega[$i]));
            // if ($ListaDet && $ListaDet['success'] && count($ListaDet['data']) > 0) {
            //     array_push($det, $ListaDet['data']);
            // }
        }
        // $det["data"] = array_merge(...$det);
        $BOD = implode(',', $BOD);
        $det = $this->model->getFacturasDetalleVerificar($cab["data"][0]['Id'], trim($BOD));

        $tieneServicios = 0;
        $sinservicios = 0;
        $BODEGAS = [];
        for ($i = 0; $i < count($det["data"]); $i++) {
            // echo json_encode($det);
            // exit();

            // if ($det["data"][$i]['Clase'] === '02') {
            //     $tieneServicios++;
            // } else {
            //     $sinservicios++;
            // }
            if (!in_array($det["data"][$i]['BodegaID'], $BODEGAS)) {
                $BODEGAS[] = $det["data"][$i]['BodegaID'];
            }
            // $ubicacion = $this->model->getFacturasDetalleUbicacion($det["data"][$i]['ProductoId'], $det["data"][$i]['BodegaID']);
            // $det["data"][$i]['ubi1'] = $ubicacion['data'][0]["ubi1"] ?? '';
            // $det["data"][$i]['ubi2'] = $ubicacion['data'][0]["ubi2"] ?? '';
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

    function ValidarSerie()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $data = $this->getJsonInput();
        $serie = $data['serie'] ?? null;
        $codigo_producto = $data['codigo_producto'] ?? null;

        if (!$serie) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Serie no proporcionada'
            ], 400);
            return;
        }

        $result = $this->model->GetSeriesRmaProductos($codigo_producto, $serie);


        if ($result && $result['success']) {
            $result["serie_encontrada"] = count($result['data']) > 0 && trim($result['data'][0]['serie']) == $serie ? true : false;
            $result["serie_disponible"] = (count($result['data']) > 0 && trim($result['data'][0]['estado']) == 'INVENTARIO') ? true : false;
            $result["serie_producto"] = $result['data'][0]['serie'] ?? null;
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

    function FinalizarVerificacion()
    {
        try {
            $data = $this->getJsonInput();

            $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
            if (!$jwtData) {
                return; // La respuesta de error ya fue enviada automáticamente
            }

            $productos_series = $data['detalle_verificacion'] ?? null;
            $factura = $data['factura'] ?? null;
            // $BODEGAS = explode(',', $data['bodegas'][0] ?? null)   ?? null;
            $BODEGAS = $data['bodegas'] ?? null;
            $EMPRESA = $data['userdata']["empleado_empresa"] ?? null;



            // Validar que los datos requeridos estén presentes
            if (!$productos_series || !$factura) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Datos incompletos: se requiere detalle_verificacion y factura'
                ], 400);
                return;
            }

            // Iniciar transacción directamente desde la base de datos del modelo
            $this->model->db->beginTransaction();


            /*** GUARDAR RMA */

            $result = [];
            $ERRORES = [];
            $ERRORESERIES = [];
            $ERRORESFACTURASLIS = [];

            $ValidarRma = $this->model->ValidarTieneRmaFacturasCab($factura);


            if ($ValidarRma['success']) {
                $RMAID = count($ValidarRma['data']) > 0 ? $ValidarRma['data'][0]['ID'] : "";

                //      echo json_encode($ValidarRma);
                // exit();

                if (count($ValidarRma['data']) == 0) {
                    $RMA = $this->model->GuardarRmaFacturasCab($data);
                    $result[] = $RMA;
                    $ValidarRma = $this->model->ValidarTieneRmaFacturasCab($factura);
                    $RMAID = $ValidarRma['data'][0]['ID'];
                }
                // echo json_encode($RMAID);
                // exit();
                for ($i = 0; $i < count($productos_series); $i++) {
                    $cantidad_series = $productos_series[$i]['seriesIngresadas'];
                    for ($j = 0; $j < count($cantidad_series); $j++) {
                        $d = [
                            "rmafacturaid" => $RMAID,
                            "producto" => $productos_series[$i]['productoId'],
                            "serie" => $cantidad_series[$j],
                            "usuario" => $data['usrid']
                        ];
                        $RMADT = $this->model->GuardarRmaFacturasDet($d);
                        if (!$RMADT || !$RMADT['success']) {
                            $ERRORES[] = $RMADT;
                        }
                    }
                }

                if ($EMPRESA == 'CARTIMEX') {
                    for ($i = 0; $i < count($productos_series); $i++) {
                        $cantidad_series = $productos_series[$i]['seriesIngresadas'];
                        for ($j = 0; $j < count($cantidad_series); $j++) {
                            $d = [
                                "factura_id" => $factura,
                                "producto" => $productos_series[$i]['productoId'],
                                "serie" => $cantidad_series[$j],
                                "usuario" => $data['usrid']
                            ];
                            $SGLSERIES = $this->model->GuardarSGLSeries($d);
                            if (!$SGLSERIES || !$SGLSERIES['success']) {
                                $ERRORESERIES[] = $SGLSERIES;
                            }
                        }
                    }
                }

                for ($i = 0; $i < count($BODEGAS); $i++) {

                    $d = [
                        "factura_id" => $factura,
                        "tipo" => "VEN-FA",
                        "usuario" => $data['usrid'],
                        "bodega" => $BODEGAS[$i]
                    ];
                    $FALIS = $this->model->ActualizarFacturasListas($d);
                    if (!$FALIS || !$FALIS['success']) {
                        $ERRORESFACTURASLIS[] = $FALIS;
                    }
                }
                // Verificar si hubo errores en cualquiera de los procesos
                $totalErrores = count($ERRORES) + count($ERRORESERIES) + count($ERRORESFACTURASLIS);
                if ($totalErrores > 0) {
                    // Si hay errores, hacer rollback
                    $this->model->db->rollback();
                    $this->jsonResponse([
                        'success' => false,
                        'error' => 'Se encontraron errores durante el proceso de verificación',
                        'total_errores' => $totalErrores,
                        'errores_rma' => $ERRORES,
                        'errores_series' => $ERRORESERIES,
                        'errores_facturas_listas' => $ERRORESFACTURASLIS,
                        'message' => 'La transacción fue revertida. Ningún cambio fue guardado.'
                    ], 200);
                } else {
                    // Si no hay errores, hacer commit
                    $this->model->db->commit();

                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Factura verificada correctamente',
                        "FACTURAS_LIS" => $FALIS,
                        'detalles' => [
                            'productos_procesados' => count($productos_series),
                            'total_series' => array_sum(array_map(function ($p) {
                                return count($p['seriesIngresadas']);
                            }, $productos_series)),
                            'bodegas_actualizadas' => count($data["bodegas"])
                        ]
                    ], 200);
                }
            } else {
                // Si ValidarRma falló, hacer rollback
                $this->model->db->rollback();

                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Error al validar RMA de la factura',
                    'detalles' => $ValidarRma
                ], 200);
            }
        } catch (Exception $e) {
            // En caso de cualquier excepción, hacer rollback
            try {
                $this->model->db->rollback();
            } catch (Exception $rollbackError) {
                // Ignorar errores de rollback si la transacción ya fue finalizada
            }

            $this->jsonResponse([
                'success' => false,
                'error' => 'Error inesperado durante el proceso de verificación',
                'message' => $e->getMessage(),
                'details' => 'La transacción fue revertida por seguridad'
            ], 500);
        }
    }
}
