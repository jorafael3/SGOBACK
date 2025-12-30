<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class DespacharFacturas extends Controller
{
    /**
     * Constructor de la clase Empresa
     * Inicializa el modelo de empresa
     **/

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('despacharfacturas'); // Cargar el modelo correcto
    }

    function GetFacturasPorDespachar()
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
        $result = $this->model->getFacturasPorDespachar($data);

        if ($result && $result['success']) {

            $this->jsonResponse([
                'success' => true,
                'message' => 'Facturas por despachar obtenidas correctamente',
                // 'data' => $ARRAY_DATOS,
                "data" => $result["data"]
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

    function DespacharFacturas()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        $usuario = $data["userdata"]['usrid'] ?? null;
        $ERRORES = [];

        $this->model->db->beginTransaction();

        echo json_encode($data);
        exit();

        if (count($data['datos']) == 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No se proporcionaron facturas para despachar'
            ], 400);
            return;
        }

        for ($i = 0; $i < count($data['datos']); $i++) {
            $result = $this->model->DespacharFacturas($data["datos"][$i], $usuario);
            if (!$result['success']) {
                $ERRORES[] = $result;
            }
        }
        if (count($ERRORES) == 0) {
            $this->model->db->commit();
            $this->jsonResponse([
                'success' => true,
                'message' => 'Facturas despachadas correctamente',
                // 'data' => $ARRAY_DATOS,
                "data" => $result["data"]
            ], 200);
        } else {
            $this->model->db->rollBack();
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al despachar facturas',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }
}
