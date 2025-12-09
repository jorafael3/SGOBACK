<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class tracking extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('tracking'); // Cargar el modelo correcto
    }

    function GetFacturasTracking()
    {
        // $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        // if (!$jwtData) {
        //     return; // La respuesta de error ya fue enviada automáticamente
        // }
          echo json_encode("asdasdasd");
        exit();
        $data = $this->getJsonInput();
        $secuencia = $data['secuencia'] ?? null;
        $empresa = $data['userdata']["empleado_empresa"] ?? null;

        echo json_encode($data);
        exit();

        if (!$secuencia) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Secuencia no proporcionada'
            ], 200);
            return;
        }

        $result = $this->model->getFacturasCab($secuencia);

        if (count($result['data']) == 0) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'No se encontraron datos para la secuencia proporcionada'
            ], 200);
            return;
        }
        $det = [];
        if (strtoupper($empresa) == 'CARTIMEX') {
            $det = $this->model->getFacturasDetCartimex($result['data'][0]['Id']);
        } else {
            $det = $this->model->getFacturasDetComputron($result['data'][0]['Id']);
        }

        // echo json_encode($result);
        // exit();

        if ($result && $result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result['data'],
                'detalle' => $det['data']
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener los datos de la factura'
            ], 200);
        }
    }

    function GetFacturasSeries()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        $factura_id = trim($data['factura_id']) ?? null;
        if (!$factura_id) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Factura ID o Bodega no proporcionada'
            ], 200);
            return;
        }

        $result = $this->model->getfacturasSeries($factura_id);

        if ($result && $result['success']) {
            // Agrupar series por código de producto
            $productosAgrupados = [];

            foreach ($result['data'] as $item) {
                $codigo = $item['producto_codigo'];

                if (!isset($productosAgrupados[$codigo])) {
                    $productosAgrupados[$codigo] = [
                        'codigo' => $codigo,
                        'producto_nombre' => $item['producto_nombre'],
                        'series' => []
                    ];
                }

                // Agregar serie si no está duplicada
                if (!in_array($item['Serie'], $productosAgrupados[$codigo]['series'])) {
                    $productosAgrupados[$codigo]['series'][] = $item['Serie'];
                }
            }

            // Convertir arrays de series a strings separados por "/"
            $productosFinal = [];
            foreach ($productosAgrupados as $producto) {
                $productosFinal[] = [
                    'codigo' => $producto['codigo'],
                    'producto_nombre' => $producto['producto_nombre'],
                    'series' => implode('/', $producto['series'])
                ];
            }

            $this->jsonResponse([
                'success' => true,
                'data' => $productosFinal
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener los datos de las series'
            ], 200);
        }
    }
}
