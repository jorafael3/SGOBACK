<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class GuiasPickup extends Controller
{
    /**
     * Constructor de la clase Empresa
     * Inicializa el modelo de empresa
     **/

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('guiaspickup'); // Cargar el modelo correcto
    }

    function GetFacturasGuiasPickup()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        $usuario = $data['usrid'] ?? null;
        $empresa = $data['userdata']["empleado_empresa"] ?? null;
        if (!$usuario) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Usuario no proporcionado'
            ], 400);
            return;
        }
        $result = $this->model->getFacturasGuiasPickup($data);

        if ($empresa == "COMPUTRON") {
            echo json_encode($result);
            exit;
        }
        if ($result && $result['success']) {
            $datos = $result['data'];
            $FATURAS_UNICAS = [];
            for ($i = 0; $i < count($datos); $i++) {
                if (in_array($datos[$i]["FACTURA_ID"], $FATURAS_UNICAS)) {
                    continue;
                }
                $FATURAS_UNICAS[] = $datos[$i]["FACTURA_ID"];
            }
            $ARRAY_DATOS = [];
            for ($i = 0; $i < count($FATURAS_UNICAS); $i++) {
                $filtradas = array_filter($datos, fn($item) => $item["FACTURA_ID"] === $FATURAS_UNICAS[$i]);
                if (count($filtradas) > 0) {
                    $CONSOLIDAR = 0;
                    $IDS_BODEGA = "";
                    $CODIGOS_VERIFICADAS = "";
                    $ES_CENTRO_CONSOLIDADO = 0;
                    for ($j = 0; $j < count($filtradas); $j++) {
                        if ($filtradas[$j]["MULTIBODEGA"] == 'SI' && $filtradas[$j]["CONSOLIDAR_FACTURA"] == 1) {
                            $CONSOLIDAR++;
                        }
                        if ($filtradas[$j]["CENTRO_CONSOLIDADO"] == 1) {
                            $ES_CENTRO_CONSOLIDADO = 1;
                        }
                        $IDS_BODEGA .= $filtradas[$j]["BODEGAID"] . ",";
                        $CODIGOS_VERIFICADAS .= $filtradas[$j]["BODEGA_CODIGO"] . ",";
                    }
                    if ($CONSOLIDAR >= 1) {
                        $CODIGOS_VERIFICADAS = "";
                        $IDS_VERIFICADAS = "";
                        $CODIGOS_CONSOLIDADAS = "";
                        $IDS_CONSOLIDADAS = "";
                        for ($j = 0; $j < count($filtradas); $j++) {
                            if ($filtradas[$j]["FACTURA_LISTA_ESTADO"] == 0) {
                                $CODIGOS_VERIFICADAS .= $filtradas[$j]["BODEGA_CODIGO"] . ",";
                                $IDS_VERIFICADAS .= $filtradas[$j]["BODEGAID"] . ",";
                            }
                            if ($filtradas[$j]["FACTURA_LISTA_ESTADO"] == 1) {
                                $CODIGOS_CONSOLIDADAS .= $filtradas[$j]["BODEGA_CODIGO"] . ",";
                                $IDS_CONSOLIDADAS .= $filtradas[$j]["BODEGAID"] . ",";
                            }
                            $filtradas[$j]["CODIGOS_VERIFICADAS"] = rtrim($CODIGOS_VERIFICADAS, ",");
                            $filtradas[$j]["CODIGOS_CONSOLIDADAS"] = rtrim($CODIGOS_CONSOLIDADAS, ",");
                            $filtradas[$j]["IDS_VERIFICADAS"] = rtrim($IDS_VERIFICADAS, ",");
                            $filtradas[$j]["IDS_CONSOLIDADAS"] = rtrim($IDS_CONSOLIDADAS, ",");
                            $filtradas[$j]["IDS_BODEGA"] = rtrim($IDS_BODEGA, ",");
                            $filtradas[$j]["CENTRO_CONSOLIDADO"] = $ES_CENTRO_CONSOLIDADO;
                        }
                        array_push($ARRAY_DATOS, $filtradas[count($filtradas) - 1]);
                    } else {
                        $filtradas[0]["IDS_BODEGA"] = rtrim($IDS_BODEGA, ",");
                        $filtradas[0]["CODIGOS_VERIFICADAS"] = rtrim($CODIGOS_VERIFICADAS, ",");
                        array_push($ARRAY_DATOS, $filtradas[0]);
                    }
                }
            }
            $this->jsonResponse([
                'success' => true,
                'message' => 'Facturas guías pickup obtenidas correctamente',
                'data' => $ARRAY_DATOS,
                "res" => $result
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener facturas guías pickup',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function GetTransporteGuiasPickup()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 1 = GET/POST opcional
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $result = $this->model->getTransporteGuiasPickup();
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener transporte guías pickup',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function GuardarGuiasPickup()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }



        $data = $this->getJsonInput();



        // $BODEGAS = explode(',', $data['bodegaInfo'][0] ?? null)   ?? null;
        $BODEGAS = $data['bodegaInfo'] ?? null;
        $id_unico = date('YmdHis') . rand(1000, 9999);
        $es_consolidado = $data['consolidacion']["activada"];


        $data['id_unico'] = $id_unico;
        $data['usrid'] = $data['userdata']["usrid"] ?? null;
        $data['es_consolidado'] = $es_consolidado;
        $data['numeroGuia'] = $es_consolidado ? $data['consolidacion']["guia"] : $data['numeroGuia'];

        $this->model->db->beginTransaction();

        // echo json_encode($BODEGAS);
        // exit;
        $ERRORESFACTURAS = [];
        $ERRORESGUIA = [];

        if ($es_consolidado == true) {
            foreach ($BODEGAS as $bodega) {
                $data['bodega'] = $bodega;
                $FACTURASLI = $this->model->ActualizarFacturasListasParaConsolidacion($data);
                if (!$FACTURASLI) {
                    $ERRORESFACTURAS[] = $bodega;
                }
            }
            // json_encode($data);
            // exit();
        } else {
            foreach ($BODEGAS as $bodega) {
                $data['bodega'] = $bodega;
                $FACTURASLI = $this->model->ActualizarFacturasListas($data);
                if (!$FACTURASLI) {
                    $ERRORESFACTURAS[] = $bodega;
                }
            }
        }
        if ($es_consolidado == true) {
            foreach ($BODEGAS as $bodega) {
                $data['bodega'] = $bodega;
                $GUIAS = $this->model->GuardarListaGuias($data);
            }
        }


        // foreach ($data['guiasPorBodega'] as $guia) {
        //     $guia['factura'] = $data["factura"];
        //     $guia['usrid'] = $data["usrid"];
        //     $FACTURAS = $this->model->GuardarListaGuias($guia);
        //     if (!$FACTURAS) {
        //         $ERRORESGUIA[] = $guia;
        //     }
        // }

        if (count($ERRORESFACTURAS) + count($ERRORESGUIA) > 0) {
            $this->model->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al actualizar las facturas en las bodegas: ' . implode(', ', $ERRORESFACTURAS),
            ], 200);
            return;
        }
        $this->model->db->commit();
        $this->jsonResponse([
            'success' => true,
            'message' => 'Datos guardados correctamente',
            // 'data_recibida' => $GUIAS
        ], 200);
    }

    function GuardarCambioTipoPedido()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $data = $this->getJsonInput();

        $result = $this->model->GuardarCambioTipoPedido($data);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al guardar cambio tipo pedido',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }
}
