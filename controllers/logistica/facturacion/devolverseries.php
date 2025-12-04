<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class DevolverSeries extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('tracking'); // Cargar el modelo correcto
    }

    function GetFacturasDevolverSeries()
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

        $FACT_DET = $this->model->getFacturaDet($FACTURA_CAB['data'][0]['Id']);

        $this->jsonResponse([
            'success' => true,
            'data' => $FACTURA_CAB['data'],
            "detalle" => $FACT_DET['data']
        ]);
    }

    function SetDevolverSeries()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $validar_RMAPROD = $this->model->Validar_RMA_PRODUCTOS($data);
        if (count($validar_RMAPROD['data']) > 0) {

            $actualizar_RMAProd = $this->model->Actualizar_RMAProductos($data);
            if ($actualizar_RMAProd['success']) {

                $actualizar_RMADT = $this->model->Actualizar_RMADT($data);
                if ($actualizar_RMADT['success']) {
                    $this->jsonResponse([
                        'success' => true,
                        'mensaje' => 'Serie devuelta correctamente'
                    ]);
                    return;
                } else {
                    $this->jsonResponse([
                        'success' => false,
                        'mensaje' => 'Error al actualizar RMA DT'
                    ], 200);
                    return;
                }
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Error al actualizar RMA Productos'
                ], 200);
                return;
            }
        } else {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Error al procesar'
            ], 200);
            return;
        }


        // $FACTURA_CAB = $this->model->getFacturasCab($secuencia);
    }
}
