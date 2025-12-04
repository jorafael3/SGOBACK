<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class ModificarSeries extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('tracking'); // Cargar el modelo correcto
    }

    function GetFacturasModificarSeries()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        $secuencia = $data['secuencia'] ?? null;
        if (!$secuencia) {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Secuencia no proporcionada'
            ], 200);
            return;
        }

        $FACTURA_CAB = $this->model->getFacturasCab($secuencia);

        if (count($FACTURA_CAB['data']) == 0) {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'No se encontraron datos para la secuencia proporcionada'
            ], 200);
            return;
        }

        $VAL_RMA_CAB = $this->model->getFacturaRmaCab($FACTURA_CAB['data'][0]['Id']);
        if ($VAL_RMA_CAB['success'] === false) {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Error al obtener los datos del detalle de la factura'
            ], 200);
            return;
        }

        if (count($VAL_RMA_CAB['data']) == 0) {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'No se encontraron datos RMA para la factura proporcionada'
            ], 200);
            return;
        };

        $FACT_DET = $this->model->getFacturaRmaDet($VAL_RMA_CAB['data'][0]['ID'], $FACTURA_CAB['data'][0]['Id']);
        $this->jsonResponse([
            'success' => true,
            'data' => $FACTURA_CAB['data'],
            "detalle" => $FACT_DET['data']
        ]);
    }

    function SetModificarSeries()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();



        $VALIDAR_SERIES = $this->model->validarSeries_RMADT($data);

        if ($VALIDAR_SERIES['success'] === false) {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Error al obtener los datos del detalle de la factura'
            ], 200);
            return;
        }

        if (count($VALIDAR_SERIES['data'])  > 0) {
            $BUSCAR_FACTURA_INGERSADA = $this->model->getFacturaIngresadaSeriesModificar($VALIDAR_SERIES['data'][0]["FacturaID"]);
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Serie ya existe en la factura ' . $BUSCAR_FACTURA_INGERSADA['data'][0]['Secuencia'] . ", Si tiene nota de credito, devolver la serie",
                'series_existentes' => $VALIDAR_SERIES['data']
            ], 200);
            return;
        }

        $BUSCAR_RMA_PRODUCTOS = $this->model->getRMAProductos($data);
        if ($BUSCAR_RMA_PRODUCTOS['success'] === false) {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Error al obtener los datos de la serie'
            ], 200);
            return;
        }

        $BUSCAR_SGL_SERIESCOMPRAS = $this->model->getSeriesCompras($data);
        if ($BUSCAR_SGL_SERIESCOMPRAS['success'] === false) {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Error al obtener los datos de la serie'
            ], 200);
            return;
        }

        $EXITE_SERIESCOMPRA = count($BUSCAR_SGL_SERIESCOMPRAS['data']);
        $EXITE_RMAPRODUCTOS = count($BUSCAR_RMA_PRODUCTOS['data']);

        if ($EXITE_SERIESCOMPRA == 0 && $EXITE_RMAPRODUCTOS == 0) {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Serie no existe en la base de datos'
            ], 200);
            return;
        }


        if (isset($BUSCAR_RMA_PRODUCTOS['data'][0]["Estado"]) && $BUSCAR_RMA_PRODUCTOS['data'][0]["Estado"] == "VENDIDO") {
            $BUSCAR_FACTURA_INGERSADA = $this->model->getFacturaIngresadaSeriesModificar($BUSCAR_RMA_PRODUCTOS['data'][0]["FacturaID"]);
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Serie vendida, ya existe en la factura ' . $BUSCAR_FACTURA_INGERSADA['data'][0]['Secuencia'] . ", Si tiene nota de credito, devolver la serie",
            ], 200);
            return;
        };

        $INSERTAR_RMADT = $data["estado"];
        if ($INSERTAR_RMADT == 0) {
            $NUEVO_RMA_DT = $this->model->InsertarRmaDt($data);
            // echo json_encode($NUEVO_RMA_DT);
            // exit;
            if ($NUEVO_RMA_DT['success'] === false) {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Error al insertar el detalle RMA de la factura',
                    "data" => $NUEVO_RMA_DT
                ], 200);
                return;
            }
            $data["RMADT_ID"] = $NUEVO_RMA_DT['data'][0]["RMAIDDT"];
        } else {
            $ACTUALIZAR_RMA_DT = $this->model->ActualizaRmaDt($data);
            if ($ACTUALIZAR_RMA_DT['success'] === false) {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Error al actualizar el detalle RMA de la factura'
                ], 200);
                return;
            }
        }

        $NUEVO_RMAPRODUCTOS = [];
        $ACTUALIZAR_RMAPRODUCTOS = [];
        $ACTUALIZAR_RMAPRODUCTOS_ANTERIOR = [];

        if ($EXITE_SERIESCOMPRA == 1 && $EXITE_RMAPRODUCTOS == 0) {
            $NUEVO_RMAPRODUCTOS = $this->model->Nuevo_RMAProductos($data);
            if ($NUEVO_RMAPRODUCTOS['success'] === false) {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Error al insertar en RMA_PRODUCTOS',
                    "data" => $NUEVO_RMAPRODUCTOS
                ], 200);
                return;
            }
        }

        if ($EXITE_RMAPRODUCTOS == 1) {
            $ACTUALIZAR_RMAPRODUCTOS = $this->model->Actualizar_RMAProductos($data);
            if ($ACTUALIZAR_RMAPRODUCTOS['success'] === false) {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Error al actualizar en RMA_PRODUCTOS',
                    "data" => $ACTUALIZAR_RMAPRODUCTOS
                ], 200);
                return;
            }
        }

        if (trim($data["serie_anterior"]) != "") {
            $ACTUALIZAR_RMAPRODUCTOS_ANTERIOR = $this->model->Actualizar_RMAProductos_Inventario($data);
            if ($ACTUALIZAR_RMAPRODUCTOS_ANTERIOR['success'] === false) {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Error al actualizar en RMA_PRODUCTOS la serie anterior',
                    "data" => $ACTUALIZAR_RMAPRODUCTOS_ANTERIOR
                ], 200);
                return;
            }
        }



        $this->jsonResponse([
            'success' => true,
            'NUEVO_RMAPRODUCTOS' => $NUEVO_RMAPRODUCTOS,
            'ACTUALIZAR_RMAPRODUCTOS' => $ACTUALIZAR_RMAPRODUCTOS,
            'ACTUALIZAR_RMAPRODUCTOS_ANTERIOR' => $ACTUALIZAR_RMAPRODUCTOS_ANTERIOR,
            "mensaje" => 'Serie modificada con éxito'
        ], 200);

        // $result = $this->model->setModificarSeries($data);

        // $this->jsonResponse($result);
    }
}
